<?php
declare(strict_types = 1);

namespace Spipu\ProcessBundle\Service;

use Spipu\CoreBundle\Entity\Role\Item;
use Spipu\CoreBundle\Service\RoleDefinitionInterface;

class RoleDefinition implements RoleDefinitionInterface
{
    /**
     * @return void
     */
    public function buildDefinition(): void
    {
        Item::load('ROLE_ADMIN_MANAGE_PROCESS_SHOW')
            ->setLabel('spipu.process.role.admin_show')
            ->setWeight(10)
            ->addChild('ROLE_ADMIN');

        Item::load('ROLE_ADMIN_MANAGE_PROCESS_DELETE')
            ->setLabel('spipu.process.role.admin_delete')
            ->setWeight(20)
            ->addChild('ROLE_ADMIN');

        Item::load('ROLE_ADMIN_MANAGE_PROCESS_RERUN')
            ->setLabel('spipu.process.role.admin_rerun')
            ->setWeight(30)
            ->addChild('ROLE_ADMIN');

        Item::load('ROLE_ADMIN_MANAGE_PROCESS_KILL')
            ->setLabel('spipu.process.role.admin_kill')
            ->setWeight(40)
            ->addChild('ROLE_ADMIN');

        Item::load('ROLE_ADMIN_MANAGE_PROCESS')
            ->setLabel('spipu.process.role.admin')
            ->setWeight(160)
            ->addChild('ROLE_ADMIN_MANAGE_PROCESS_SHOW')
            ->addChild('ROLE_ADMIN_MANAGE_PROCESS_RERUN')
            ->addChild('ROLE_ADMIN_MANAGE_PROCESS_DELETE')
            ->addChild('ROLE_ADMIN_MANAGE_PROCESS_KILL');

        Item::load('ROLE_SUPER_ADMIN')
            ->addChild('ROLE_ADMIN_MANAGE_PROCESS');
    }
}
