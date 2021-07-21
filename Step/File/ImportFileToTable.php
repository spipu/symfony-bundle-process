<?php
declare(strict_types=1);

namespace Spipu\ProcessBundle\Step\File;

use Exception;
use Spipu\ProcessBundle\Entity\Process\ParametersInterface;
use Spipu\ProcessBundle\Exception\RowReaderException;
use Spipu\ProcessBundle\Exception\StepException;
use Spipu\ProcessBundle\Service\LoggerInterface;
use Spipu\ProcessBundle\Step\File\RowReader\RowReaderInterface;
use Spipu\ProcessBundle\Step\StepInterface;
use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ImportFileToTable implements StepInterface
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var int
     */
    private $maxRowToInsert;

    /**
     * @var int
     */
    private $logEveryRows;

    /**
     * ImportFileToTable constructor.
     * @param Connection $connection
     * @param ContainerInterface $container
     * @param int $maxRowToInsert
     * @param int $logEveryRows
     */
    public function __construct(
        Connection $connection,
        ContainerInterface $container,
        int $maxRowToInsert = 1000,
        int $logEveryRows = 1000000
    ) {
        $this->connection = $connection;
        $this->container = $container;
        $this->maxRowToInsert = $maxRowToInsert;
        $this->logEveryRows = $logEveryRows;
    }

    /**
     * @param ParametersInterface $parameters
     * @param LoggerInterface $logger
     * @return array
     * @throws StepException
     * @throws RowReaderException
     */
    public function execute(ParametersInterface $parameters, LoggerInterface $logger)
    {
        $filename  = $parameters->get('filename');
        $logger->debug(sprintf('File to import: [%s]', $filename));

        $tablename = $parameters->get('tablename');
        $logger->debug(sprintf('Table to use: [%s]', $tablename));

        $logger->debug('Prepare Row Reader');
        $rowReader = $this->getRowReader($parameters->get('row_reader'));
        $logger->debug(' => Ready');

        $logger->debug('Count the number of lines');
        $nbLines = $this->getNbLines($filename);
        $logger->debug(sprintf(' => [%d] lines', $nbLines));

        // Add security on the number of lines.
        if ($nbLines < 1) {
            $nbLines = 1;
        }

        // We want to log every 1/8 part of the file, with some security.
        $logEveryRows = (int) ($nbLines / 8);
        if ($logEveryRows < 100) {
            $logEveryRows = 100;
        }
        if ($logEveryRows > $this->logEveryRows) {
            $logEveryRows = $this->logEveryRows;
        }

        $fileHandle = $this->openFile($filename);
        try {
            $rows = [];
            while ($row = $rowReader->read($fileHandle)) {
                if (($rowReader->getNbAcceptedLines() % $logEveryRows) == 0) {
                    $logger->setProgress((int) (100 * $rowReader->getNbReadLines() / $nbLines));
                    $logger->debug(
                        sprintf(
                            ' - reading... imported [%s] / read [%s]',
                            number_format($rowReader->getNbAcceptedLines(), 0, ',', '.'),
                            number_format($rowReader->getNbReadLines(), 0, ',', '.')
                        )
                    );
                }
                $rows[] = $row;
                unset($row);
                if (count($rows) >= $this->maxRowToInsert) {
                    $this->insertRows($rows, $tablename);
                    $rows = [];
                }
            }
            if (count($rows) > 0) {
                $this->insertRows($rows, $tablename);
            }
            unset($rows);

            $logger->debug(
                sprintf(
                    'Read lines: [%s]',
                    number_format($rowReader->getNbReadLines(), 0, ',', '.')
                )
            );
            $logger->debug(
                sprintf(
                    'Imported lines: [%s]',
                    number_format($rowReader->getNbAcceptedLines(), 0, ',', '.')
                )
            );
        } finally {
            fclose($fileHandle);
        }

        return [
            'read'     => $rowReader->getNbReadLines(),
            'imported' => $rowReader->getNbAcceptedLines(),
        ];
    }

    /**
     * Get the number of lines
     * @param string $filename
     * @return int
     */
    private function getNbLines(string $filename): int
    {
        $cmd = 'wc -l '.escapeshellarg($filename);
        try {
            $output = shell_exec($cmd);
            $output = trim(explode("\n", $output)[0]);
            $output = trim(explode(" ", $output)[0]);

            return intval($output);
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * @param array $definition
     * @return RowReaderInterface
     * @throws StepException
     * @throws RowReaderException
     */
    private function getRowReader(array $definition): RowReaderInterface
    {
        $rowReader = clone $this->container->get($definition['class']);
        if (!($rowReader instanceof RowReaderInterface)) {
            throw new StepException('The asked RowReader does not implements the RowReaderInterface');
        }

        $rowReader->init();
        if (array_key_exists('parameters', $definition)) {
            $rowReader->setParameters($definition['parameters']);
        }
        $rowReader->setFields($definition['fields']);

        if (array_key_exists('global_actions', $definition)) {
            $rowReader->setGlobalActions($definition['global_actions']);
        }

        return $rowReader;
    }

    /**
     * @param string $filename
     * @return resource
     * @throws StepException
     */
    private function openFile(string $filename)
    {
        if (!is_file($filename)) {
            throw new StepException(sprintf("%s does not exist.", $filename));
        }

        if (!is_readable($filename)) {
            throw new StepException(sprintf("Unable to read %s.", $filename));
        }

        $fileHandle = fopen($filename, "r");
        if ($fileHandle === false) {
            throw new StepException(sprintf("Unable to open %s.", $filename));
        }

        $fileSize = filesize($filename);
        if ($fileSize == 0) {
            fclose($fileHandle);
            throw new StepException(sprintf("Unable to open %s.", $filename));
        }

        return $fileHandle;
    }

    /**
     * Insert the rows
     * @param array $rows
     * @param string $tablename
     * @return void
     * @throws StepException
     */
    private function insertRows(array &$rows, string $tablename): void
    {
        $columns = array_keys($rows[0]);
        foreach ($columns as &$column) {
            $column = $this->connection->quoteIdentifier($column);
        }

        foreach ($rows as &$row) {
            foreach ($row as &$value) {
                $fieldValue = $value;
                $value = $this->connection->quote($value);

                if (is_null($fieldValue)) {
                    $value = 'NULL';
                }
            }
            $row = '('.implode(',', $row).')';
        }

        $query = sprintf(
            'INSERT INTO %s %s VALUES %s;',
            $this->connection->quoteIdentifier($tablename),
            '('.implode(',', $columns).')',
            implode(', ', $rows)
        );

        try {
            $this->connection->getWrappedConnection()->exec($query);
        } catch (Exception $e) {
            throw new StepException($e->getMessage());
        }
    }
}
