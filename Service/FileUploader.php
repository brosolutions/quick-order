<?php
/**
 * Copyright (c) 2025 BroSolutions
 * All rights reserved
 *
 * This product includes proprietary software developed at BroSolutions, Ukraine
 * For more information see https://www.brosolutions.net/
 *
 * To obtain a valid license for using this software please contact us at
 * contact@brosolutions.net
 */
declare(strict_types=1);

namespace BroSolutions\QuickOrder\Service;

use Exception;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\File\UploaderFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Io\File;

/**
 * @copyright  Copyright (c) 2025 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class FileUploader
{
    /**
     * @var string
     */
    private const DIRECTORY_TO_DOWNLOAD = 'quickorder_files';

    /**
     * @var UploaderFactory
     */
    private $uploaderFactory;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var File
     */
    private $fileIo;

    /**
     * @param UploaderFactory $uploaderFactory
     * @param Filesystem $filesystem
     * @param File $fileIo
     */
    public function __construct(
        UploaderFactory  $uploaderFactory,
        Filesystem       $filesystem,
        File $fileIo
    ) {
        $this->uploaderFactory = $uploaderFactory;
        $this->filesystem = $filesystem;
        $this->fileIo = $fileIo;
    }

    /**
     * File upload
     *
     * @param string $fileName
     * @param string $fileInputName
     * @param string $directory
     * @return string
     * @throws Exception
     */
    public function execute(string $fileName, string $fileInputName = 'file', string $directory = DirectoryList::MEDIA): string
    {
        $folderStructure = $this->generateFolderStructure($fileName);

        $uploader = $this->uploaderFactory->create(['fileId' => $fileInputName]);
        $uploader->setAllowCreateFolders(true);

        $directory = $this->filesystem->getDirectoryRead($directory);

        $uploadDir = $directory->getAbsolutePath(self::DIRECTORY_TO_DOWNLOAD . '/' . $folderStructure);
        if (!$this->fileIo->fileExists($uploadDir)) {
            $this->fileIo->mkdir($uploadDir, 0755, true);
        }

        $fileName = $uploader->save($uploadDir)['file'];

        return self::DIRECTORY_TO_DOWNLOAD . '/' . $folderStructure . '/' . $fileName;
    }

    /**
     * Generate folder structure
     *
     * @param string $fileName
     * @return string
     */
    private function generateFolderStructure(string $fileName): string
    {
        $firstLetter = strtolower(substr($fileName, 0, 1));
        $timestamp = time();

        return $firstLetter . '/' . $timestamp;
    }
}
