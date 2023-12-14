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
    private OutputInterface $output;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

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

    private function formatMemory(int $value): string
    {
        $stringValue = number_format(((float) $value) / (1024. * 1024.), 2, '.', '');

        return str_pad($stringValue, 6, ' ', STR_PAD_LEFT) . ' Mo';
    }

    private function formatLevel(string $value): string
    {
        return str_pad($value, 7, '_', STR_PAD_RIGHT);
    }
}
