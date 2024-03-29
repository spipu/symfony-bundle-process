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

namespace Spipu\ProcessBundle\Step\File;

use Spipu\ProcessBundle\Entity\Process\ParametersInterface;
use Spipu\ProcessBundle\Exception\StepException;
use Spipu\ProcessBundle\Service\LoggerInterface;
use Spipu\ProcessBundle\Step\StepInterface;
use ZipArchive;

class ExtractZipFile implements StepInterface
{
    private array $zipErrors = [
        ZipArchive::ER_OK           => "No error.",
        ZipArchive::ER_MULTIDISK    => "Multi-disk zip archives not supported.",
        ZipArchive::ER_RENAME       => "Renaming temporary file failed.",
        ZipArchive::ER_CLOSE        => "Closing zip archive failed",
        ZipArchive::ER_SEEK         => "Seek error",
        ZipArchive::ER_READ         => "Read error",
        ZipArchive::ER_WRITE        => "Write error",
        ZipArchive::ER_CRC          => "CRC error",
        ZipArchive::ER_ZIPCLOSED    => "Containing zip archive was closed",
        ZipArchive::ER_NOENT        => "No such file.",
        ZipArchive::ER_EXISTS       => "File already exists",
        ZipArchive::ER_OPEN         => "Can't open file",
        ZipArchive::ER_TMPOPEN      => "Failure to create temporary file.",
        ZipArchive::ER_ZLIB         => "Zlib error",
        ZipArchive::ER_MEMORY       => "Memory allocation failure",
        ZipArchive::ER_CHANGED      => "Entry has been changed",
        ZipArchive::ER_COMPNOTSUPP  => "Compression method not supported.",
        ZipArchive::ER_EOF          => "Premature EOF",
        ZipArchive::ER_INVAL        => "Invalid argument",
        ZipArchive::ER_NOZIP        => "Not a zip archive",
        ZipArchive::ER_INTERNAL     => "Internal error",
        ZipArchive::ER_INCONS       => "Zip archive inconsistent",
        ZipArchive::ER_REMOVE       => "Can't remove file",
        ZipArchive::ER_DELETED      => "Entry has been deleted",
    ];

    public function execute(ParametersInterface $parameters, LoggerInterface $logger): string
    {
        $file = (string) $parameters->get('file');
        $destination = (string) $parameters->get('destination');

        $logger->debug(sprintf('File: [%s]', $file));
        $logger->debug(sprintf('Destination: [%s]', $destination));

        if (!is_dir($destination)) {
            mkdir($destination, 0775, true);
        }

        $zip = new ZipArchive();
        $result = $zip->open($file);

        if ($result !== true) {
            $error = 'Unknown error';
            if (array_key_exists($result, $this->zipErrors)) {
                $error = $this->zipErrors[$result];
            }
            throw new StepException(sprintf('ZIP Error: %d - %s', $result, $error));
        }

        $zip->extractTo($destination);
        $zip->close();

        return $destination;
    }
}
