<?php
declare(strict_types = 1);

namespace Spipu\ProcessBundle\Step\Database;

use Spipu\ProcessBundle\Entity\Process\ParametersInterface;
use Spipu\ProcessBundle\Exception\StepException;
use Spipu\ProcessBundle\Service\LoggerInterface;
use Spipu\ProcessBundle\Step\StepInterface;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Connection;

/**
 * Class CleanDuplicatesData
 *
 * @package Spipu\ProcessBundle\Step\Generic
 */
class CleanDuplicatesData implements StepInterface
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
     * @throws StepException|DBALException
     */
    public function execute(ParametersInterface $parameters, LoggerInterface $logger)
    {
        $tablename = $parameters->get('tablename');
        $fields = $parameters->get('fields');

        // Part 1 - identify Duplicates rows.
        $logger->debug(
            sprintf(
                'Count row with duplicate values on [%1$s] in [%2$s] table',
                implode(', ', $fields),
                $tablename
            )
        );

        $protectedFields = [];
        foreach ($fields as $field) {
            $protectedFields[] = $this->connection->quoteIdentifier($field);
        }

        $query = sprintf(
            '
                SELECT count(*)
                FROM (
                        SELECT %2$s
                        FROM %1$s
                        GROUP BY %2$s
                        HAVING count(*) > 1
                ) as a
            ',
            $this->connection->quoteIdentifier($tablename),
            implode(',', $protectedFields)
        );

        try {
            $nbDuplicatedEntries = $this->connection->fetchArray($query)[0];
        } catch (\Exception $e) {
            throw new StepException($e->getMessage());
        }

        $logger->debug(
            sprintf(
                '  => Found [%1$d] duplicate rows',
                $nbDuplicatedEntries
            )
        );

        // Part 2 : delete duplicate rows.
        $logger->debug(
            sprintf(
                'Delete rows with duplicate values on [%1$s] in [%2$s] table',
                implode(',', $fields),
                $tablename
            )
        );

        $query = sprintf(
            '
                DELETE t1
                FROM %1$s t1
                JOIN %1$s t2
                ON t2.id < t1.id
                AND %2$s
            ',
            $this->connection->quoteIdentifier($tablename),
            $this->getCondition($fields, 't1', 't2')
        );

        try {
            $nbPurgedLines = $this->connection->exec($query);
        } catch (\Exception $e) {
            throw new StepException($e->getMessage());
        }

        $logger->debug(
            sprintf(
                '  => Purged [%1$d] duplicate rows',
                $nbPurgedLines
            )
        );

        return [
            'duplicated' => $nbDuplicatedEntries,
            'purged'     => $nbPurgedLines,
        ];
    }

    /**
     * @param string[] $fields
     * @param string $idx1
     * @param string $idx2
     * @return string
     */
    private function getCondition(array $fields, string $idx1, string $idx2)
    {
        $condition = array();
        foreach ($fields as $field) {
            /**@var array condition*/
            $condition[] = sprintf(
                '%1$s.%3$s = %2$s.%3$s',
                $idx1,
                $idx2,
                $this->connection->quoteIdentifier($field)
            );
        }
        return implode(' AND ', $condition);
    }
}
