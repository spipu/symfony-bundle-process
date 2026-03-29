# Installing Spipu Process Bundle

[back](./README.md)

## Requirements

- PHP 8.1+
- Symfony 6.4+
- `spipu/core-bundle`
- `spipu/ui-bundle`
- `spipu/configuration-bundle`

## Installation

```bash
composer require spipu/process-bundle
```

## Configuration

### 1. Register the bundle

In `config/bundles.php`:

```php
return [
    // ...
    Spipu\CoreBundle\SpipuCoreBundle::class => ['all' => true],
    Spipu\UiBundle\SpipuUiBundle::class => ['all' => true],
    Spipu\ConfigurationBundle\SpipuConfigurationBundle::class => ['all' => true],
    Spipu\ProcessBundle\SpipuProcessBundle::class => ['all' => true],
];
```

### 2. Import the routes

In `config/routes.yaml`:

```yaml
spipu_process:
    resource: '@SpipuProcessBundle/config/routes.yaml'
```

### 3. Run database migrations

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

### 4. Load default configuration fixtures

```bash
php bin/console spipu:fixtures:load
```

This seeds the ConfigurationBundle keys used by ProcessBundle (see [Module Configuration](./configuration.md)).

### 5. Create the process YAML directory

By default, process definitions are loaded from `config/process/`. Create it:

```bash
mkdir -p config/process
```

Or override the path in `config/packages/spipu_process.yaml`:

```yaml
spipu_process:
    config_dir: '%kernel.project_dir%/config/process'
```

### 6. Register custom steps as services

Any class implementing `StepInterface` must be tagged `spipu.process.step`:

```yaml
# config/services.yaml
App\Step\:
    resource: '../src/App/Step/'
    tags:
        - { name: spipu.process.step }
```

## Available Commands

| Command | Description |
|---------|-------------|
| `spipu:process:rerun <task-id>` | Re-run an existing failed or queued task by its ID |
| `spipu:process:cron-manager <action>` | Run a cron management action: `rerun`, `cleanup`, or `check-pid` |
| `spipu:process:check` | Check and display the number of tasks (optionally filtered by `--status`) |

### `spipu:process:rerun`

Re-runs an existing task by its database ID.

```bash
php bin/console spipu:process:rerun <task-id>
# Enable debug output (show logs in terminal)
php bin/console spipu:process:rerun <task-id> --debug
```

### `spipu:process:cron-manager`

Intended to be scheduled as a regular cron job. Accepts one required argument:

| Action | Description |
|--------|-------------|
| `rerun` | Re-run waiting/failed tasks eligible for automatic retry |
| `cleanup` | Remove old finished task records and logs |
| `check-pid` | Verify running tasks still have a live PID; mark orphans as failed |

```bash
# Typical crontab entries
*/5 * * * * php bin/console spipu:process:cron-manager rerun
0 2 * * * php bin/console spipu:process:cron-manager cleanup
* * * * *  php bin/console spipu:process:cron-manager check-pid
```

### `spipu:process:check`

Counts tasks in the queue, optionally filtered by status.

```bash
# Show all tasks count
php bin/console spipu:process:check

# Show tasks in a specific status
php bin/console spipu:process:check --status=failed

# Output only the raw number (useful for monitoring scripts)
php bin/console spipu:process:check --direct
```

## Admin UI

The task and log admin interface is mounted under the routes registered in the bundle (prefix depends on your application routing). The main entry points are:

| Route name | URL path | Role required |
|-----------|----------|---------------|
| `spipu_process_admin_task_list` | `/process/task/` | `ROLE_ADMIN_MANAGE_PROCESS_SHOW` |
| `spipu_process_admin_task_show` | `/process/task/show/{id}` | `ROLE_ADMIN_MANAGE_PROCESS_SHOW` |
| `spipu_process_admin_task_execute_choice` | `/process/task/execute-choice` | `ROLE_ADMIN_MANAGE_PROCESS_EXECUTE` |
| `spipu_process_admin_task_execute` | `/process/task/execute/{processCode}` | `ROLE_ADMIN_MANAGE_PROCESS_EXECUTE` |
| `spipu_process_admin_task_rerun` | `/process/task/rerun/{id}` | `ROLE_ADMIN_MANAGE_PROCESS_RERUN` |
| `spipu_process_admin_task_kill` | `/process/task/kill/{id}` | `ROLE_ADMIN_MANAGE_PROCESS_KILL` |
| `spipu_process_admin_task_delete` | `/process/task/delete/{id}` | `ROLE_ADMIN_MANAGE_PROCESS_DELETE` |
| `spipu_process_admin_log_list` | `/process/log/` | `ROLE_ADMIN_MANAGE_PROCESS_SHOW` |
| `spipu_process_admin_log_show` | `/process/log/show/{id}` | `ROLE_ADMIN_MANAGE_PROCESS_SHOW` |
| `spipu_process_admin_log_delete` | `/process/log/delete/{id}` | `ROLE_ADMIN_MANAGE_PROCESS_DELETE` |

See [Module Configuration](./configuration.md) for role details.

[back](./README.md)
