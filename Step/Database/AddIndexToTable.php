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

/**
 * Class AddIndexToTable
 *
 * @package Spipu\ProcessBundle\Step\Generic
 */
class AddIndexToTable extends AbstractDatabase
{
    /**
     * @param ParametersInterface $parameters
     * @param LoggerInterface $logger
     * @return bool
     * @throws Exception
     */
    public function execute(ParametersInterface $parameters, LoggerInterface $logger): bool
    {
        $connection = $this->getConnection($parameters, $logger);

        $tablename = $parameters->get('tablename');
        $logger->debug(sprintf('Table: [%s]', $tablename));

        $fields = $parameters->get('fields');
        $logger->debug(sprintf('Fields: [%s]', implode(', ', $fields)));

        // Build the Index name.
        $indexName = md5($tablename . '_' . implode('_', $fields));

        // Look at if the index already exists.
        $schema = $connection->createSchemaManager();
        $list = $schema->listTableIndexes($tablename);
        if (array_key_exists($indexName, $list)) {
            $logger->warning(' => The index already exists');
            return false;
        }

        // Protect fields.
        foreach ($fields as &$field) {
            $field = $connection->quoteIdentifier($field);
        }

        // Create Index.
        $query = sprintf(
            'CREATE INDEX %1$s ON %2$s (%3$s)',
            $connection->quoteIdentifier($indexName),
            $connection->quoteIdentifier($tablename),
            implode(', ', $fields)
        );
        try {
            $connection->executeQuery($query);
        } catch (Exception $e) {
            $logger->error(' => Error with the following query');
            $logger->error($query);
            throw $e;
        }
        $logger->debug(' => The index has been created');

        return true;
    }
}
