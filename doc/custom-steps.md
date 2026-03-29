# Creating Custom Steps

[back](./README.md)

## StepInterface

Every step must implement `Spipu\ProcessBundle\Step\StepInterface`:

```php
namespace Spipu\ProcessBundle\Step;

use Spipu\ProcessBundle\Entity\Process\ParametersInterface;
use Spipu\ProcessBundle\Service\LoggerInterface;
use Spipu\ProcessBundle\Exception\StepException;

interface StepInterface
{
    /**
     * @throws StepException
     */
    public function execute(ParametersInterface $parameters, LoggerInterface $logger): mixed;
}
```

The return value of `execute()` becomes available to subsequent steps as `{{ result.your_step_code }}`.

## Minimal Step Example

```php
namespace App\Step;

use Spipu\ProcessBundle\Step\StepInterface;
use Spipu\ProcessBundle\Service\LoggerInterface;
use Spipu\ProcessBundle\Entity\Process\ParametersInterface;

class SendNotification implements StepInterface
{
    public function __construct(private MailerInterface $mailer) {}

    public function execute(ParametersInterface $parameters, LoggerInterface $logger): mixed
    {
        $to      = $parameters->get('to');
        $subject = $parameters->get('subject');
        $body    = $parameters->get('body');

        $logger->info('Sending notification to ' . $to);

        // ... send email ...

        $logger->notice('Notification sent.');

        return true;
    }
}
```

## Reading Parameters

```php
// Get a parameter value (throws if the key does not exist in the parameter chain)
$value = $parameters->get('my_param');

// Set a default before reading (safe way to handle optional parameters)
$parameters->setDefaultValue('my_optional_param', 'default_value');
$value = $parameters->get('my_optional_param');
```

Note: `ParametersInterface::get()` does not accept a default as a second argument. Use `setDefaultValue()` before calling `get()` when a parameter is optional.

## Logging

The `LoggerInterface` extends `Psr\Log\LoggerInterface` and provides all standard PSR-3 log levels:

| Method | Log level | Effect on process status |
|--------|-----------|--------------------------|
| `$logger->debug(string $message)` | Debug | None |
| `$logger->info(string $message)` | Informational | None |
| `$logger->notice(string $message)` | Notice | None |
| `$logger->warning(string $message)` | Warning | Process ends with "warning" status |
| `$logger->error(string $message)` | Error | None (use exceptions to fail) |
| `$logger->critical(string $message)` | Critical | None (use exceptions to fail) |

To fail a process, throw a `Spipu\ProcessBundle\Exception\StepException`.

To stop execution early **without** marking the process as failed (e.g. a graceful early exit), throw a `Spipu\ProcessBundle\Exception\StopExecutionException`. The process will be marked as finished and a warning will be logged.

## Reporting Progress

Use `$logger->setProgress(int $progressOnCurrentStep)` to update the progress bar visible in the admin UI. The value must be an integer between **0** and **100** representing completion percentage **within the current step**.

```php
public function execute(ParametersInterface $parameters, LoggerInterface $logger): mixed
{
    $items = $parameters->get('items');
    $total = count($items);

    foreach ($items as $i => $item) {
        $logger->setProgress((int) (($i / $total) * 100));
        $logger->info(sprintf('Processing item %d/%d', $i + 1, $total));

        // ... process $item ...
    }

    $logger->setProgress(100);

    return $total;
}
```

## Registering the Step

Tag the service as `spipu.process.step` and mark it `public` so the process engine can resolve it by FQCN:

```yaml
# config/services.yaml
App\Step\SendNotification:
    tags:
        - { name: spipu.process.step }
    public: true
```

Or use a glob pattern (recommended):

```yaml
App\Step\:
    resource: '../src/Step/'
    tags:
        - { name: spipu.process.step }
    public: true
```

## Using the Step in a Process

```yaml
# config/process/notify.yaml
spipu_process:
    send_notification:
        name: "Send a notification"
        options:
            can_be_put_in_queue: false
            can_be_rerun_automatically: false
        inputs:
            recipient:
                type: string
                required: true
            message:
                type: string
                required: true
        steps:
            notify:
                class: App\Step\SendNotification
                parameters:
                    to:      "{{ input.recipient }}"
                    subject: "New notification"
                    body:    "{{ input.message }}"
```

## StepReportInterface (Optional)

If your step should attach structured output to the task report, implement `Spipu\ProcessBundle\Step\StepReportInterface` and use `StepReportTrait`:

```php
use Spipu\ProcessBundle\Step\StepInterface;
use Spipu\ProcessBundle\Step\StepReportInterface;
use Spipu\ProcessBundle\Step\StepReportTrait;

class MyStep implements StepInterface, StepReportInterface
{
    use StepReportTrait;

    public function execute(ParametersInterface $parameters, LoggerInterface $logger): mixed
    {
        // ... do work ...

        $this->addReportMessage('Processed 42 records');
        $this->addReportWarning('3 records skipped', 'https://example.com/details');
        // $this->addReportError('Something went wrong');

        return 42;
    }
}
```

The trait provides three methods (all protected):

| Method | Description |
|--------|-------------|
| `addReportMessage(string $message, ?string $link = null)` | Add an informational line to the report |
| `addReportWarning(string $message, ?string $link = null)` | Add a warning line to the report |
| `addReportError(string $message, ?string $link = null)` | Add an error line to the report |

The `setReport(?Report $report)` method (required by `StepReportInterface`) is implemented by the trait and called automatically by the process engine — do not call it yourself.

## Injecting Services

Steps are full DI container services — inject any service via the constructor:

```php
class ImportFromCsv implements StepInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private MyRepository $repository
    ) {}

    public function execute(ParametersInterface $parameters, LoggerInterface $logger): mixed
    {
        // ...
    }
}
```

[back](./README.md)
