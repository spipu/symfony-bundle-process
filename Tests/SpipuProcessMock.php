<?php
namespace Spipu\ProcessBundle\Tests;

use PHPUnit\Framework\TestCase;
use Spipu\ConfigurationBundle\Tests\SpipuConfigurationMock;
use Spipu\CoreBundle\Tests\SymfonyMock;
use Spipu\ProcessBundle\Entity\Log;
use Spipu\ProcessBundle\Entity\Process\ParametersInterface;
use Spipu\ProcessBundle\Entity\Task;
use Spipu\ProcessBundle\Exception\CallRestException;
use Spipu\ProcessBundle\Service\LoggerInterface;
use Spipu\ProcessBundle\Service\MainParameters;
use Spipu\ProcessBundle\Step\StepInterface;

class SpipuProcessMock extends TestCase
{
    const COUNT_CLASSNAME = StepCountMock::class;
    const ERROR_CLASSNAME = StepErrorMock::class;

    /**
     * @param int|null $id
     * @return Log
     */
    public static function getLogEntity(int $id = null)
    {
        $entity = new Log();

        if ($id !== null) {
            $setId = \Closure::bind(
                function ($value) {
                    $this->id = $value;
                },
                $entity,
                $entity
            );
            $setId($id);
        }

        return $entity;
    }

    /**
     * @param int|null $id
     * @return Task
     */
    public static function getTaskEntity(int $id = null)
    {
        $entity = new Task();

        if ($id !== null) {
            $setId = \Closure::bind(
                function ($value) {
                    $this->id = $value;
                },
                $entity,
                $entity
            );
            $setId($id);
        }

        return $entity;
    }

    /**
     * @param TestCase $testCase
     * @return MainParameters
     */
    public static function getMainParameters(TestCase $testCase)
    {
        return new MainParameters(
            self::getContainer($testCase),
            SpipuConfigurationMock::getManager($testCase)
        );
    }

    /**
     * @param TestCase $testCase
     * @return \PHPUnit\Framework\MockObject\MockObject|\Symfony\Component\DependencyInjection\ContainerInterface
     */
    public static function getContainer(TestCase $testCase)
    {
        $services = [
            self::COUNT_CLASSNAME => self::getStepCountProcessor(),
            self::ERROR_CLASSNAME => self::getStepErrorProcessor(),
        ];

        $parameters = [
            'app.workflows' => self::getConfigurationSampleDataBuilt(),
        ];

        return SymfonyMock::getContainer($testCase, $services, $parameters);
    }

    /**
     * @return array
     */
    public static function getConfigurationSampleData(): array
    {
        return [
            'test' => [
                'name' => 'Test',
                'options' => [
                    'can_be_put_in_queue' => false,
                    'can_be_rerun_automatically' => false,
                ],
                'inputs' => [
                    'input1' => ['type' => 'string', 'required' => true, 'allowed_mime_types' => []],
                    'input2' => ['type' => 'int',    'required' => true, 'allowed_mime_types' => []],
                    'input3' => ['type' => 'float',  'required' => true, 'allowed_mime_types' => []],
                    'input4' => ['type' => 'bool',   'required' => true, 'allowed_mime_types' => []],
                    'input5' => ['type' => 'array',  'required' => true, 'allowed_mime_types' => []],
                    'input6' => ['type' => 'file',   'required' => true, 'allowed_mime_types' => ['csv']],
                ],
                'parameters' => [
                    'param1' => 'Foo',
                    'param2' => '{{ param1 }} Bar',
                ],
                'steps' => [
                    'first' => [
                        'class' => self::COUNT_CLASSNAME,
                        'parameters' => [
                            'string' => '{{ param2 }} first',
                            'array'  => [1],
                        ],
                    ],
                    'second' => [
                        'class' => self::COUNT_CLASSNAME,
                        'parameters' => [
                            'string' => '{{ param2 }} second',
                            'array'  => [1, 2, 3],
                        ],
                    ],
                ],
            ],
            'other' => [
                'name' => 'Other',
                'options' => [
                    'can_be_put_in_queue' => true,
                    'can_be_rerun_automatically' => true,
                ],
                'inputs' => [
                    'generic_exception' => ['type' => 'bool',   'required' => true, 'allowed_mime_types' => []],
                ],
                'parameters' => [],
                'steps' => [
                    'error' => [
                        'class' => self::ERROR_CLASSNAME,
                        'parameters' => [],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public static function getConfigurationSampleDataBuilt(): array
    {
        $workflows = static::getConfigurationSampleData();
        foreach ($workflows as $workflowCode => &$workflow) {
            $workflow['code'] = $workflowCode;
            foreach ($workflow['inputs'] as $inputCode => &$input) {
                $input['name'] = $inputCode;

                if (!array_key_exists('allowed_mime_types', $input)) {
                    $input['allowed_mime_types'] = [];
                }

                if (!array_key_exists('required', $input)) {
                    $input['required'] = true;
                }
            }

            foreach ($workflow['steps'] as $stepCode => &$step) {
                $step['code'] = $stepCode;
            }
        }

        return $workflows;
    }

    /**
     * @return StepInterface
     */
    public static function getStepCountProcessor()
    {
        $classname = static::COUNT_CLASSNAME;

        $processor = new $classname();

        return $processor;
    }

    /**
     * @return StepInterface
     */
    public static function getStepErrorProcessor()
    {
        $classname = static::ERROR_CLASSNAME;

        $processor = new $classname();

        return $processor;
    }
}

class StepCountMock implements StepInterface
{
    /**
     * @param ParametersInterface $parameters
     * @param LoggerInterface $logger
     * @return int
     */
    public function execute(ParametersInterface $parameters, LoggerInterface $logger): int
    {
        return count($parameters->get('array'));
    }
}

class StepErrorMock implements StepInterface
{
    /**
     * @param ParametersInterface $parameters
     * @param LoggerInterface $logger
     * @return int
     * @throws \Exception
     */
    public function execute(ParametersInterface $parameters, LoggerInterface $logger): int
    {
        if (!$parameters->get('input.generic_exception')) {
            throw new CallRestException('The CallRest Error !');
        }

        throw new \Exception('The Generic Error !');
    }
}
