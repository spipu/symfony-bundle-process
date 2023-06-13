<?php

/**
 * This file is part of a Spipu Bundle
 *
 * (c) Laurent Minguet
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spipu\ProcessBundle\Service;

use Spipu\ConfigurationBundle\Service\ConfigurationManager as Manager;

class ModuleConfiguration
{
    private Manager $manager;
    private string $mailSenderConfig;

    public function __construct(
        Manager $manager,
        string $mailSenderConfig = 'app.email.sender'
    ) {
        $this->manager = $manager;
        $this->mailSenderConfig = $mailSenderConfig;
    }

    public function hasTaskAutomaticRerun(): bool
    {
        return ($this->getConfigurationValue('process.task.automatic_rerun') == 1);
    }

    public function hasTaskCanKill(): bool
    {
        return ($this->getConfigurationValue('process.task.can_kill') == 1);
    }

    public function hasTaskCanExecute(): bool
    {
        return ($this->getConfigurationValue('process.task.can_execute') == 1);
    }

    public function getTaskLimitPerRerun(): int
    {
        $value = (int) $this->getConfigurationValue('process.task.limit_per_rerun');

        if ($value < 1) {
            $value = 1;
        }

        return $value;
    }

    public function hasFailedSendEmail(): bool
    {
        return ($this->getConfigurationValue('process.failed.send_email') == 1);
    }

    public function getFailedEmailTo(): string
    {
        return (string) $this->getConfigurationValue('process.failed.email');
    }

    public function getFailedEmailFrom(): string
    {
        return (string) $this->getConfigurationValue($this->mailSenderConfig);
    }

    public function getFailedMaxRetry(): int
    {
        $value = (int) $this->getConfigurationValue('process.failed.max_retry');

        if ($value < 0) {
            $value = 0;
        }

        return $value;
    }

    public function hasCleanupFinishedLogs(): bool
    {
        return ($this->getConfigurationValue('process.cleanup.finished_logs') == 1);
    }

    public function getCleanupFinishedLogsAfter(): int
    {
        $value = (int) $this->getConfigurationValue('process.cleanup.finished_logs_after');

        if ($value < 0) {
            $value = 0;
        }

        return $value;
    }

    public function hasCleanupFinishedTasks(): bool
    {
        return ($this->getConfigurationValue('process.cleanup.finished_tasks') == 1);
    }

    public function getCleanupFinishedTasksAfter(): int
    {
        $value = (int) $this->getConfigurationValue('process.cleanup.finished_tasks_after');

        if ($value < 0) {
            $value = 0;
        }

        return $value;
    }

    public function getFolderImport(): string
    {
        return (string) $this->getConfigurationValue('process.folder.import');
    }

    public function getFolderExport(): string
    {
        return (string) $this->getConfigurationValue('process.folder.export');
    }

    private function getConfigurationValue(string $key): mixed
    {
        return $this->manager->get($key);
    }
}
