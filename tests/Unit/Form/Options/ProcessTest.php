<?php

declare(strict_types=1);

namespace Spipu\ProcessBundle\Tests\Unit\Form\Options;

use PHPUnit\Framework\TestCase;
use Spipu\ProcessBundle\Form\Options\Process;
use Spipu\ProcessBundle\Service\ConfigReader;

class ProcessTest extends TestCase
{
    public function testOptionsAreSortedByName(): void
    {
        $configReader = $this->createMock(ConfigReader::class);
        $configReader
            ->expects($this->once())
            ->method('getProcessList')
            ->willReturn(
                [
                    'aaa' => 'Zeta process',
                    'bbb' => 'alpha process',
                    'ccc' => 'Process 10',
                    'ddd' => 'Process 2',
                    'eee' => 'Beta process',
                ]
            );

        $options = new Process($configReader);

        $this->assertSame(
            [
                'bbb' => 'alpha process',
                'eee' => 'Beta process',
                'ddd' => 'Process 2',
                'ccc' => 'Process 10',
                'aaa' => 'Zeta process',
            ],
            $options->getOptions()
        );

        $this->assertSame(
            [
                '' => ' ',
                'bbb' => 'alpha process',
                'eee' => 'Beta process',
                'ddd' => 'Process 2',
                'ccc' => 'Process 10',
                'aaa' => 'Zeta process',
            ],
            $options->getOptionsWithEmptyValue()
        );
    }
}
