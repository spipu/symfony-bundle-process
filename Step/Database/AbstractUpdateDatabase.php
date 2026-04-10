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
use Spipu\ProcessBundle\Exception\StepException;
use Spipu\ProcessBundle\Service\LoggerInterface;
use Doctrine\DBAL\Connection;

abstract class AbstractUpdateDatabase extends AbstractDatabase
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var array
     */
    protected $report = [];

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param ParametersInterface $parameters
     * @param LoggerInterface $logger
     * @return array
     * @throws StepException
     */
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

    /**
     * @param ParametersInterface $parameters
     * @return void
     * @throws StepException
     */
    abstract protected function updateDatabase(ParametersInterface $parameters): void;
}
