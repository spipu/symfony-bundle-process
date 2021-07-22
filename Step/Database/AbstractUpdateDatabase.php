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
use Spipu\ProcessBundle\Step\StepInterface;
use Doctrine\DBAL\Connection;

abstract class AbstractUpdateDatabase implements StepInterface
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
     * ImportFileToTable constructor.
     * @param Connection $connection
     */
    public function __construct(
        Connection $connection
    ) {
        $this->connection = $connection;
    }

    /**
     * @param ParametersInterface $parameters
     * @param LoggerInterface $logger
     * @return array
     * @throws StepException
     */
    public function execute(ParametersInterface $parameters, LoggerInterface $logger)
    {
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
