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

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Exception;
use BroSolutions\QuickOrder\Service\FileUploader;

/**
 * @copyright  Copyright (c) 2025 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class Upload implements HttpPostActionInterface
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
     * @param Http $request
     * @param JsonFactory $resultJsonFactory
     * @param FormKeyValidator $formKeyValidator
     * @param FileUploader $fileUploader
     */
    public function __construct(
        Http             $request,
        JsonFactory      $resultJsonFactory,
        FormKeyValidator $formKeyValidator,
        FileUploader $fileUploader,
    ) {
        $this->request = $request;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->formKeyValidator = $formKeyValidator;
        $this->fileUploader = $fileUploader;
    }

    /**
     * Upload functionality
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $resultJson = $this->resultJsonFactory->create();
        if (!$this->formKeyValidator->validate($this->request)) {
            return $resultJson->setData(['success' => false,
                    'message' => __('Invalid Form Key. Please refresh the page.')
                ]);
        }

        try {
            $files = $this->request->getFiles()->toArray();
            if ($this->request->isPost() && isset($files['file']) && $files['file']['error'] == 0) {
                $file = $files['file'];

                if (!preg_match('/^[a-zA-Z0-9_-]+(\.[a-zA-Z0-9]+)?$/', $file['name'])) {
                    return $resultJson->setData([
                        'success' => false,
                        'message' => __('File name contains invalid characters.
                        Only alphanumeric characters, underscores, dashes, and dots are allowed.')
                    ]);
                }

                return $resultJson->setData([
                    'success' => true,
                    'message' => __('File uploaded successfully!'),
                    'file_path' => $this->fileUploader->execute($file['name'])
                ]);
            } else {
                return $resultJson->setData([
                    'success' => false,
                    'message' => __('No file uploaded or error uploading file.')
                ]);
            }
        } catch (Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
