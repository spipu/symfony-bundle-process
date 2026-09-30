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
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Index\IndexType;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use Spipu\ProcessBundle\Entity\Process\ParametersInterface;
use Spipu\ProcessBundle\Service\LoggerInterface;

class CreateTemporaryTable extends AbstractDatabase
{
    /**
     * @param ParametersInterface $parameters
     * @param LoggerInterface $logger
     * @return mixed
     * @throws Exception
     */
    public function execute(ParametersInterface $parameters, LoggerInterface $logger): string
    {
        $connection = $this->getConnection($parameters, $logger);

        $tablename = (string) $parameters->get('tablename');
        $fields = $parameters->get('fields');

        $parameters->setDefaultValue('charset', 'utf8mb4');
        $charset = $parameters->get('charset');

        $parameters->setDefaultValue('collation', 'utf8mb4_unicode_ci');
        $collation = $parameters->get('collation');

        $logger->debug(sprintf('Table to create: [%s] with [%d] fields', $tablename, count($fields)));

        $columns = [
            Column::editor()
                ->setUnquotedName('id')
                ->setTypeName(Types::BIGINT)
                ->setNotNull(true)
                ->setAutoincrement(true)
                ->create(),
            Column::editor()
                ->setUnquotedName('row_id')
                ->setTypeName(Types::BIGINT)
                ->setNotNull(false)
                ->create(),
        ];

        foreach ($fields as $name => $definition) {
            $type = $definition['type'];
            $options = [];
            if (array_key_exists('options', $definition)) {
                $options = $definition['options'];
            }
            $columns[] = new Column($name, $type, $options);
        }

        $qualifier = null;
        $unqualifiedName = $tablename;
        if (str_contains($tablename, '.')) {
            [$qualifier, $unqualifiedName] = explode('.', $tablename, 2);
        }

        $table = Table::editor()
            ->setUnquotedName($unqualifiedName, $qualifier)
            ->setColumns(...$columns)
            ->setPrimaryKeyConstraint(PrimaryKeyConstraint::editor()->setUnquotedColumnNames('id')->create())
            ->setIndexes(Index::editor()->setType(IndexType::UNIQUE)->setUnquotedColumnNames('row_id'))
            ->setOptions(['charset' => $charset, 'collation' => $collation])
            ->create();

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
