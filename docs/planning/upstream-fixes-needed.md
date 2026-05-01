# Upstream Fixes Needed

## webware/command-bus

### `EmptyPipelineHandler` — handle `CommandResultInterface` passthrough

**File:** `src/Handler/EmptyPipelineHandler.php`

**Problem:** When `CommandHandlerMiddleware` forwards the `CommandResult` via
`$handler->handle($result)` to continue the pipeline (e.g. to `PostHandleMiddleware`),
any middleware that calls `$handler->handle($command)` at the end of the chain reaches
`EmptyPipelineHandler` with a `CommandResult` (which implements `CommandInterface`).
The current implementation throws `CommandException::commandNotHandled()` for it.

**Fix:** Check `$command instanceof CommandResultInterface` first and return it directly:

```php
public function handle(CommandInterface $command): CommandResultInterface
{
    if ($command instanceof CommandResultInterface) {
        return $command;
    }

    throw CommandException::commandNotHandled($command::class);
}
```

**Root cause context:** This gap only surfaced after fixing `CommandHandlerMiddleware`
to forward via `$handler->handle($result)` instead of returning directly. Before that
fix, `EmptyPipelineHandler` was never reached with a `CommandResult`.
