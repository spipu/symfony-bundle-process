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

namespace Spipu\ProcessBundle\Command;

use Exception;
use Spipu\ProcessBundle\Entity\Process\Process;
use Spipu\ProcessBundle\Exception\InputException;
use Spipu\ProcessBundle\Exception\ProcessException;
use Spipu\ProcessBundle\Service\LoggerOutput;
use Spipu\ProcessBundle\Service\ModuleConfiguration;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Spipu\ProcessBundle\Service\Manager as ProcessManager;

/**
 * @SuppressWarnings(PMD.CouplingBetweenObjects)
 */
class ProcessRunCommand extends Command
{
    public const ARGUMENT_PROCESS = 'process';
    public const OPTION_INPUT = 'inputs';
    public const OPTION_DEBUG = 'debug';

    /**
     * @var ProcessManager
     */
    private $processManager;

    /**
     * @var ModuleConfiguration
     */
    private $processConfiguration;

    /**
     * @var SymfonyStyle
     */
    private $symfonyStyle = null;

    /**
     * RunProcess constructor.
     * @param ProcessManager $processManager
     * @param ModuleConfiguration $processConfiguration
     * @param null|string $name
     */
    public function __construct(
        ProcessManager $processManager,
        ModuleConfiguration $processConfiguration,
        ?string $name = null
    ) {
        parent::__construct($name);

        $this->processManager = $processManager;
        $this->processConfiguration = $processConfiguration;
    }

    /**
     * Configure the command
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName('spipu:process:run')
            ->setDescription('Run a new process.')
            ->setHelp('This command allows you to run any process')
            ->addArgument(
                static::ARGUMENT_PROCESS,
                InputArgument::REQUIRED,
                'The code of the process to execute'
            )
            ->addOption(
                static::OPTION_INPUT,
                'i',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
                'Inputs of the process (if needed)'
            )
            ->addOption(
                static::OPTION_DEBUG,
                'd',
                InputOption::VALUE_NONE,
                'Display the logs on console'
            );
    }

    /**
     * Ask for missing arguments and options
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        if (!$input->getArgument(static::ARGUMENT_PROCESS)) {
            $output->writeln('');
            $output->writeln('Available process:');
            $process = $this->processManager->getConfigReader()->getProcessList();
            $list = [];
            foreach ($process as $code => $name) {
                $list[] = ['code' => $code, 'name' => $name];
            }
            $table = new Table($output);
            $table
                ->setHeaders(array_keys($list[0]))
                ->setRows($list);
            $table->render();
        }
    }

    /**
     * Execute the command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->processConfiguration->hasTaskCanExecute()) {
            throw new ProcessException('Execution is disabled in module configuration');
        }

        // Init the new process.
        $processCode = $input->getArgument(static::ARGUMENT_PROCESS);
        $output->writeln('Execute process: ' . $processCode);
        $process = $this->processManager->load($processCode);

        // Init the inputs.
        $inputs = $input->getOption(static::OPTION_INPUT);
        $this->askInputs($process, $inputs, $input, $output);

        // Debug mode or not.
        $loggerOutput = null;
        if ($input->getOption(static::OPTION_DEBUG)) {
            $output->writeln('Enable Debug Output');
            $loggerOutput = new LoggerOutput($output);
        }

        // Execute the process.
        $this->processManager->setLoggerOutput($loggerOutput);
        $result = $this->processManager->execute($process);

        // Display the result.
        $output->writeln(' => Result:');
        $output->writeln($result);

        return self::SUCCESS;
    }

    /**
     * @param Process $process
     * @param array $inputs
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return bool
     * @throws InputException
     * @SuppressWarnings(PMD.CyclomaticComplexity)
     */
    private function askInputs(Process $process, array $inputs, InputInterface $input, OutputInterface $output): bool
    {
        $inputObjects = $process->getInputs()->getInputs();
        if (count($inputObjects) === 0) {
            return false;
        }

        $values = [];
        foreach ($inputs as $value) {
            $value = explode(':', $value, 2);
            if (count($value) !== 2) {
                throw new InputException('The inputs format is invalid. It must be --inputs key:value');
            }
            $values[$value[0]] = $value[1];
        }

        foreach ($inputObjects as $inputObject) {
            $key = $inputObject->getName();
            $type = $inputObject->getType();
            $value = '';

            if (array_key_exists($key, $values)) {
                $value = $values[$key];
            }

            if (!array_key_exists($key, $values)) {
                $title = "$key ($type) " . ($inputObject->isRequired() ? 'required' : 'optional');
                $value = $this->getSymfonyStyle($input, $output)->ask($title);
                if ($value === null) {
                    $value = '';
                }
            }

            if ($inputObject->isRequired() && $value !== '') {
                $value = $this->validateInput($value, $type);
            }
            $process->getInputs()->set($key, $value);
        }

        return true;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return SymfonyStyle
     */
    private function getSymfonyStyle(InputInterface $input, OutputInterface $output): SymfonyStyle
    {
        if ($this->symfonyStyle === null) {
            $this->symfonyStyle = new SymfonyStyle($input, $output);
            $this->symfonyStyle->note('This process needs some inputs');
        }

        return $this->symfonyStyle;
    }

    /**
     * @param string $value
     * @param string $type
     * @return int|string
     * @throws InputException
     * @SuppressWarnings(PMD.CyclomaticComplexity)
     */
    private function validateInput(string $value, string $type)
    {
        switch ($type) {
            case 'string':
                return $value;

            case 'file':
                if (!is_file($value) || !is_readable($value)) {
                    throw new InputException('This is not a existing or readable file');
                }
                return $value;

            case 'int':
                return (int) $value;

            case 'float':
                return (float) $value;

            case 'bool':
                return in_array(strtolower($value), ['1', 'true', 'y', 'yes']);

            case 'array':
                $value = json_decode($value, true);
                if ($value === null) {
                    throw new InputException('json format must be used for array type');
                }
                return $value;
        }

        throw new InputException('The type is not authorized');
    }
}
