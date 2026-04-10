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

use Doctrine\DBAL\Connection;
use Spipu\ProcessBundle\Entity\Process\ParametersInterface;
use Spipu\ProcessBundle\Service\ConnectionManagerInterface;
use Spipu\ProcessBundle\Service\LoggerInterface;
use Spipu\ProcessBundle\Step\StepInterface;

abstract class AbstractDatabase implements StepInterface
{
    /**
     * @var ConnectionManagerInterface
     */
    private ConnectionManagerInterface $connectionManager;

    /**
     * AbstractDatabase constructor.
     * @param ConnectionManagerInterface $connectionManager
     */
    public function __construct(
        ConnectionManagerInterface $connectionManager
    ) {
        $this->connectionManager = $connectionManager;
    }

    /**
     * @param ParametersInterface $parameters
     * @param LoggerInterface $logger
     * @return Connection
     */
    protected function getConnection(ParametersInterface $parameters, LoggerInterface $logger): Connection
    {
        $parameters->setDefaultValue('connection', 'default');
        $connectionCode = $parameters->get('connection');
        $logger->debug(sprintf('Connection: [%s]', $connectionCode));

        return $this->connectionManager->getConnection($connectionCode);
    }
}
