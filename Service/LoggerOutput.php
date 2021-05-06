<?php
declare(strict_types = 1);

namespace Spipu\ProcessBundle\Service;

use Symfony\Component\Console\Output\OutputInterface;

class LoggerOutput implements LoggerOutputInterface
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * LoggerOutput constructor.
     * @param OutputInterface $output
     */
    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * @param array $message
     * @return void
     */
    public function write(array $message): void
    {
        $this->output->writeln(
            sprintf(
                '[%s][%s Mo][%s Mo][%s] %s',
                date('Y-m-d H:i:s', $message['date']),
                number_format($message['memory'] / (1024 * 1024), 2, '.', ''),
                number_format($message['memory_peak'] / (1024 * 1024), 2, '.', ''),
                $message['level'],
                $message['message']
            )
        );
    }
}
