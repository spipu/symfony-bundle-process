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
| `process.task.can_execute` | boolean | `1` | Master switch: enable/disable all process execution, including the `rerun` and `cleanup` cron actions. Only the `check-pid` cron action keeps running when disabled |
| `process.task.can_kill` | boolean | `0` | Allow killing running processes from the admin UI |
| `process.task.force_schedule_for_async` | boolean | `0` | Force async processes into the queue even when triggered from the UI |

### Automatic Re-run

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `process.task.automatic_rerun` | boolean | `1` | Enable the automatic retry mechanism |
| `process.task.limit_per_rerun` | integer | `1000` | Max tasks to re-run per `cron-manager rerun` invocation |
| `process.task.rerun_every` | integer | `5` | Minimum minutes between re-run attempts |
| `process.task.waiting_alert_after` | integer | `60` | Minutes after which a task still in `created` status (created and not scheduled, or scheduled date exceeded) is reported by the `check-pid` cron action through the `WaitingTasksEvent` event |

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

The bundle dispatches the following Symfony events:

| Event class | Event code | When |
|-------------|------------|------|
| `Spipu\ProcessBundle\Event\LogFailedEvent` | `spipu.process.log.failed` | When a process log is marked as failed |
| `Spipu\ProcessBundle\Event\WaitingTasksEvent` | `spipu.process.task.waiting` | When the `check-pid` cron action finds tasks still in `created` status after `process.task.waiting_alert_after` minutes |

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

Subscribe to `WaitingTasksEvent` to be alerted when the task queue is not processed (e.g. the `rerun` cron action is not running, or execution is disabled).
It is dispatched at each `check-pid` execution while waiting tasks remain, and only when at least one task is found:

```php
use Spipu\ProcessBundle\Event\WaitingTasksEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
class MyWaitingTasksListener
{
    public function __invoke(WaitingTasksEvent $event): void
    {
        $nbTasks = $event->getNbTasks();                         // total
        $nbUnscheduled = $event->getNbUnscheduledTasks();        // created before the delay, without schedule date
        $nbScheduled = $event->getNbScheduledTasks();            // schedule date exceeded by the delay
        $delay = $event->getWaitingDelay();                      // in minutes
        $canExecute = $event->hasTaskCanExecute();               // false if execution is disabled
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

# Check running task PIDs, mark dead tasks as failed, and report waiting tasks (cron action: check-pid)
php bin/console spipu:process:cron-manager check-pid

# Count all tasks
php bin/console spipu:process:check

# Count tasks in a specific status
php bin/console spipu:process:check --status=failed

# Output only the raw count (for monitoring scripts)
php bin/console spipu:process:check --direct
```

[back](./README.md)
