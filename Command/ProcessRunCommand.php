<?php
declare(strict_types = 1);

namespace Spipu\ProcessBundle\Command;

use Spipu\ProcessBundle\Entity\Process\Process;
use Spipu\ProcessBundle\Exception\InputException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Spipu\ProcessBundle\Service\Manager as ProcessManager;

class ProcessRunCommand extends Command
{
    const ARGUMENT_PROCESS = 'process';
    const OPTION_INPUT = 'inputs';

    /**
     * @var ProcessManager
     */
    private $processManager;

    /**
     * RunProcess constructor.
     * @param ProcessManager $processManager
     * @param null|string $name
     */
    public function __construct(
        ProcessManager $processManager,
        ?string $name = null
    ) {
        $this->processManager = $processManager;

        parent::__construct($name);
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
            $output->writeln('Available processs:');
            $processs = $this->processManager->getConfigReader()->getProcessList();
            $list = [];
            foreach ($processs as $code => $name) {
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
     * @return void
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        // Init the new process.
        $processCode = $input->getArgument(static::ARGUMENT_PROCESS);
        $output->writeln('Execute process: '.$processCode);
        $process = $this->processManager->load($processCode);

        // Init the inputs.
        $inputs = $input->getOption(static::OPTION_INPUT);
        $this->askInputs($process, $inputs, $input, $output);

        // Execute the process.
        $result = $this->processManager->execute($process);

        // Display the result.
        $output->writeln(' => Result:');
        $output->writeln($result);
    }

    /**
     * @param Process $process
     * @param array $inputs
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return bool
     * @throws InputException
     */
    private function askInputs(Process $process, array $inputs, InputInterface $input, OutputInterface $output): bool
    {
        $definition = $process->getInputs()->getDefinition();
        if (count($definition) === 0) {
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

        $first = true;
        $symfonyStyle = null;
        foreach ($definition as $key => $type) {
            $value = '';

            if (array_key_exists($key, $values)) {
                $value = $values[$key];
            }

            if (!array_key_exists($key, $values)) {
                if ($first) {
                    $symfonyStyle = new SymfonyStyle($input, $output);
                    $symfonyStyle->note('This process needs some inputs');
                    $first = false;
                }
                $value = $symfonyStyle->ask("$key ($type)");
                if ($value === null) {
                    $value = '';
                }
            }

            $value = $this->validateInput($value, $type);
            $process->getInputs()->set($key, $value);
        }

        return true;
    }

    /**
     * @param string $value
     * @param string $type
     * @return int|string
     * @throws InputException
     */
    private function validateInput(string $value, string $type)
    {
        if ($type === 'string') {
            return (string) $value;
        }

        if ($type === 'int') {
            return (int) $value;
        }

        if ($type === 'float') {
            return (float) $value;
        }

        if ($type === 'bool') {
            return in_array(strtolower($value), ['1', 'true', 'y', 'yes']);
        }

        if ($type === 'array') {
            $value = json_decode($value, true);

            if ($value === null) {
                throw new InputException('json format must be used for array type');
            }

            return $value;
        }

        throw new InputException('The type is not authorized');
    }
}
