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

use BroSolutions\QuickOrder\Service\SaveProductListToAccount;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * @copyright  Copyright (c) 2025 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class SaveList implements HttpPostActionInterface
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
     * @var SaveProductListToAccount
     */
    private $saveProductListToAccount;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var RedirectFactory
     */
    private $redirectFactory;

    /**
     * @param Http $request
     * @param JsonFactory $resultJsonFactory
     * @param FormKeyValidator $formKeyValidator
     * @param SaveProductListToAccount $saveProductListToAccount
     * @param CustomerSession $customerSession
     * @param RedirectFactory $redirectFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        Http             $request,
        JsonFactory      $resultJsonFactory,
        FormKeyValidator $formKeyValidator,
        SaveProductListToAccount   $saveProductListToAccount,
        CustomerSession $customerSession,
        RedirectFactory $redirectFactory,
        LoggerInterface $logger
    ) {
        $this->request = $request;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->formKeyValidator = $formKeyValidator;
        $this->saveProductListToAccount = $saveProductListToAccount;
        $this->customerSession = $customerSession;
        $this->redirectFactory = $redirectFactory;
        $this->logger = $logger;
    }

    /**
     * Throwable
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $errorMessage =  [
            'success' => false,
            'message' => __('Something went wrong while saving the list')
        ];

        $resultRedirect = $this->redirectFactory->create();
        $resultJson = $this->resultJsonFactory->create();
        try {
            if (!$this->formKeyValidator->validate($this->request)) {

                return $resultJson->setData(
                    [
                        'success' => false,
                        'message' => __('Invalid Form Key. Please refresh the page.')
                    ]
                );
            }

            if (!$this->customerSession->isLoggedIn()) {
                return $resultRedirect->setPath('customer/account/login');
            }

            $this->saveProductListToAccount->execute(
                $this->request->getParam('jsonData'),
                $this->request->getParam('list_name')
            );
        } catch (NoSuchEntityException $e) {
            return $resultJson->setData($errorMessage);
        } catch (Throwable $e) {
            $this->logger->error(sprintf('Error saving list: %s', $e->getMessage()), $e->getTrace());
            return $resultJson->setData($errorMessage);
        }

        return $resultJson->setData([
            'success' => true
        ]);
    }
}
