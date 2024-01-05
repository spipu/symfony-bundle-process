<?php
namespace Spipu\ProcessBundle\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use Spipu\ConfigurationBundle\Tests\SpipuConfigurationMock;
use Spipu\ProcessBundle\Service\ModuleConfiguration;

class ModuleConfigurationTest extends TestCase
{
    /**
     * @param TestCase $testCase
     * @param array $values
     * @param string $mailSenderConfig
     * @return ModuleConfiguration
     */
    public static function getService(TestCase $testCase, array $values = [], string $mailSenderConfig = 'app.email.sender')
    {
        $defaultValues = [
            'process.task.automatic_rerun'          => 1,
            'process.task.can_kill'                 => 1,
            'process.task.limit_per_rerun'          => 1000,
            'process.task.force_schedule_for_async' => 0,
            'process.failed.send_email'             => 1,
            'process.failed.email'                  => 'to@mock.fr',
            $mailSenderConfig                       => 'from@mock.fr',
            'process.failed.max_retry'              => 5,
            'process.cleanup.finished_logs'         => 1,
            'process.cleanup.finished_logs_after'   => 5,
            'process.cleanup.finished_tasks'        => 1,
            'process.cleanup.finished_tasks_after'  => 7,
            'process.folder.import'                 => './var/import/',
            'process.folder.export'                 => './var/export/',
        ];

        $values = array_merge($defaultValues, $values);

        $manager = SpipuConfigurationMock::getManager($testCase, null, $values);

        $moduleConfiguration = new ModuleConfiguration(
            $manager,
            $mailSenderConfig
        );

        return $moduleConfiguration;
    }

    public function testService()
    {
        $values = [
            'process.cleanup.finished_logs'         => 0,
            'process.cleanup.finished_tasks'        => 0,
            'process.failed.send_email'             => 0,
            'process.task.automatic_rerun'          => 0,
            'process.task.can_kill'                 => 0,
            'process.task.can_execute'              => 0,
            'process.task.force_schedule_for_async' => 0,
        ];
        $moduleConfiguration = self::getService($this, $values);

        $this->assertSame(false, $moduleConfiguration->hasCleanupFinishedLogs());
        $this->assertSame(false, $moduleConfiguration->hasCleanupFinishedTasks());
        $this->assertSame(false, $moduleConfiguration->hasFailedSendEmail());
        $this->assertSame(false, $moduleConfiguration->hasTaskAutomaticRerun());
        $this->assertSame(false, $moduleConfiguration->hasTaskCanKill());
        $this->assertSame(false, $moduleConfiguration->hasTaskCanExecute());
        $this->assertSame(false, $moduleConfiguration->hasTaskForceScheduleForAsync());

        $values = [
            'process.cleanup.finished_logs'         => 1,
            'process.cleanup.finished_tasks'        => 0,
            'process.failed.send_email'             => 0,
            'process.task.automatic_rerun'          => 0,
            'process.task.can_kill'                 => 0,
            'process.task.can_execute'              => 0,
            'process.task.force_schedule_for_async' => 0,
        ];
        $moduleConfiguration = self::getService($this, $values);

        $this->assertSame(true, $moduleConfiguration->hasCleanupFinishedLogs());
        $this->assertSame(false, $moduleConfiguration->hasCleanupFinishedTasks());
        $this->assertSame(false, $moduleConfiguration->hasFailedSendEmail());
        $this->assertSame(false, $moduleConfiguration->hasTaskAutomaticRerun());
        $this->assertSame(false, $moduleConfiguration->hasTaskCanKill());
        $this->assertSame(false, $moduleConfiguration->hasTaskCanExecute());
        $this->assertSame(false, $moduleConfiguration->hasTaskForceScheduleForAsync());

        $values = [
            'process.cleanup.finished_logs'         => 0,
            'process.cleanup.finished_tasks'        => 1,
            'process.failed.send_email'             => 0,
            'process.task.automatic_rerun'          => 0,
            'process.task.can_kill'                 => 0,
            'process.task.can_execute'              => 0,
            'process.task.force_schedule_for_async' => 0,
        ];
        $moduleConfiguration = self::getService($this, $values);

        $this->assertSame(false, $moduleConfiguration->hasCleanupFinishedLogs());
        $this->assertSame(true, $moduleConfiguration->hasCleanupFinishedTasks());
        $this->assertSame(false, $moduleConfiguration->hasFailedSendEmail());
        $this->assertSame(false, $moduleConfiguration->hasTaskAutomaticRerun());
        $this->assertSame(false, $moduleConfiguration->hasTaskCanKill());
        $this->assertSame(false, $moduleConfiguration->hasTaskCanExecute());
        $this->assertSame(false, $moduleConfiguration->hasTaskForceScheduleForAsync());

        $values = [
            'process.cleanup.finished_logs'         => 0,
            'process.cleanup.finished_tasks'        => 0,
            'process.failed.send_email'             => 1,
            'process.task.automatic_rerun'          => 0,
            'process.task.can_kill'                 => 0,
            'process.task.can_execute'              => 0,
            'process.task.force_schedule_for_async' => 0,
        ];
        $moduleConfiguration = self::getService($this, $values);

        $this->assertSame(false, $moduleConfiguration->hasCleanupFinishedLogs());
        $this->assertSame(false, $moduleConfiguration->hasCleanupFinishedTasks());
        $this->assertSame(true, $moduleConfiguration->hasFailedSendEmail());
        $this->assertSame(false, $moduleConfiguration->hasTaskAutomaticRerun());
        $this->assertSame(false, $moduleConfiguration->hasTaskCanKill());
        $this->assertSame(false, $moduleConfiguration->hasTaskCanExecute());
        $this->assertSame(false, $moduleConfiguration->hasTaskForceScheduleForAsync());

        $values = [
            'process.cleanup.finished_logs'         => 0,
            'process.cleanup.finished_tasks'        => 0,
            'process.failed.send_email'             => 0,
            'process.task.automatic_rerun'          => 1,
            'process.task.can_kill'                 => 0,
            'process.task.can_execute'              => 0,
            'process.task.force_schedule_for_async' => 0,
        ];
        $moduleConfiguration = self::getService($this, $values);

        $this->assertSame(false, $moduleConfiguration->hasCleanupFinishedLogs());
        $this->assertSame(false, $moduleConfiguration->hasCleanupFinishedTasks());
        $this->assertSame(false, $moduleConfiguration->hasFailedSendEmail());
        $this->assertSame(true, $moduleConfiguration->hasTaskAutomaticRerun());
        $this->assertSame(false, $moduleConfiguration->hasTaskCanKill());
        $this->assertSame(false, $moduleConfiguration->hasTaskCanExecute());
        $this->assertSame(false, $moduleConfiguration->hasTaskForceScheduleForAsync());

        $values = [
            'process.cleanup.finished_logs'         => 0,
            'process.cleanup.finished_tasks'        => 0,
            'process.failed.send_email'             => 0,
            'process.task.automatic_rerun'          => 0,
            'process.task.can_kill'                 => 1,
            'process.task.can_execute'              => 0,
            'process.task.force_schedule_for_async' => 0,
        ];
        $moduleConfiguration = self::getService($this, $values);

        $this->assertSame(false, $moduleConfiguration->hasCleanupFinishedLogs());
        $this->assertSame(false, $moduleConfiguration->hasCleanupFinishedTasks());
        $this->assertSame(false, $moduleConfiguration->hasFailedSendEmail());
        $this->assertSame(false, $moduleConfiguration->hasTaskAutomaticRerun());
        $this->assertSame(true, $moduleConfiguration->hasTaskCanKill());
        $this->assertSame(false, $moduleConfiguration->hasTaskCanExecute());
        $this->assertSame(false, $moduleConfiguration->hasTaskForceScheduleForAsync());

        $values = [
            'process.cleanup.finished_logs'         => 0,
            'process.cleanup.finished_tasks'        => 0,
            'process.failed.send_email'             => 0,
            'process.task.automatic_rerun'          => 0,
            'process.task.can_kill'                 => 0,
            'process.task.can_execute'              => 1,
            'process.task.force_schedule_for_async' => 0,
        ];
        $moduleConfiguration = self::getService($this, $values);

        $this->assertSame(false, $moduleConfiguration->hasCleanupFinishedLogs());
        $this->assertSame(false, $moduleConfiguration->hasCleanupFinishedTasks());
        $this->assertSame(false, $moduleConfiguration->hasFailedSendEmail());
        $this->assertSame(false, $moduleConfiguration->hasTaskAutomaticRerun());
        $this->assertSame(false, $moduleConfiguration->hasTaskCanKill());
        $this->assertSame(true,  $moduleConfiguration->hasTaskCanExecute());
        $this->assertSame(false, $moduleConfiguration->hasTaskForceScheduleForAsync());

        $values = [
            'process.cleanup.finished_logs'         => 0,
            'process.cleanup.finished_tasks'        => 0,
            'process.failed.send_email'             => 0,
            'process.task.automatic_rerun'          => 0,
            'process.task.can_kill'                 => 0,
            'process.task.can_execute'              => 0,
            'process.task.force_schedule_for_async' => 1,
        ];
        $moduleConfiguration = self::getService($this, $values);

        $this->assertSame(false, $moduleConfiguration->hasCleanupFinishedLogs());
        $this->assertSame(false, $moduleConfiguration->hasCleanupFinishedTasks());
        $this->assertSame(false, $moduleConfiguration->hasFailedSendEmail());
        $this->assertSame(false, $moduleConfiguration->hasTaskAutomaticRerun());
        $this->assertSame(false, $moduleConfiguration->hasTaskCanKill());
        $this->assertSame(false, $moduleConfiguration->hasTaskCanExecute());
        $this->assertSame(true, $moduleConfiguration->hasTaskForceScheduleForAsync());

        $values = [
            'process.cleanup.finished_logs_after'  => 42,
            'process.cleanup.finished_tasks_after' => 43,
            'mock.email.sender'                    => 'from@test.fr',
            'process.failed.email'                 => 'to@test.fr',
            'process.failed.max_retry'             => 44,
        ];

        $moduleConfiguration = self::getService($this, $values, 'mock.email.sender');
        $this->assertSame(42, $moduleConfiguration->getCleanupFinishedLogsAfter());
        $this->assertSame(43, $moduleConfiguration->getCleanupFinishedTasksAfter());
        $this->assertSame('from@test.fr', $moduleConfiguration->getFailedEmailFrom());
        $this->assertSame('to@test.fr', $moduleConfiguration->getFailedEmailTo());
        $this->assertSame(44, $moduleConfiguration->getFailedMaxRetry());
        $this->assertSame('./var/import/', $moduleConfiguration->getFolderImport());
        $this->assertSame('./var/export/', $moduleConfiguration->getFolderExport());

        $values = [
            'process.cleanup.finished_logs_after'  => -1,
            'process.cleanup.finished_tasks_after' => -2,
            'process.failed.max_retry'             => -3,
        ];

        $moduleConfiguration = self::getService($this, $values);
        $this->assertSame(0, $moduleConfiguration->getCleanupFinishedLogsAfter());
        $this->assertSame(0, $moduleConfiguration->getCleanupFinishedTasksAfter());
        $this->assertSame(0, $moduleConfiguration->getFailedMaxRetry());


        $moduleConfiguration = self::getService($this);
        $this->assertSame(1000, $moduleConfiguration->getTaskLimitPerRerun());

        $moduleConfiguration = self::getService($this, ['process.task.limit_per_rerun' => 50]);
        $this->assertSame(50, $moduleConfiguration->getTaskLimitPerRerun());


        $moduleConfiguration = self::getService($this, ['process.task.limit_per_rerun' => 0]);
        $this->assertSame(1, $moduleConfiguration->getTaskLimitPerRerun());

        $moduleConfiguration = self::getService($this, ['process.task.limit_per_rerun' => -10]);
        $this->assertSame(1, $moduleConfiguration->getTaskLimitPerRerun());
    }
}
