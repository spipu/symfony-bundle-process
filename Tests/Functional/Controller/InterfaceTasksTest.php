<?php

namespace Spipu\ProcessBundle\Tests\Functional\Controller;

use Spipu\ProcessBundle\Tests\Functional\AbstractFunctionalTest;
use Spipu\UiBundle\Tests\UiWebTestCaseTrait;

class InterfaceTasksTest extends AbstractFunctionalTest
{
    use UiWebTestCaseTrait;

    public function testAdmin()
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
