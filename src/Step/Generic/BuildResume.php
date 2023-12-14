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

namespace Spipu\ProcessBundle\Step\Generic;

use Spipu\ProcessBundle\Entity\Process\ParametersInterface;
use Spipu\ProcessBundle\Service\LoggerInterface;
use Spipu\ProcessBundle\Step\StepInterface;

class BuildResume implements StepInterface
{
    public function execute(ParametersInterface $parameters, LoggerInterface $logger): array
    {
        $resume = [
            'Imported File:    ' . $parameters->get('result.get_file'),
            'Read Lines:       ' . $parameters->get('result.import_file')['read'],
            'Imported Lines:   ' . $parameters->get('result.import_file')['imported'],
        ];

        $updateDatabaseResult = $parameters->get('result.update_database');
        if (array_key_exists('inserted', $updateDatabaseResult)) {
            $resume[] = 'Inserted Rows:    ' . $updateDatabaseResult['inserted'];
        }
        if (array_key_exists('updated', $updateDatabaseResult)) {
            $resume[] = 'Updated Rows:     ' . $updateDatabaseResult['updated'];
        }
        if (array_key_exists('deleted', $updateDatabaseResult)) {
            $resume[] = 'Deleted Rows:     ' . $updateDatabaseResult['deleted'];
        }
        if (array_key_exists('disabled', $updateDatabaseResult)) {
            $resume[] = 'Disabled Rows:    ' . $updateDatabaseResult['disabled'];
        }

        $resume[] = 'Archived File:    ' . $parameters->get('result.archive_file');

        $logger->notice(implode("\n", $resume));

        return $resume;
    }
}
