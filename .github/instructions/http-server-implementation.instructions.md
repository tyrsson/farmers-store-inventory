---
description: "Use when implementing or reviewing the HTTP server layer: AsyncRunner, RequestParser, ResponseEmitter, StaticFileHandler, ServerRequestFactory, socket accept loop, HTTP/1.1 request parsing, response serialisation, connection handling, or socket resource management."
applyTo: "src/mezzio-async/src/Http/**/*.php"
---

> **⚠ NOT THE ACTIVE RUNTIME (as of April 28, 2026).** `src/mezzio-async/` is retained for
> future reintegration but is NOT loaded, NOT registered, and NOT running. The active stack
> is the **PHP built-in web server** (`php -S 0.0.0.0:8080 -t public/`) inside a `php:latest`
> container — verified from `.devcontainer/docker-compose.yml` and `public/index.php`.
> These conventions apply only when working on files under `src/mezzio-async/src/Http/`.

# HTTP Server Implementation Guide

## Actual Class Inventory

| Class | Role |
|-------|------|
| `Http\Server` | TCP socket, scheduler entry, Scope, accept loop, signal handling |
| `Http\ServerFactory` | Reads host/port config, injects logger, returns `Server` |
| `Runner\AsyncRunner` | Mezzio integration — parse, dispatch, emit, log per connection |
| `Http\RequestParser` | fread chunk accumulator → `?ServerRequestInterface` |
| `Http\ResponseEmitter` | Serialises PSR-7 response to raw bytes via `fwrite` |
| `Http\ServerRequestFactory` | Invokable — builds `Laminas\Diactoros\ServerRequest` |
| `Http\StaticFileHandler` | Serves `public/` with path-traversal guard |

---

## Server Socket Setup

The server socket is created via `socket_create` with `SO_REUSEPORT` set. This is
**required** for hot-reload: `pcntl_exec` replaces the process image but TrueAsync's
io_uring/epoll layer holds a kernel-level reference to the listening fd that survives
PHP's `fclose()`. The new process must be able to bind the same port immediately without
waiting for the old socket to be released.

```php
private function createServerSocket(): mixed
{
    $sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    socket_set_option($sock, SOL_SOCKET, SO_REUSEADDR, 1);
    socket_set_option($sock, SOL_SOCKET, SO_REUSEPORT, 1);
    socket_bind($sock, $this->host, $this->port);
    socket_listen($sock, 4096);
    return socket_export_stream($sock);  // ← back to a stream resource
}
```

The stream resource from `socket_export_stream()` is used normally with
`stream_socket_accept` in the accept loop. The server socket is closed in the `finally`
block of the accept coroutine, **and** again after `await()` returns (outside the
scheduler) as a safety net before `pcntl_exec`.

---

## Accept Loop

```php
$scope->spawn(function () use ($server, $scope): void {
    try {
        while (true) {
            $peerName = '';
            $conn     = @stream_socket_accept($server, -1, $peerName);

            if ($conn === false) {
                break; // scope cancelled — exit cleanly
            }

            $scope->spawn($this->handleConnection(...), $conn, $peerName);
        }
    } finally {
        fclose($server);
    }
});
```

**Critical rules:**
- `stream_socket_accept` takes **positional arguments only** — never named args
- `@` suppresses the warning emitted when accept is interrupted by scope cancellation
- The server socket is closed in `finally` of the accept coroutine
- Connection coroutines are spawned into the same `$scope` so the scope's exception
  handler shields the accept loop from individual connection errors

---

## Connection Handling — Parse Outside Logging try/finally

Modern browsers open speculative TCP connections without sending data. `RequestParser::parse()`
returns `null` for these. Parse must happen **outside** the logging `try/finally` so that
null (empty) connections are dropped silently with no log entry. If parse is inside `try`,
the `finally` logging block always runs and produces `UNKNOWN / 500 / 0ms` noise.

**Correct structure:**
```php
private function handleConnection(mixed $conn, string $peerName): void
{
    // PARSE — outside the logging try/finally
    try {
        $request = $this->parser->parse($conn, $peerName);
    } catch (Throwable $e) {
        $this->logger->warning('Parse error from ' . $peerName, ['exception' => $e]);
        fclose($conn);
        return;
    }

    if ($request === null) {
        // Speculative pre-connect — no bytes ever sent; drop silently
        fclose($conn);
        return;
    }

    // Real request — always log
    $method  = $request->getMethod();
    $target  = $request->getRequestTarget();
    $startNs = hrtime(true);
    $status  = 500;

    try {
        if ($this->staticFiles->tryServe($method, $target, $conn)) {
            $status = 200;
            return;
        }

        $response = $this->handler->handle($request);
        $status   = $response->getStatusCode();
        $this->emitter->emit($response, $conn);
    } catch (Throwable $e) {
        $this->logger->error('Request error from ' . $peerName, ['exception' => $e]);
        if (is_resource($conn)) {
            $this->emitter->emitError($conn, 500, 'Internal Server Error');
        }
    } finally {
        if (is_resource($conn)) {
            fclose($conn);
        }

        $ms = round((hrtime(true) - $startNs) / 1_000_000, 2);
        $this->logger->info(sprintf('%s %s %d %sms %s', $method, $target, $status, $ms, $peerName));
    }
}
```

---

## RequestParser

`RequestParser::parse(mixed $socket, string $peerName): ?ServerRequestInterface`

**Strategy:** accumulate `fread()` chunks until `\r\n\r\n` header terminator is found, then
hand the raw string to `ServerRequestFactory`. All `fread()` inside a TrueAsync coroutine
automatically yields to the scheduler — no `stream_set_blocking()` needed.

**Return contract:**
- Returns `null` when the first `fread` returns `''` or `false` with no data accumulated.
  This is a browser speculative pre-connect; the caller must close and return silently.
- Throws `RuntimeException` when data had started arriving and the connection was then dropped.
- Throws `RuntimeException` on malformed request line or oversized request (>8 MiB).

**Constants:**
```php
private const int READ_SIZE = 8192;
private const int MAX_SIZE  = 8_388_608; // 8 MiB hard ceiling
```

**Header parsing:** all header names are lowercased. Values are collected as arrays
(`$headers[$name][]`). The factory receives `array<string, string[]>`.

**Body reading:** after the header block, any bytes already buffered past `\r\n\r\n` are
treated as the body start. Remaining bytes declared by `Content-Length` are read in a
second loop.

---

## ServerRequestFactory

Invokable — called directly by `RequestParser` with:

```php
($this->requestFactory)($method, $target, $protocol, $headers, $body, $peerName);
```

Produces a `Laminas\Diactoros\ServerRequest`. Handles:
- URI construction from `Host` header + target
- Cookie parsing (`; ` separator → `parse_str`)
- Query param parsing via `parse_url` + `parse_str`
- Parsed body for `application/x-www-form-urlencoded`
- Body stream: `Laminas\Diactoros\Stream('php://temp', 'wb+')`

---

## ResponseEmitter

```php
// Normal response
public function emit(ResponseInterface $response, mixed $socket): void

// Error shortcut (no PSR-7 object needed)
public function emitError(mixed $socket, int $status, string $reason): void
```

`emit()` writes: status line → headers → `\r\n` → body (65 536-byte read chunks).  
All `fwrite()` calls yield the coroutine on slow clients automatically.

`emitError()` writes a minimal response:
```
HTTP/1.1 {status} {reason}\r\nContent-Length: 0\r\nConnection: close\r\n\r\n
```

---

## Hot-Reload (HotCodeReload\Watcher)

`HotCodeReload\Watcher` watches source paths for `.php`/`.phtml` changes and triggers
a server restart via a callback.

**Implementation:** uses `inotifywait` (from `inotify-tools`) as a subprocess rather
than `Async\FileSystemWatcher`. TrueAsync's `FileSystemWatcher` with `recursive=true` is
buggy — it only delivers events for files created directly in the watched root, not in
subdirectories.

```php
$cmd = array_merge(
    ['inotifywait', '-m', '-e', 'close_write,moved_to', '--format', '%f', '-q'],
    $this->recursive ? ['-r'] : [],
    $this->paths,
);
$process = proc_open($cmd, [0 => ['pipe','r'], 1 => ['pipe','w'], 2 => ['file','/dev/null','w']], $pipes);
```

`fgets($stdout)` inside the coroutine suspends without blocking other coroutines.
The subprocess is `proc_terminate()`d in `finally` so it is always cleaned up.

**Docker requirement:** `inotify-tools` must be installed in the container image
(`apt-get install -y inotify-tools`). Both `docker/php/Dockerfile` and
`.devcontainer/docker/php/Dockerfile` install it.

**Restart sequence inside `Http\Server::listen()`:**
1. `Channel(1)` acts as first-wins between signal listener (`false`) and watcher (`true`).
2. On file change the scope is cancelled, `awaitAfterCancellation()` drains connections.
3. `pcntl_exec(PHP_BINARY, $argv)` is called **outside** `await(spawn(...))` — after the
   TrueAsync scheduler has fully exited. Calling it inside the scheduler leaves io_uring
   references open and causes `Address already in use` in the restarted process.

---

## StaticFileHandler

`tryServe(string $method, string $path, mixed $conn): bool`

- Returns `true` and writes the full response if a matching file exists in `public/`.
- Returns `false` if the request should continue to the Mezzio pipeline.
- Only handles `GET` and `HEAD` requests.
- Strips query string before resolving path.
- Path traversal prevention: `realpath()` result must start with `$publicRoot`.
- MIME types mapped for: css, js, json, html, svg, png, jpg, jpeg, gif, webp, ico,
  woff, woff2, ttf, txt.
- Response headers include `Cache-Control: public, max-age=3600`.

`StaticFileHandlerFactory` hardcodes the public root as `'public'` (relative to CWD,
which is `/var/www/app` inside Docker).


---

## Error Handling in Connection Coroutines

Connection coroutines must not propagate exceptions to the server scope — they must catch
and log locally:

```php
$scope->spawn(function() use ($conn, $peerName) {
    try {
        $request  = $this->parser->parse($conn);
        $psrReq   = ($this->requestFactory)($request);
        $this->dispatcher->dispatch(new RequestEvent($psrReq, $conn));
    } catch (Async\AsyncCancellation $e) {
        // Server is shutting down — do nothing, let finally close the socket
        throw $e;   // re-throw so scope knows coroutine was cancelled
    } catch (\Throwable $e) {
        $this->dispatcher->dispatch(new ConnectionErrorEvent($e, $peerName));
        $this->emitErrorResponse($conn, 500);
    } finally {
        fclose($conn);
    }
});
```

**Rule:** Only re-throw `Async\AsyncCancellation`. Swallow or handle all other exceptions
at the connection boundary. Never let a single bad request bring down the entire server.

---

## PSR-7 Request Construction

Use `ServerRequestAsyncFactory` to build the `Laminas\Diactoros\ServerRequest`. Populate
`$_SERVER`-equivalent keys from the parsed raw request:

```php
$serverParams = [
    'REQUEST_METHOD'  => $raw->method,
    'REQUEST_URI'     => $raw->target,
    'SERVER_PROTOCOL' => $raw->protocol,
    'REMOTE_ADDR'     => $peerName,
    'HTTP_HOST'       => $raw->headers['host'][0] ?? '',
    // ... other SAPI equivalents
];
```

Apply `FilterServerRequestInterface` (XForwardedHeaders) the same way
`ServerRequestSwooleFactory` does in mezzio-swoole.

---

## HTTP Keep-Alive (v2 Concern)

HTTP/1.1 persistent connections (`Connection: keep-alive`) are **out of scope for v1**. In
v1, every connection is closed after the response is sent. Set `Connection: close` in all
responses to ensure clients do not expect keep-alive.

---

## Chunked Transfer Encoding (Reading)

If `Transfer-Encoding: chunked` is present in the request:

```
chunk-size CRLF
chunk-data CRLF
...
0 CRLF
CRLF
```

De-chunk the body before passing it to the PSR-7 stream. Reference: RFC 7230 §4.1.

---

## Static Files

For static file serving, `fread()` and `fwrite()` inside coroutines are automatically
non-blocking. Serve files with a read-file-write-socket loop:

```php
$file = fopen($filePath, 'rb');
try {
    while (!feof($file)) {
        fwrite($socket, fread($file, self::CHUNK_SIZE));
    }
} finally {
    fclose($file);
}
```

No extra async machinery required.
