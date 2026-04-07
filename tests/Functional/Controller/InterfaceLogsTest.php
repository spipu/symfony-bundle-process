<?php

declare(strict_types=1);

namespace Spipu\ProcessBundle\Tests\Functional\Controller;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use Spipu\ProcessBundle\Controller\LogController;
use Spipu\ProcessBundle\Tests\Functional\AbstractFunctionalTestCase;
use Spipu\UiBundle\Tests\UiWebTestCaseTrait;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(LogController::class)]
class InterfaceLogsTest extends AbstractFunctionalTestCase
{
    use UiWebTestCaseTrait;

    public function testAdmin(): void
    {
        $client = static::createClient();

        $this->adminLogin($client, 'Process Logs');

        // Tasks List
        $crawler = $client->clickLink('Process Logs');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $gridProperties = $this->getGridProperties($crawler, 'process_log');
        $this->assertSame(0, $gridProperties['count']['nb']);

        $expectedColumns = [
            'id',
            'code',
            'status',
            'progress',
            'created_at',
            'updated_at',
        ];
        $this->assertSame($expectedColumns, array_keys($gridProperties['columns']));
    }
}
