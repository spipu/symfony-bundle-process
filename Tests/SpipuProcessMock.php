<?php

/**
 * This file is part of a Spipu Bundle
 *
 * (c) Laurent Minguet
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spipu\ProcessBundle\Tests;

use Closure;
use PHPUnit\Framework\MockObject\MockObject;
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
use Symfony\Component\DependencyInjection\ContainerInterface;

class SpipuProcessMock extends TestCase
{
    public const COUNT_CLASSNAME = StepCountMock::class;
    public const ERROR_CLASSNAME = StepErrorMock::class;

    /**
     * @param int|null $id
     * @return Log
     */
    public static function getLogEntity(int $id = null): Log
    {
        $entity = new Log();

        if ($id !== null) {
            $setId = Closure::bind(
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
    public static function getTaskEntity(int $id = null): Task
    {
        $entity = new Task();

        if ($id !== null) {
            $setId = Closure::bind(
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
    public static function getMainParameters(TestCase $testCase): MainParameters
    {
        return new MainParameters(
            self::getContainer($testCase),
            SpipuConfigurationMock::getManager($testCase)
        );
    }

    /**
     * @param TestCase $testCase
     * @return MockObject|ContainerInterface
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
                    'process_lock_on_failed' => true,
                    'process_lock' => [],
                    'needed_role' => null,
                    'automatic_report' => false,
                ],
                'inputs' => [
                    'input1' => ['type' => 'string', 'required' => true, 'allowed_mime_types' => [], 'regexp' => null, 'help' => null],
                    'input2' => ['type' => 'int',    'required' => true, 'allowed_mime_types' => [], 'regexp' => null, 'help' => null],
                    'input3' => ['type' => 'float',  'required' => true, 'allowed_mime_types' => [], 'regexp' => null, 'help' => null],
                    'input4' => ['type' => 'bool',   'required' => true, 'allowed_mime_types' => [], 'regexp' => null, 'help' => null],
                    'input5' => ['type' => 'array',  'required' => true, 'allowed_mime_types' => [], 'regexp' => null, 'help' => null],
                    'input6' => ['type' => 'file',   'required' => true, 'allowed_mime_types' => ['csv'], 'regexp' => null, 'help' => null],
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
                        'ignore_in_progress' => false,
                    ],
                    'second' => [
                        'class' => self::COUNT_CLASSNAME,
                        'parameters' => [
                            'string' => '{{ param2 }} second',
                            'array'  => [1, 2, 3],
                        ],
                        'ignore_in_progress' => false,
                    ],
                ],
            ],
            'other' => [
                'name' => 'Other',
                'options' => [
                    'can_be_put_in_queue' => true,
                    'can_be_rerun_automatically' => true,
                    'process_lock_on_failed' => true,
                    'process_lock' => [],
                    'needed_role' => null,
                    'automatic_report' => false,
                ],
                'inputs' => [
                    'generic_exception' => ['type' => 'bool',   'required' => true, 'allowed_mime_types' => [], 'regexp' => null, 'help' => null],
                ],
                'parameters' => [],
                'steps' => [
                    'error' => [
                        'class' => self::ERROR_CLASSNAME,
                        'parameters' => [],
                        'ignore_in_progress' => false,
                    ],
                ],
            ],
            'lock' => [
                'name' => 'Lock',
                'options' => [
                    'can_be_put_in_queue' => true,
                    'can_be_rerun_automatically' => true,
                    'process_lock_on_failed' => true,
                    'process_lock' => [
                        'lock',
                        'other',
                    ],
                    'needed_role' => null,
                    'automatic_report' => false,
                ],
                'inputs' => [],
                'parameters' => [],
                'steps' => [
                    'count' => [
                        'class' => self::COUNT_CLASSNAME,
                        'parameters' => [
                            'array'  => [1, 2, 3]
                        ],
                        'ignore_in_progress' => false,
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
                if (!array_key_exists('regexp', $input)) {
                    $input['regexp'] = null;
                }
                if (!array_key_exists('help', $input)) {
                    $input['help'] = null;
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
    public static function getStepCountProcessor(): StepInterface
    {
        $classname = static::COUNT_CLASSNAME;

        $processor = new $classname();

        return $processor;
    }

    /**
     * @return StepInterface
     */
    public static function getStepErrorProcessor(): StepInterface
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
