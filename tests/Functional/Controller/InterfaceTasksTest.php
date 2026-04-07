<?php

declare(strict_types=1);

namespace Spipu\ProcessBundle\Tests\Functional\Controller;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use Spipu\ProcessBundle\Controller\TaskController;
use Spipu\ProcessBundle\Tests\Functional\AbstractFunctionalTestCase;
use Spipu\UiBundle\Tests\UiWebTestCaseTrait;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(TaskController::class)]
class InterfaceTasksTest extends AbstractFunctionalTestCase
{
    use UiWebTestCaseTrait;

    public function testAdmin(): void
    {
        $client = static::createClient();

        $this->adminLogin($client, 'Process Tasks');

        // Tasks List
        $crawler = $client->clickLink('Process Tasks');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $gridProperties = $this->getGridProperties($crawler, 'process_task');
        $this->assertSame(0, $gridProperties['count']['nb']);

        $expectedColumns = [
            'id',
            'code',
            'status',
            'progress',
            'try_last_at',
            'try_number',
            'can_be_run_automatically',
            'scheduled_at',
            'executed_at',
            'updated_at',
        ];
        $this->assertSame($expectedColumns, array_keys($gridProperties['columns']));
    }
}
