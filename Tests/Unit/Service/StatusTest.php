<?php
namespace Spipu\ProcessBundle\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use Spipu\ProcessBundle\Service\Status;

class StatusTest extends TestCase
{
    /**
     * @param TestCase $testCase
     * @return Status
     */
    static public function getService(TestCase $testCase)
    {
        $service = new Status();

        return $service;
    }

    public function testService()
    {
        $service = self::getService($this);

        $this->assertSame(
            [
                Status::CREATED,
                Status::RUNNING,
                Status::FINISHED,
                Status::FAILED,
            ],
            $service->getStatuses()
        );

        $this->assertSame(
            [
                Status::CREATED,
                Status::FAILED,
            ],
            $service->getExecutableStatuses()
        );

        $this->assertSame(
            Status::CREATED,
            $service->getCreatedStatus()
        );

        $this->assertSame(
            Status::RUNNING,
            $service->getRunningStatus()
        );

        $this->assertSame(
            Status::FINISHED,
            $service->getFinishedStatus()
        );

        $this->assertSame(
            Status::FAILED,
            $service->getFailedStatus()
        );

        $this->assertSame(false, $service->canKill(Status::CREATED));
        $this->assertSame(true,  $service->canKill(Status::RUNNING));
        $this->assertSame(false, $service->canKill(Status::FINISHED));
        $this->assertSame(false, $service->canKill(Status::FAILED));

        $this->assertSame(true,  $service->canRerun(Status::CREATED));
        $this->assertSame(false, $service->canRerun(Status::RUNNING));
        $this->assertSame(false, $service->canRerun(Status::FINISHED));
        $this->assertSame(true,  $service->canRerun(Status::FAILED));
    }
}
