<?php
/**
 * Copyright (c) 2024 BroSolutions
 * All rights reserved
 *
 * This product includes proprietary software developed at BroSolutions, Ukraine
 * For more information see https://www.brosolutions.net/
 *
 * To obtain a valid license for using this software please contact us at
 * contact@brosolutions.net
 */
declare(strict_types=1);

namespace BroSolutions\QuickOrder\Plugin\Cart;

use BroSolutions\QuickOrder\Model\QuickOrderRepository;
use BroSolutions\QuickOrder\Model\QuickOrderFactory;
use Closure;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Checkout\Controller\Cart\Add;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * @copyright  Copyright (c) 2025 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class AddPlugin
{
    /**
     * @var Http
     */
    private $request;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * @var QuickOrderRepository
     */
    private $quickOrderRepository;

    /**
     * @var QuickOrderFactory
     */
    private $quickOrderFactory;

    /**
     * @param Http $request
     * @param Json $json
     * @param ResponseInterface $response
     * @param QuickOrderRepository $quickOrderRepository
     * @param QuickOrderFactory $quickOrderFactory
     */
    public function __construct(
        Http $request,
        Json $json,
        ResponseInterface $response,
        QuickOrderRepository $quickOrderRepository,
        QuickOrderFactory $quickOrderFactory
    ) {
        $this->request = $request;
        $this->json = $json;
        $this->response = $response;
        $this->quickOrderRepository = $quickOrderRepository;
        $this->quickOrderFactory = $quickOrderFactory;
    }

    /**
     * @param Add $subject
     * @param Closure $proceed
     * @return ResponseInterface|ResultInterface
     * @throws CouldNotSaveException
     */
    public function aroundExecute(Add $subject, Closure $proceed): ResponseInterface|ResultInterface
    {
        $isQuickOrder = $this->request->getParam('quick_order', false);
        if ($isQuickOrder) {
            $quickOrderParams = $this->request->getParams();
            $quickOrder = $this->quickOrderFactory->create();
            $quickOrder->setTelephone($quickOrderParams['quick_order_telephone']);
            $quickOrder->setProductId((int)$quickOrderParams['product']);
            $quickOrder->setRequestParams($this->json->serialize($quickOrderParams));
            $this->quickOrderRepository->save($quickOrder);

            $this->response->representJson(
                $this->json->serialize([])
            );

            return $this->response;
        }

        return $proceed();
    }
}
