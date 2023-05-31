<?php
namespace Spipu\ProcessBundle\Tests\Unit\Entity\Process;

use PHPUnit\Framework\TestCase;
use Spipu\ProcessBundle\Entity\Process\Report;

class ReportTest extends TestCase
{
    public function testOk()
    {
        $report = new Report('foo@bar.fr');
        $this->assertSame('foo@bar.fr', $report->getEmail());
        $this->assertSame(0, $report->getNbSteps());
        $this->assertSame([], $this->convertReportToArray($report));

        $report->addMessage('foo message');
        $this->assertSame(1, $report->getNbSteps());
        $this->assertSame(
            [
                ['level' => 'message', 'message' => 'foo message', 'link' => null,],
            ],
            $this->convertReportToArray($report)
        );

        $report->addWarning('foo warning');
        $this->assertSame(2, $report->getNbSteps());
        $this->assertSame(
            [
                ['level' => 'message', 'message' => 'foo message', 'link' => null,],
                ['level' => 'warning', 'message' => 'foo warning', 'link' => null,],
            ],
            $this->convertReportToArray($report)
        );

        $report->addError('foo error');
        $this->assertSame(3, $report->getNbSteps());
        $this->assertSame(
            [
                ['level' => 'message', 'message' => 'foo message', 'link' => null,],
                ['level' => 'warning', 'message' => 'foo warning', 'link' => null,],
                ['level' => 'error',   'message' => 'foo error',   'link' => null,],
            ],
            $this->convertReportToArray($report)
        );

        $report->addMessage('bar message');
        $this->assertSame(4, $report->getNbSteps());
        $this->assertSame(
            [
                ['level' => 'message', 'message' => 'foo message', 'link' => null,],
                ['level' => 'warning', 'message' => 'foo warning', 'link' => null,],
                ['level' => 'error',   'message' => 'foo error',   'link' => null,],
                ['level' => 'message', 'message' => 'bar message', 'link' => null,],
            ],
            $this->convertReportToArray($report)
        );
    }

    /**
     * @param Report $report
     * @return array
     */
    private function convertReportToArray(Report $report): array
    {
        $result = [];
        foreach ($report->getSteps() as $step) {
            $result[] = [
                'level'   => $step->getLevel(),
                'message' => $step->getMessage(),
                'link'    => $step->getLink(),
            ];
        }

        return $result;
    }
}