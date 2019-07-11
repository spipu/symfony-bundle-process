<?php
declare(strict_types = 1);

namespace Spipu\ProcessBundle\Step\Database;

use Spipu\ProcessBundle\Entity\Process\ParametersInterface;
use Spipu\ProcessBundle\Service\LoggerInterface;
use Spipu\ProcessBundle\Step\StepInterface;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Connection;

class CreateTemporaryTable implements StepInterface
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * CreateTemporaryTable constructor.
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
     * @return mixed
     * @throws \Doctrine\DBAL\DBALException
     */
    public function execute(ParametersInterface $parameters, LoggerInterface $logger)
    {
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

        $schema = $this->connection->getSchemaManager();
        $schema->dropAndCreateTable($table);

        return $tablename;
    }
}
