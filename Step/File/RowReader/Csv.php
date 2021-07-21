<?php
declare(strict_types=1);

namespace Spipu\ProcessBundle\Step\File\RowReader;

use Spipu\ProcessBundle\Exception\RowReaderException;

class Csv extends AbstractRowReader
{
    /**
     * @var array
     */
    private $header;

    /**
     * Validate the parameters
     * @param array $parameters
     * @return bool
     * @throws RowReaderException
     */
    protected function validateParameters(array $parameters): bool
    {
        if (!array_key_exists('delimiter', $parameters)) {
            throw new RowReaderException('The parameter definition is invalid - missing delimiter');
        }

        if (!array_key_exists('enclosure', $parameters)) {
            throw new RowReaderException('The parameter definition is invalid - missing enclosure');
        }

        if (!array_key_exists('escape', $parameters)) {
            throw new RowReaderException('The parameter definition is invalid - missing escape');
        }

        return true;
    }

    /**
     * @param array $definition
     * @return array
     */
    protected function prepareField(array $definition): array
    {
        return [
            'field' => $definition['field'],
        ];
    }

    /**
     * Read a line
     * IMPORTANT: DO NOT SPLIT THIS METHOD, WE NEED PERFORMANCES !!!!
     * @param resource $fileHandler
     * @return null|array
     * @throws RowReaderException
     * @SuppressWarnings(PMD.CyclomaticComplexity)
     * @SuppressWarnings(PMD.NPathComplexity)
     */
    public function read($fileHandler): ?array
    {
        $this->prepareHeader($fileHandler);

        do {
            $line = $this->readCsvLine($fileHandler);

            if (!$line) {
                return null;
            }

            $this->currentReadLine++;

            $line = array_combine($this->header, $line);

            $row = [];
            $skipLine = false;

            foreach ($this->fields as $fieldCode => &$fieldDescription) {
                if (!array_key_exists($fieldDescription['field'], $line)) {
                    throw new RowReaderException(
                        sprintf(
                            'The field [%s] of the line [%d] is missing',
                            $fieldDescription['field'],
                            $this->currentReadLine
                        )
                    );
                }

                $value = $line[$fieldDescription['field']];

                foreach ($this->globalActions as $action) {
                    $value = $this->actionList->execute($action['name'], $value, $action['parameters']);
                }

                if (!$skipLine && $fieldDescription['required'] && ($value === '')) {
                    throw new RowReaderException(
                        sprintf(
                            'The field [%s] of the line [%d] is empty',
                            $fieldCode,
                            $this->currentReadLine
                        )
                    );
                }

                foreach ($fieldDescription['actions'] as $action) {
                    $value = $this->actionList->execute($action['name'], $value, $action['parameters']);
                }

                if ($fieldDescription['import']) {
                    $row[$fieldCode] = $value;
                }

                if (is_array($fieldDescription['condition']) && !in_array($value, $fieldDescription['condition'])) {
                    $skipLine = true;
                }
                if (is_string($fieldDescription['condition']) && $fieldDescription['condition'] !== $value) {
                    $skipLine = true;
                }

                if ($fieldDescription['mapping'] !== null) {
                    $mappingField = $fieldDescription['mapping']['field'];

                    $row[$mappingField] = null;
                    if ($value !== '') {
                        if (array_key_exists($value, $fieldDescription['mapping']['values'])) {
                            $row[$mappingField] = $fieldDescription['mapping']['values'][$value];
                        } elseif ($fieldDescription['mapping']['ignore_if_unknown']) {
                            $skipLine = true;
                        }
                    }
                }
            }
        } while ($skipLine);

        $this->currentAcceptedLine++;

        return $row;
    }

    /**
     * @param resource $fileHandler
     * @return bool
     * @throws RowReaderException
     */
    private function prepareHeader($fileHandler): bool
    {
        if ($this->header) {
            return false;
        }

        $this->header = $this->readCsvLine($fileHandler);

        if (!$this->header) {
            throw new RowReaderException('The file must have at least one line for the header');
        }

        foreach ($this->header as $key => $value) {
            $this->header[$key] = trim($value);
        }

        return true;
    }

    /**
     * @param resource $fileHandler
     * @return array|false|null
     */
    private function readCsvLine($fileHandler)
    {
        return fgetcsv(
            $fileHandler,
            0,
            $this->parameters['delimiter'],
            $this->parameters['enclosure'],
            $this->parameters['escape']
        );
    }
}
