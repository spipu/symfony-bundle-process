# Spipu Process Bundle

The **ProcessBundle** is a background job / workflow execution engine. Processes are defined declaratively in YAML files as sequences of **steps**. Each step is a PHP class that performs one unit of work. Processes can be queued, scheduled, monitored, and retried from the admin UI.

## Documentation

- [Installation](./install.md)
- [Defining Processes (YAML)](./process-definition.md)
- [Built-in Steps Reference](./steps.md)
- [Creating Custom Steps](./custom-steps.md)
- [Module Configuration](./configuration.md)

## Features

- **YAML process definitions** — define workflows declaratively without writing orchestration code
- **Step-based execution** — each step is a DI-container service implementing `StepInterface`
- **Parameter interpolation** — pass and transform values between steps using `{{ variable }}` syntax
- **LoopStep** — iterate over a collection, executing a sub-sequence of steps per item
- **Queue support** — processes can be put in an async execution queue
- **Admin UI** — list, run, schedule, and monitor processes at `/admin/process/`
- **Task log** — per-execution log with status (`created`, `running`, `finished`, `failed`)
- **Email on failure** — send an alert email when a process fails
- **Automatic retry** — failed processes can be retried automatically
- **File input support** — processes can accept file uploads as input
- **CLI commands** — `spipu:process:rerun`, `spipu:process:cron-manager`, `spipu:process:check` for background and monitoring

## Requirements

- PHP >= 8.3
- Symfony >= 7.4
- `spipu/core-bundle`
- `spipu/ui-bundle`
- `spipu/configuration-bundle`
- Doctrine ORM

## Quick Start

```bash
composer require spipu/process-bundle
```

Then define your first process YAML and create your step classes. See [Installation](./install.md).

## Architecture Overview

```
YAML definition
   └── Process (inputs + steps)
         └── Step 1 → Step 2 → Step 3 → ...
               └── each step: implements StepInterface, tagged spipu.process.step
```

Processes are loaded from YAML by `ConfigReader`. `ProcessManager` loads, validates inputs, and executes. Each step receives a `ParametersInterface` bag (shared across all steps in a process) and a `LoggerInterface`.
