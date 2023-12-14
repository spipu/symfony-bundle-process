<?php

namespace Spipu\ProcessBundle\Tests\Functional\Controller;

use Spipu\ProcessBundle\Tests\Functional\AbstractFunctionalTest;
use Spipu\UiBundle\Tests\UiWebTestCaseTrait;

class InterfaceLogsTest extends AbstractFunctionalTest
{
    use UiWebTestCaseTrait;

    public function testAdmin()
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
