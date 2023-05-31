<?php
namespace Spipu\ProcessBundle\Tests\Unit\Entity\Process;

use PHPUnit\Framework\TestCase;
use Spipu\ProcessBundle\Entity\Process\Process;
use Spipu\ProcessBundle\Tests\SpipuProcessMock;

class ProcessTest extends TestCase
{
    public static function getProcess(TestCase $testCase)
    {
        $process = new Process(
            'test',
            'Test',
            OptionsTest::getOptions($testCase, ['can_be_put_in_queue' => true, 'can_be_rerun_automatically' => false, 'process_lock_on_failed' => true, 'process_lock' => [], 'needed_role' => null, 'automatic_report' => false,]),
            InputsTest::getInputs($testCase, ['name' => ['type' => 'string']]),
            ParametersTest::getParameters($testCase, ['Foo' => '{{ input.name }}']),
            [
                'first' => StepTest::getStep(
                    $testCase,
                    'first',
                    SpipuProcessMock::getStepCountProcessor(),
                    [
                        'string' => '{{ Foo }} first',
                        'array' => [1]
                    ]
                ),
                'second' => StepTest::getStep(
                    $testCase,
                    'second',
                    SpipuProcessMock::getStepCountProcessor(),
                    [
                        'string' => '{{ Foo }} second',
                        'array' => [1, 2, 3]
                    ]
                ),
            ]
        );

         return $process;
    }

    public function testExecuteOk()
    {
        $process = self::getProcess($this);
        $process->getInputs()->set('name', 'Bar');
        $process->getParameters()->set('input.name', 'Bar');

        $this->assertSame('test', $process->getCode());
        $this->assertSame('Test', $process->getName());
        $this->assertSame('Bar', $process->getParameters()->get('Foo'));

        $this->assertSame(2, count($process->getSteps()));

        $this->assertSame('first', $process->getSteps()['first']->getCode());
        $this->assertSame('second', $process->getSteps()['second']->getCode());

        $this->assertInstanceOf(SpipuProcessMock::COUNT_CLASSNAME, $process->getSteps()['first']->getProcessor());
        $this->assertInstanceOf(SpipuProcessMock::COUNT_CLASSNAME, $process->getSteps()['second']->getProcessor());

        $process->setLogId(42);
        $this->assertSame(42, $process->getLogId());

        $task = SpipuProcessMock::getTaskEntity(1);
        $process->setTask($task);
        $this->assertSame($task, $process->getTask());

        $this->assertSame('Bar', $process->getInputs()->get('name'));
        $this->assertSame(true, $process->getOptions()->canBePutInQueue());
        $this->assertSame(false, $process->getOptions()->canBeRerunAutomatically());
    }
}