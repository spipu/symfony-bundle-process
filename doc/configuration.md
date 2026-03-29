# Process Bundle Module Configuration

[back](./README.md)

## Configuration Keys

All ProcessBundle runtime settings are stored via the **ConfigurationBundle** and are editable from the admin UI. Default values are seeded by running `php bin/console spipu:fixtures:load`.

### Archive

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `process.archive.keep_number` | integer | `5` | Number of archived files to keep |

### Task Execution

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `process.task.can_execute` | boolean | `1` | Master switch: enable/disable all process execution |
| `process.task.can_kill` | boolean | `0` | Allow killing running processes from the admin UI |
| `process.task.force_schedule_for_async` | boolean | `0` | Force async processes into the queue even when triggered from the UI |

### Automatic Re-run

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `process.task.automatic_rerun` | boolean | `1` | Enable the automatic retry mechanism |
| `process.task.limit_per_rerun` | integer | `1000` | Max tasks to re-run per `cron-manager rerun` invocation |
| `process.task.rerun_every` | integer | `5` | Minimum minutes between re-run attempts |

### Failure Notifications

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `process.failed.send_email` | boolean | `1` | Send an email when a process fails |
| `process.failed.email` | email | `debug@my-website.fr` | Recipient address for failure notifications |
| `process.failed.max_retry` | integer | `5` | Max automatic retries before marking as permanently failed |

The sender address for failure emails is read from the `app.email.sender` ConfigurationBundle key (configurable via `ModuleConfiguration`).

### Log Cleanup

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `process.cleanup.finished_logs` | boolean | `1` | Enable automatic cleanup of finished task logs |
| `process.cleanup.finished_logs_after` | integer | `7` | Delete finished logs older than N days |
| `process.cleanup.finished_tasks` | boolean | `1` | Enable automatic cleanup of finished task records |
| `process.cleanup.finished_tasks_after` | integer | `7` | Delete finished task records older than N days |

## Roles

| Role | Description |
|------|-------------|
| `ROLE_ADMIN_MANAGE_PROCESS_SHOW` | View process task list and task/log details |
| `ROLE_ADMIN_MANAGE_PROCESS_EXECUTE` | Execute and queue processes from the admin UI |
| `ROLE_ADMIN_MANAGE_PROCESS_RERUN` | Re-run existing tasks from the admin UI |
| `ROLE_ADMIN_MANAGE_PROCESS_KILL` | Kill running processes from the admin UI |
| `ROLE_ADMIN_MANAGE_PROCESS_DELETE` | Delete tasks and logs from the admin UI |
| `ROLE_ADMIN_MANAGE_PROCESS` | Full process management (includes all of the above) |

`ROLE_SUPER_ADMIN` inherits `ROLE_ADMIN_MANAGE_PROCESS` automatically.

## Events

The bundle dispatches the following Symfony event:

| Event class | Event code | When |
|-------------|------------|------|
| `Spipu\ProcessBundle\Event\LogFailedEvent` | `spipu.process.log.failed` | When a process log is marked as failed |

Subscribe to this event to implement custom failure handling (e.g. custom notifications):

```php
use Spipu\ProcessBundle\Event\LogFailedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
class MyProcessFailureListener
{
    public function __invoke(LogFailedEvent $event): void
    {
        $log = $event->getProcessLog();
        $url = $event->getProcessLogUrl();
        $exception = $event->getException(); // may be null
        // ...
    }
}
```

## CLI Usage

```bash
# Re-run a specific failed task by its database ID
php bin/console spipu:process:rerun <task-id>

# Re-run a failed task with console log output
php bin/console spipu:process:rerun <task-id> --debug

# Run waiting tasks (cron action: rerun)
php bin/console spipu:process:cron-manager rerun

# Clean up finished tasks and logs (cron action: cleanup)
php bin/console spipu:process:cron-manager cleanup

# Check running task PIDs and mark dead tasks as failed (cron action: check-pid)
php bin/console spipu:process:cron-manager check-pid

# Count all tasks
php bin/console spipu:process:check

# Count tasks in a specific status
php bin/console spipu:process:check --status=failed

# Output only the raw count (for monitoring scripts)
php bin/console spipu:process:check --direct
```

[back](./README.md)
