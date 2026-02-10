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
use Exception;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * @copyright  Copyright (c) 2025 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class CreateAutomatedOrder implements HttpPostActionInterface
{
    /**
     * @var Http
     */
    private $request;

    /**
     * @var FormKeyValidator
     */
    private $formKeyValidator;

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var SaveProductListToAccount
     */
    private $saveProductListToAccount;

    /**
     * @param Http $request
     * @param FormKeyValidator $formKeyValidator
     * @param JsonFactory $resultJsonFactory
     * @param SaveProductListToAccount $saveProductListToAccount
     */
    public function __construct(
        Http             $request,
        FormKeyValidator $formKeyValidator,
        JsonFactory      $resultJsonFactory,
        SaveProductListToAccount $saveProductListToAccount
    ) {
        $this->request = $request;
        $this->formKeyValidator = $formKeyValidator;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->saveProductListToAccount = $saveProductListToAccount;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();

        if (!$this->formKeyValidator->validate($this->request)) {
            return $resultJson->setData(['success' => false,
                'message' => __('Invalid Form Key. Please refresh the page.')
            ]);
        }

        try {

            $data = $this->saveProductListToAccount->execute($this->request->getParam('jsonData'));

        } catch (Exception | LocalizedException| NoSuchEntityException $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
