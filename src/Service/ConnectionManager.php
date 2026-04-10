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

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;

class ConnectionManager implements ConnectionManagerInterface
{
    /**
     * @var ManagerRegistry
     */
    private ManagerRegistry $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(
        ManagerRegistry $registry
    ) {
        $this->registry = $registry;
    }

    /**
     * @param string $name
     * @return Connection
     */
    public function getConnection(string $name = 'default'): Connection
    {
        /** @var Connection $connection */
        $connection = $this->registry->getConnection($name);

        return $connection;
    }
}
