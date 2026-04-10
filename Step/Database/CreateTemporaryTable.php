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

use Doctrine\DBAL\Exception;
use Spipu\ProcessBundle\Entity\Process\ParametersInterface;
use Spipu\ProcessBundle\Service\LoggerInterface;
use Doctrine\DBAL\Schema\Table;

class CreateTemporaryTable extends AbstractDatabase
{
    /**
     * @param ParametersInterface $parameters
     * @param LoggerInterface $logger
     * @return mixed
     * @throws Exception
     */
    public function execute(ParametersInterface $parameters, LoggerInterface $logger)
    {
        $connection = $this->getConnection($parameters, $logger);

        $tablename = $parameters->get('tablename');
        $fields = $parameters->get('fields');

        $logger->debug(sprintf('Table to create: [%s] with [%d] fields', $tablename, count($fields)));

        $table = new Table($tablename);
        $table->addColumn('id', 'bigint', ['notnull' => true, 'autoincrement' => true]);
        $table->addColumn('row_id', 'bigint', ['notnull' => false]);
        foreach ($fields as $name => $definition) {
            $type = $definition['type'];
            $options = [];
            if (array_key_exists('options', $definition)) {
                $options = $definition['options'];
            }
            $table->addColumn($name, $type, $options);
        }
        $table->setPrimaryKey(array('id'));
        $table->addUniqueIndex(['row_id']);

        $schema = $connection->createSchemaManager();
        try {
            $schema->dropTable($tablename);
        } catch (Exception $e) {
            // Nothing here, if the table does not exist yet, it is not a pb.
        }
        $schema->createTable($table);

        return $tablename;
    }
}
