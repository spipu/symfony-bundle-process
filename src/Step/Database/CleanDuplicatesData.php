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
use Spipu\ProcessBundle\Exception\StepException;
use Spipu\ProcessBundle\Service\LoggerInterface;
use Spipu\ProcessBundle\Step\StepInterface;
use Doctrine\DBAL\Connection;

class CleanDuplicatesData implements StepInterface
{
    private Connection $connection;

    public function __construct(
        Connection $connection
    ) {
        $this->connection = $connection;
    }

    public function execute(ParametersInterface $parameters, LoggerInterface $logger): array
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
            $nbDuplicatedEntries = $this->connection->fetchNumeric($query)[0];
        } catch (Exception $e) {
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
            $nbPurgedLines = $this->connection->executeQuery($query);
        } catch (Exception $e) {
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
     * @param string $alias1
     * @param string $alias2
     * @return string
     */
    private function getCondition(array $fields, string $alias1, string $alias2): string
    {
        $condition = [];
        foreach ($fields as $field) {
            $condition[] = sprintf(
                '%1$s.%3$s = %2$s.%3$s',
                $alias1,
                $alias2,
                $this->connection->quoteIdentifier($field)
            );
        }
        return implode(' AND ', $condition);
    }
}
