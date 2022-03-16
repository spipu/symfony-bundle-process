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

namespace Spipu\ProcessBundle\Step\File\RowReader;

use Doctrine\DBAL\Connection;
use Spipu\ProcessBundle\Exception\RowReaderException;

abstract class AbstractRowReader implements RowReaderInterface
{
    /**
     * @var array
     */
    protected $fields = [];

    /**
     * @var int
     */
    protected $currentReadLine = 0;

    /**
     * @var int
     */
    protected $currentAcceptedLine = 0;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var ActionList
     */
    protected $actionList;

    /**
     * @var array
     */
    protected $globalActions;

    /**
     * @var array
     */
    protected $parameters;

    /**
     * FixedWidth constructor.
     * @param Connection $connection
     * @param ActionList $actionList
     */
    public function __construct(
        Connection $connection,
        ActionList $actionList
    ) {
        $this->connection = $connection;
        $this->actionList = $actionList;
    }

    /**
     * @return void
     */
    public function init(): void
    {
        $this->parameters = [];
        $this->fields = [];
        $this->globalActions = [];
        $this->currentReadLine = 0;
        $this->currentAcceptedLine = 0;
    }

    /**
     * Set the parameters
     * @param array $parameters
     * @return void
     * @throws RowReaderException
     */
    public function setParameters(array $parameters): void
    {
        $this->validateParameters($parameters);

        $this->parameters = $parameters;
    }

    /**
     * Validate the parameters
     * @param array $parameters
     * @return bool
     * @throws RowReaderException
     * @SuppressWarnings(PMD.UnusedFormalParameter)
     */
    protected function validateParameters(array $parameters): bool
    {
        throw new RowReaderException('This row reader does not accept parameters');

        return false;
    }

    /**
     * @param array $definitions
     * @return void
     * @throws RowReaderException
     */
    public function setFields(array $definitions): void
    {
        foreach ($definitions as $code => $definition) {
            $this->addField($code, $definition);
        }
    }

    /**
     * @param array $actions
     * @return void
     */
    public function setGlobalActions(array $actions): void
    {
        $this->globalActions = $actions;

        $this->prepareActions($this->globalActions);
    }


    /**
     * @param string $code
     * @param array $definition
     * @return void
     * @throws RowReaderException
     */
    private function addField(string $code, array $definition): void
    {
        if (array_key_exists($code, $this->fields)) {
            throw new RowReaderException('The asked field code is already defined');
        }

        $field = $this->prepareField($definition);

        $field['required']  = (array_key_exists('required', $definition) ? (bool) $definition['required'] : false);
        $field['condition'] = (array_key_exists('condition', $definition) ? $definition['condition'] : null);
        $field['import']    = (array_key_exists('import', $definition) ? (bool) $definition['import'] : false);
        $field['actions']   = (array_key_exists('actions', $definition) ? (array) $definition['actions'] : []);
        $field['mapping']   = (array_key_exists('mapping', $definition) ? (array) $definition['mapping'] : null);

        if (!is_array($field['condition']) && $field['condition'] !== null) {
            $field['condition'] = (string) $field['condition'];
        }

        $this->prepareActions($field['actions']);
        $this->prepareMapping($field['mapping']);

        $this->fields[$code] = $field;
    }

    /**
     * Prepare the field definition
     * @param array $definition
     * @return array
     */
    abstract protected function prepareField(array $definition): array;

    /**
     * @param mixed $actions
     * @return bool
     */
    private function prepareActions(&$actions): bool
    {
        if (
            $actions === false
            || $actions === null
            || $actions === ''
        ) {
            $actions = [];
            return false;
        }

        if (!is_array($actions)) {
            $actions = [$actions];
        }

        foreach ($actions as $key => $action) {
            $parameters = [];
            $actionName = $action;
            if (is_array($action)) {
                $actionName = key($action);
                $parameters = isset($action[$actionName]) ? $action[$actionName] : [];
            }
            $actions[$key] = [
                'name'       => $actionName,
                'parameters' => $parameters,
            ];
        }

        return true;
    }

    /**
     * @param array|null $mapping
     * @return bool
     * @throws RowReaderException
     * @SuppressWarnings(PMD.NPathComplexity)
     */
    private function prepareMapping(?array &$mapping): bool
    {
        if ($mapping === null) {
            return false;
        }

        if (!array_key_exists('field', $mapping)) {
            throw new RowReaderException('The mapping field definition is invalid - missing field');
        }
        if (!array_key_exists('source', $mapping)) {
            throw new RowReaderException('The mapping field definition is invalid - missing source');
        }
        if (!array_key_exists('table', $mapping['source'])) {
            throw new RowReaderException('The mapping field definition is invalid - missing source.table');
        }
        if (!array_key_exists('field', $mapping['source'])) {
            throw new RowReaderException('The mapping field definition is invalid - missing source.field');
        }
        if (!array_key_exists('link', $mapping['source'])) {
            throw new RowReaderException('The mapping field definition is invalid - missing source.link');
        }
        if (!array_key_exists('ignore_if_unknown', $mapping)) {
            $mapping['ignore_if_unknown'] = true;
        }

        $mapping['ignore_if_unknown'] = (bool) $mapping['ignore_if_unknown'];

        $query = sprintf(
            'SELECT %s AS `code`, %s AS `id` FROM %s',
            $this->connection->quoteIdentifier($mapping['source']['link']),
            $this->connection->quoteIdentifier($mapping['source']['field']),
            $this->connection->quoteIdentifier($mapping['source']['table'])
        );
        $list = $this->connection->executeQuery($query)->fetchAllAssociative();
        $mapping['values'] = [];
        foreach ($list as $row) {
            $mapping['values'][$row['code']] = $row['id'];
        }

        return true;
    }

    /**
     * Read a line
     * @param resource $fileHandler
     * @return null|array
     * @throws RowReaderException
     */
    abstract public function read($fileHandler): ?array;

    /**
     * Get the number of read lines
     * @return int
     */
    public function getNbReadLines(): int
    {
        return $this->currentReadLine;
    }

    /**
     * Get the number of accepted lines
     * @return int
     */
    public function getNbAcceptedLines(): int
    {
        return $this->currentAcceptedLine;
    }
}
