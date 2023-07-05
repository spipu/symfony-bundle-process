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

use Spipu\ProcessBundle\Exception\FileException;

class FileExportManager
{
    private FileManagerInterface $fileManager;
    private string $folderCode;
    private string $fileCode;
    private string $fileExtension;
    private ?string $localFileName = null;
    private ?string $finalFileName = null;

    public function __construct(
        FileManagerInterface $fileManager,
        string $folderCode,
        string $fileCode,
        string $fileExtension
    ) {
        $this->fileManager = $fileManager;
        $this->folderCode = $folderCode;
        $this->fileCode = $fileCode;
        $this->fileExtension = $fileExtension;
    }

    public function prepare(): void
    {
        if ($this->localFileName !== null) {
            throw new FileException('The FileExport is already prepared');
        }

        $this->localFileName = $this->fileManager->prepareLocalOutputFile(
            $this->folderCode,
            $this->fileCode,
            $this->fileExtension
        );
    }

    public function finalizeFile(): void
    {
        if ($this->finalFileName !== null) {
            throw new FileException('The FileExport is already finalized');
        }

        $this->finalFileName = $this->fileManager->finalizeOutputFile(
            $this->folderCode,
            $this->fileCode,
            $this->fileExtension,
            $this->localFileName
        );
    }

    public function getFolderCode(): string
    {
        return $this->folderCode;
    }

    public function getLocalFilePath(): string
    {
        if ($this->localFileName === null) {
            throw new FileException('The FileExport is not yet prepared');
        }

        return $this->fileManager->getLocalOutputFilePath($this->folderCode, $this->localFileName);
    }

    public function getFinalFileName(): string
    {
        if ($this->finalFileName === null) {
            throw new FileException('The FileExport is not yet finalized');
        }

        return $this->finalFileName;
    }
}
