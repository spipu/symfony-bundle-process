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

use Spipu\ProcessBundle\Exception\RowReaderException;

class FixedWidth extends AbstractRowReader
{
    /**
     * @param array $definition
     * @return array
     */
    protected function prepareField(array $definition): array
    {
        return [
            'start'     => $definition['start'],
            'length'    => $definition['length'],
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
        do {
            $line = fgets($fileHandler);

            if (!$line) {
                return null;
            }

            $this->currentReadLine++;

            foreach ($this->globalActions as $action) {
                $line = $this->actionList->execute($action['name'], $line, $action['parameters']);
            }

            $row = [];
            $skipLine = false;
            foreach ($this->fields as $fieldCode => &$fieldDescription) {
                $fieldStart  = $fieldDescription['start'];
                $fieldLength = $fieldDescription['length'];

                if (strlen($line) < $fieldStart + $fieldLength - 1) {
                    throw new RowReaderException(
                        sprintf(
                            'The length of the line [%d] is lower than [%d]',
                            $this->currentReadLine,
                            $fieldStart + $fieldLength - 1
                        )
                    );
                }

                $value = trim(mb_substr($line, $fieldStart - 1, $fieldLength));

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

                if (is_array($fieldDescription['condition']) && !in_array($value, $fieldDescription['condition'])) {
                    $skipLine = true;
                }
                if (is_string($fieldDescription['condition']) && $fieldDescription['condition'] !== $value) {
                    $skipLine = true;
                }

                if ($fieldDescription['import']) {
                    $row[$fieldCode] = $value;
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
}
