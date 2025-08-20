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

namespace BroSolutions\QuickOrder\Controller\Index;

use BroSolutions\QuickOrder\Model\ProductManagement;
use BroSolutions\QuickOrder\Service\CsvProductParser;
use BroSolutions\QuickOrder\Service\FileUploader;
use BroSolutions\QuickOrder\Service\FilterProducts;
use Exception;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Filesystem;

/**
 * @copyright  Copyright (c) 2025 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class UploadCsv implements HttpPostActionInterface
{
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var Http
     */
    private $request;

    /**
     * @var FormKeyValidator
     */
    private $formKeyValidator;

    /**
     * @var FileUploader
     */
    private $fileUploader;

    /**
     * @var CsvProductParser
     */
    private $csvProductParser;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var ProductManagement
     */
    private $productManagement;

    /**
     * @var FilterProducts
     */
    private $filterProducts;

    /**
     * @param Http $request
     * @param JsonFactory $resultJsonFactory
     * @param FormKeyValidator $formKeyValidator
     * @param FileUploader $fileUploader
     * @param CsvProductParser $csvProductParser
     * @param Filesystem $filesystem
     * @param ProductManagement $productManagement
     * @param FilterProducts $filterProducts
     */
    public function __construct(
        Http             $request,
        JsonFactory      $resultJsonFactory,
        FormKeyValidator $formKeyValidator,
        FileUploader $fileUploader,
        CsvProductParser $csvProductParser,
        Filesystem $filesystem,
        ProductManagement $productManagement,
        FilterProducts $filterProducts
    ) {
        $this->request = $request;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->formKeyValidator = $formKeyValidator;
        $this->fileUploader = $fileUploader;
        $this->csvProductParser = $csvProductParser;
        $this->filesystem = $filesystem;
        $this->productManagement = $productManagement;
        $this->filterProducts = $filterProducts;
    }

    /**
     * Add to cart
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $resultJson = $this->resultJsonFactory->create();
        if (!$this->formKeyValidator->validate($this->request)) {
            return $resultJson->setData(
                [
                    'success' => false,
                    'message' => __('Invalid Form Key. Please refresh the page.')
                ]
            );
        }

        try {

            $file = $this->request->getFiles('csv');
            if (!$file || empty($file['tmp_name'])) {
                return $resultJson->setData(
                    [
                        'success' => false,
                        'message' => __('No file uploaded.')
                    ]
                );
            }

            // basic MIME/extension sanity checks
            $allowed = ['text/csv', 'text/plain', 'application/vnd.ms-excel'];
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            if (!in_array($file['type'], $allowed) || !in_array(strtolower($ext), ['csv'])) {

                return $resultJson->setData(
                    [
                        'success' => false,
                        'message' => __('Please upload a .csv file.')
                    ]
                );

            }

            // make a unique file name
            $filename = preg_replace('/[^A-Za-z0-9_\.\-]/', '_', $file['name']);

            $filePath = $this->fileUploader->execute($filename, 'csv', DirectoryList::VAR_DIR);
            $varDir = $this->filesystem->getDirectoryRead(DirectoryList::VAR_DIR);
            $filePath = $varDir->getAbsolutePath($filePath);
            $csvFileContentArray = $this->csvProductParser->execute($filePath);

            $products = $this->productManagement->getProduct($csvFileContentArray, $this->request->getParam('storeCode'));

            $filteredProducts = $this->filterProducts->execute($products, $csvFileContentArray);

        } catch (Exception $e) {
            return $resultJson->setData(
                [
                    'success' => false,
                    'message' => $e->getMessage()
                ]
            );
        }

        return $resultJson->setData([
            'success' => true,
            'products' => $filteredProducts
        ]);
    }
}
