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

use Spipu\ConfigurationBundle\Service\Manager;

class ModuleConfiguration
{
    /**
     * @var Manager
     */
    private $manager;

    /**
     * @var string
     */
    private $mailSenderConfig;

    /**
     * Configuration constructor.
     * @param Manager $manager
     * @param string $mailSenderConfig
     */
    public function __construct(
        Manager $manager,
        string $mailSenderConfig = 'app.email.sender'
    ) {
        $this->manager = $manager;
        $this->mailSenderConfig = $mailSenderConfig;
    }

    /**
     * @return bool
     */
    public function hasTaskAutomaticRerun(): bool
    {
        return ($this->getConfigurationValue('process.task.automatic_rerun') == 1);
    }

    /**
     * @return bool
     */
    public function hasTaskCanKill(): bool
    {
        return ($this->getConfigurationValue('process.task.can_kill') == 1);
    }

    /**
     * @return bool
     */
    public function hasFailedSendEmail(): bool
    {
        return ($this->getConfigurationValue('process.failed.send_email') == 1);
    }

    /**
     * @return string
     */
    public function getFailedEmailTo(): string
    {
        return (string) $this->getConfigurationValue('process.failed.email');
    }

    /**
     * @return string
     */
    public function getFailedEmailFrom(): string
    {
        return (string) $this->getConfigurationValue($this->mailSenderConfig);
    }

    /**
     * @return int
     */
    public function getFailedMaxRetry(): int
    {
        $value = (int) $this->getConfigurationValue('process.failed.max_retry');

        if ($value < 0) {
            $value = 0;
        }

        return $value;
    }

    /**
     * @return bool
     */
    public function hasCleanupFinishedLogs(): bool
    {
        return ($this->getConfigurationValue('process.cleanup.finished_logs') == 1);
    }

    /**
     * @return int
     */
    public function getCleanupFinishedLogsAfter(): int
    {
        $value = (int) $this->getConfigurationValue('process.cleanup.finished_logs_after');

        if ($value < 0) {
            $value = 0;
        }

        return $value;
    }

    /**
     * @return bool
     */
    public function hasCleanupFinishedTasks(): bool
    {
        return ($this->getConfigurationValue('process.cleanup.finished_tasks') == 1);
    }

    /**
     * @return int
     */
    public function getCleanupFinishedTasksAfter(): int
    {
        $value = (int) $this->getConfigurationValue('process.cleanup.finished_tasks_after');

        if ($value < 0) {
            $value = 0;
        }

        return $value;
    }

    /**
     * @return string
     */
    public function getFolderImport(): string
    {
        return (string) $this->getConfigurationValue('process.folder.import');
    }

    /**
     * @return string
     */
    public function getFolderExport(): string
    {
        return (string) $this->getConfigurationValue('process.folder.export');
    }

    /**
     * @param string $key
     * @return mixed
     */
    private function getConfigurationValue(string $key)
    {
        return $this->manager->get($key);
    }
}
