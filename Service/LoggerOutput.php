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
                '[%s][%s][%s][%s] %s',
                date('Y-m-d H:i:s', $message['date']),
                $this->formatMemory((int) $message['memory']),
                $this->formatMemory((int) $message['memory_peak']),
                $this->formatLevel($message['level']),
                $message['message']
            )
        );
    }

    /**
     * @param int $value
     * @return string
     */
    private function formatMemory(int $value): string
    {
        $stringValue = number_format(((float) $value) / (1024. * 1024.), 2, '.', '');

        return str_pad($stringValue, 6, ' ', STR_PAD_LEFT) . ' Mo';
    }

    /**
     * @param string $value
     * @return string
     */
    private function formatLevel(string $value): string
    {
        return str_pad($value, 7, '_', STR_PAD_RIGHT);
    }
}
