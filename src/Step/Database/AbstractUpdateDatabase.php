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

namespace Spipu\ProcessBundle\Step\Database;

use Spipu\ProcessBundle\Entity\Process\ParametersInterface;
use Spipu\ProcessBundle\Service\LoggerInterface;
use Doctrine\DBAL\Connection;

abstract class AbstractUpdateDatabase extends AbstractDatabase
{
    protected Connection $connection;
    protected array $report = [];
    protected LoggerInterface $logger;

    public function execute(ParametersInterface $parameters, LoggerInterface $logger): array
    {
        $this->connection = $this->getConnection($parameters, $logger);
        $this->logger = $logger;

        $this->report = [
            'inserted' => 0,
            'updated'  => 0,
            'deleted'  => 0,
            'disabled' => 0,
        ];

        $this->updateDatabase($parameters);

        return $this->report;
    }

    abstract protected function updateDatabase(ParametersInterface $parameters): void;
}
