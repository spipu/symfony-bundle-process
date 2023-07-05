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

namespace Spipu\ProcessBundle\Service;

use Spipu\ProcessBundle\Entity\Process\Input;
use Spipu\ProcessBundle\Entity\Process\Process;
use Spipu\ProcessBundle\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileManager implements FileManagerInterface
{
    private string $folderImport;
    private string $folderExport;

    public function __construct(
        string $folderImport,
        string $folderExport
    ) {
        $this->folderImport = $this->cleanPath($folderImport);
        $this->folderExport = $this->cleanPath($folderExport);
    }

    public function saveInputFile(Process $process, Input $input, File $file): string
    {
        $extension = $file->guessExtension();
        if ($extension === '' || $extension === null) {
            $extension = 'bin';
        }
        $filename = $this->buildFilename($input->getName(), $extension);

        $folder = $this->getFolderImport() . $this->cleanCode($process->getCode());
        $this->prepareFolder($folder);

        if ($file instanceof UploadedFile) {
            $file->move($folder, $filename);
            return $filename;
        }

        copy($file->getPathname(), $folder . '/' . $filename);
        return $filename;
    }

    public function getInputFilePath(Process $process, Input $input, string $filename): string
    {
        $folder = $this->getFolderImport() . $this->cleanCode($process->getCode());

        $fullPath = $folder . '/' . $filename;
        if (!is_file($fullPath) || !is_readable($fullPath)) {
            throw new FileException('The input file [' . $input->getName() . '] is not readable');
        }

        return $fullPath;
    }

    public function prepareLocalOutputFile(string $folderCode, string $fileCode, string $fileExtension): string
    {
        $folder = $this->getFolderExport() . $this->cleanCode($folderCode);
        $this->prepareFolder($folder);

        $filename = $this->buildFilename($fileCode, $fileExtension) . '.tmp';
        if (is_file($folder . '/' . $filename)) {
            throw new FileException('An temporary output file already exists with this name');
        }

        touch($folder . '/' . $filename);

        return $filename;
    }

    public function getLocalOutputFilePath(string $folderCode, string $localFileName): string
    {
        $folder = $this->getFolderExport() . $this->cleanCode($folderCode);

        $fullPath = $folder . '/' . $localFileName;
        if (!is_file($fullPath) || !is_writeable($fullPath)) {
            throw new FileException('The output file is not writeable');
        }

        return $fullPath;
    }

    public function finalizeOutputFile(
        string $folderCode,
        string $fileCode,
        string $fileExtension,
        string $localFileName
    ): string {
        $folder = $this->getFolderExport() . $this->cleanCode($folderCode);
        $this->prepareFolder($folder);

        $filename = $this->buildFilename($fileCode, $fileExtension);
        if (is_file($folder . '/' . $filename)) {
            throw new FileException('An final output file already exists with this name');
        }

        if (!is_file($folder . '/' . $localFileName)) {
            throw new FileException('The temporary output file does not exists');
        }

        rename($folder . '/' . $localFileName, $folder . '/' . $filename);

        return $filename;
    }

    public function getOutputFileDownloadPath(string $folderCode, string $filename): string
    {
        $folder = $this->getFolderExport() . $this->cleanCode($folderCode);
        if (!is_dir($folder) || !is_readable($folder)) {
            throw new FileException('The output folder is not readable');
        }

        $fullPath = $folder . '/' . $filename;
        if (!is_file($fullPath) || !is_readable($fullPath)) {
            throw new FileException('The output file is not readable');
        }

        return $fullPath;
    }

    private function getFolderImport(): string
    {
        return $this->folderImport;
    }

    private function getFolderExport(): string
    {
        return $this->folderExport;
    }

    private function buildFilename(string $fileCode, string $fileExtension): string
    {
        $fileCode = $this->cleanCode($fileCode);
        return $fileCode . '_' . date('YmdHis') . '_' . uniqid() . '.' . $fileExtension;
    }

    /**
     * @param string $folder
     * @return void
     * @throws FileException
     * @SuppressWarnings(PMD.ErrorControlOperator)
     */
    private function prepareFolder(string $folder): void
    {
        if (!is_dir($folder)) {
            if (!@mkdir($folder, 0775, true)) {
                throw new FileException('The folder [' . $folder . '] can not be created');
            }
        }
        if (!is_writable($folder)) {
            throw new FileException('The folder [' . $folder . '] is not writeable');
        }
    }

    private function cleanPath(string $path): string
    {
        $path = rtrim(str_replace(['\\', '//'], '/', $path), '/');

        if ($path === '') {
            throw new FileException('The folder can not be an empty string');
        }

        return $path . '/';
    }

    private function cleanCode(string $code): string
    {
        return trim(str_replace(['\\', '/', '.', ' '], '', mb_strtolower($code)));
    }
}
