<?php
namespace Spipu\ProcessBundle\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use Spipu\CoreBundle\Entity\Role\Item;
use Spipu\CoreBundle\Tests\Unit\Service\RoleDefinitionUiTest;
use Spipu\ProcessBundle\Service\RoleDefinition;

class RoleDefinitionTest extends TestCase
{

    public function testService()
    {
        $items = RoleDefinitionUiTest::loadRoles($this, new RoleDefinition());

        $this->assertEquals(5, count($items));

        $this->assertArrayHasKey('ROLE_ADMIN_MANAGE_PROCESS_SHOW', $items);
        $this->assertArrayHasKey('ROLE_ADMIN_MANAGE_PROCESS_DELETE', $items);
        $this->assertArrayHasKey('ROLE_ADMIN_MANAGE_PROCESS_RERUN', $items);
        $this->assertArrayHasKey('ROLE_ADMIN_MANAGE_PROCESS_KILL', $items);
        $this->assertArrayHasKey('ROLE_ADMIN_MANAGE_PROCESS', $items);

        $this->assertEquals(Item::TYPE_ROLE, $items['ROLE_ADMIN_MANAGE_PROCESS_SHOW']->getType());
        $this->assertEquals(Item::TYPE_ROLE, $items['ROLE_ADMIN_MANAGE_PROCESS_DELETE']->getType());
        $this->assertEquals(Item::TYPE_ROLE, $items['ROLE_ADMIN_MANAGE_PROCESS_RERUN']->getType());
        $this->assertEquals(Item::TYPE_ROLE, $items['ROLE_ADMIN_MANAGE_PROCESS_KILL']->getType());
        $this->assertEquals(Item::TYPE_ROLE, $items['ROLE_ADMIN_MANAGE_PROCESS']->getType());

        Item::resetAll();
    }
}
