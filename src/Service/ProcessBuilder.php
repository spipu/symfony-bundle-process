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

use Spipu\ProcessBundle\Entity\Process;
use Spipu\ProcessBundle\Entity\Task;
use Spipu\ProcessBundle\Exception\ProcessException;

/**
 * @SuppressWarnings(PMD.CouplingBetweenObjects)
 * @SuppressWarnings(PMD.ExcessiveClassComplexity)
 */
class ProcessBuilder
{
    private ConfigReader $configReader;
    private MainParameters $mainParameters;
    private InputsFactory $inputsFactory;

    public function __construct(
        ConfigReader $configReader,
        MainParameters $mainParameters,
        InputsFactory $inputsFactory
    ) {
        $this->configReader = $configReader;
        $this->mainParameters = $mainParameters;
        $this->inputsFactory = $inputsFactory;
    }

    public function buildProcess(string $code): Process\Process
    {
        $processDefinition = $this->configReader->getProcessDefinition($code);

        $processOptions = $this->buildOptions($processDefinition['options']);
        $processInputs = $this->buildInputs($processDefinition['inputs']);

        $processParameters = $this->buildParameters($processDefinition['parameters']);
        $processParameters->setParentParameters($this->mainParameters);

        $process = new Process\Process(
            $processDefinition['code'],
            $processDefinition['name'],
            $processOptions,
            $processInputs,
            $processParameters,
            $this->buildSteps($processDefinition)
        );

        if ($process->getOptions()->canBePutInQueue()) {
            $task = $this->buildEmptyTask($process->getCode());

            $process->setTask($task);
        }

        return $process;
    }

    /**
     * @param array $processDefinition
     * @return array<string, Process\Step>
     */
    public function buildSteps(array $processDefinition): array
    {
        if (!array_key_exists('steps', $processDefinition)) {
            throw new ProcessException('The steps definition is missing');
        }

        $steps = [];
        foreach ($processDefinition['steps'] as $stepKey => $stepDefinition) {
            if (!array_key_exists('code', $stepDefinition) && is_string($stepKey)) {
                $stepDefinition['code'] = $stepKey;
            }

            $step = $this->buildStep($stepDefinition);
            $steps[$step->getCode()] = $step;
        }

        return $steps;
    }

    public function buildStep(array $stepDefinition): Process\Step
    {
        if (!array_key_exists('code', $stepDefinition)) {
            throw new ProcessException('The step definition code is missing');
        }

        if (!array_key_exists('class', $stepDefinition)) {
            throw new ProcessException('The step definition class is missing');
        }

        if (array_key_exists('parameters', $stepDefinition) && !is_array($stepDefinition['parameters'])) {
            throw new ProcessException('The step definition parameters must be an array');
        }

        return new Process\Step(
            (string) $stepDefinition['code'],
            $this->configReader->getStepClassFromClassname((string) $stepDefinition['class']),
            $this->buildParameters($stepDefinition['parameters'] ?? []),
            (bool) ($stepDefinition['ignore_in_progress'] ?? false)
        );
    }

    private function buildParameters(array $parametersDefinition): Process\Parameters
    {
        return new Process\Parameters($parametersDefinition);
    }

    private function buildInputs(array $inputsDefinition): Process\Inputs
    {
        return $this->inputsFactory->create($inputsDefinition);
    }

    private function buildOptions(array $optionsDefinition): Process\Options
    {
        return new Process\Options($optionsDefinition);
    }

    private function buildEmptyTask(string $processCode): Task
    {
        $task = new Task();

        $task
            ->setCode($processCode)
            ->setInputs("[]")
            ->setStatus(Status::CREATED)
            ->setTryNumber(0)
            ->setTryLastAt(null)
            ->setScheduledAt(null)
            ->setExecutedAt(null);

        return $task;
    }
}
