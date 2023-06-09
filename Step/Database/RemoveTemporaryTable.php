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

use Exception;
use Spipu\ProcessBundle\Entity\Process\ParametersInterface;
use Spipu\ProcessBundle\Service\LoggerInterface;
use Spipu\ProcessBundle\Step\StepInterface;
use Doctrine\DBAL\Connection;

class RemoveTemporaryTable implements StepInterface
{
    private Connection $connection;

    public function __construct(
        Connection $connection
    ) {
        $this->connection = $connection;
    }

    public function execute(ParametersInterface $parameters, LoggerInterface $logger): string
    {
        $parameters->setDefaultValue('if_exists', false);
        $ifExists = (bool) $parameters->get('if_exists');

        $tablename = (string) $parameters->get('tablename');

        $logger->debug(sprintf('Table to delete: [%s]', $tablename));

        try {
            $schema = $this->connection->createSchemaManager();
            $schema->dropTable($tablename);
        } catch (Exception $e) {
            if (!$ifExists) {
                throw $e;
            }
            $logger->debug($e->getMessage());
        }

        return $tablename;
    }
}
