# TrueAsync Known Bugs / Issues to Report

## FileSystemWatcher — recursive mode does not deliver subdirectory events

**Discovered:** April 2026  
**Needs:** GitHub issue filed, or discussion with Edmond (TrueAsync maintainer)

### Symptom

`new FileSystemWatcher($path, recursive: true)` only delivers events for files
created or modified **directly in the watched root directory**. Files changed inside
any subdirectory produce no events whatsoever.

### Reproduction

```php
$watcher = new \Async\FileSystemWatcher('/watched/root', recursive: true);
foreach ($watcher as $event) {
    // fires for: touch /watched/root/file.php
    // silent for: touch /watched/root/subdir/file.php
}
```

### Confirmed Steps

1. Start a watcher on `/workspaces/farmers-store-inventory` with `recursive: true`
2. `touch /workspaces/farmers-store-inventory/test.php` → event fires ✓
3. `touch /workspaces/farmers-store-inventory/src/test.php` → no event ✗

### Impact

Hot-reload using `FileSystemWatcher` silently fails for any real project whose source
files live in subdirectories (i.e. all of them).

### Workaround (in place)

`HotCodeReload\Watcher` uses an `inotifywait` subprocess instead:

```bash
inotifywait -m -r -e close_write,moved_to --format '%f' -q src/ config/
```

`fgets()` on the subprocess stdout inside a TrueAsync coroutine suspends without
blocking other coroutines. See `src/mezzio-async/src/HotCodeReload/Watcher.php`.

### Reference

- Stub: `stubs/async.php` — `Async\FileSystemWatcher` constructor
  `(string $path, bool $recursive = false, bool $coalesce = true)`
- TrueAsync docs: https://true-async.github.io/en/docs.html
