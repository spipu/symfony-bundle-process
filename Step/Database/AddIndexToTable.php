<?php
declare(strict_types=1);

namespace Spipu\ProcessBundle\Step\Database;

use Spipu\ProcessBundle\Entity\Process\ParametersInterface;
use Spipu\ProcessBundle\Service\LoggerInterface;
use Spipu\ProcessBundle\Step\StepInterface;
use Doctrine\DBAL\Connection;

/**
 * Class AddIndexToTable
 *
 * @package Spipu\ProcessBundle\Step\Generic
 */
class AddIndexToTable implements StepInterface
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
     * @return bool
     * @throws \Exception
     */
    public function execute(ParametersInterface $parameters, LoggerInterface $logger)
    {
        $tablename = $parameters->get('tablename');
        $logger->debug(sprintf('Table: [%s]', $tablename));

        $fields = $parameters->get('fields');
        $logger->debug(sprintf('Fields: [%s]', implode(', ', $fields)));

        // Build the Index name.
        $indexName = md5($tablename.'_'.implode('_', $fields));

        // Look at if the index already exists.
        $schema = $this->connection->getSchemaManager();
        $list = $schema->listTableIndexes($tablename);
        if (array_key_exists($indexName, $list)) {
            $logger->warning(' => The index already exists');
            return false;
        }

        // Protect fields.
        foreach ($fields as &$field) {
            $field = $this->connection->quoteIdentifier($field);
        }

        // Create Index.
        $query = sprintf(
            'CREATE INDEX %1$s ON %2$s (%3$s)',
            $this->connection->quoteIdentifier($indexName),
            $this->connection->quoteIdentifier($tablename),
            implode(', ', $fields)
        );
        try {
            $this->connection->getWrappedConnection()->exec($query);
        } catch (\Exception $e) {
            $logger->error(' => Error with the following query');
            $logger->error($query);
            throw $e;
        }
        $logger->debug(' => The index has been created');

        return true;
    }
}
