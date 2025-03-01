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
use BroSolutions\QuickOrder\Model\CartManagement;

/**
 * @copyright  Copyright (c) 2025 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class AddToCart implements HttpPostActionInterface
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
     * @var CartManagement
     */
    private $cartManagement;

    /**
     * @param Http $request
     * @param JsonFactory $resultJsonFactory
     * @param FormKeyValidator $formKeyValidator
     * @param CartManagement $cartManagement
     */
    public function __construct(
        Http             $request,
        JsonFactory      $resultJsonFactory,
        FormKeyValidator $formKeyValidator,
        CartManagement   $cartManagement,
    ) {
        $this->request = $request;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->formKeyValidator = $formKeyValidator;
        $this->cartManagement = $cartManagement;
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
            $this->cartManagement->addToCart($this->request->getParam('jsonData'));
        } catch (Exception $e) {
            return $resultJson->setData(
                [
                    'success' => false,
                    'message' => $e->getMessage()
                ]
            );
        }

        return $resultJson->setData([
            'success' => true
        ]);
    }
}
