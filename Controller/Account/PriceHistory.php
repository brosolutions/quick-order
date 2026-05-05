<?php
/**
 * Copyright (c) 2026 BroSolutions
 * All rights reserved
 *
 * This product includes proprietary software developed at BroSolutions, Ukraine
 * For more information see https://www.brosolutions.net/
 *
 * To obtain a valid license for using this software please contact us at
 * contact@brosolutions.net
 */
declare(strict_types=1);

namespace BroSolutions\QuickOrder\Controller\Account;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use BroSolutions\QuickOrder\Model\ResourceModel\PriceHistory\CollectionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;

/**
 * @copyright  Copyright (c) 2026 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class PriceHistory implements HttpGetActionInterface
{
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @param JsonFactory $resultJsonFactory
     * @param CollectionFactory $collectionFactory
     * @param RequestInterface $request
     */
    public function __construct(
        JsonFactory $resultJsonFactory,
        CollectionFactory $collectionFactory,
        RequestInterface $request
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->collectionFactory = $collectionFactory;
        $this->request = $request;
    }

    /**
     * Price history
     *
     * @return Json
     */
    public function execute()
    {
        $sku = $this->request->getParam('sku');
        $result = $this->resultJsonFactory->create();

        $collection = $this->collectionFactory->create()
            ->addFieldToFilter('sku', $sku)
            ->setOrder('changed_at', 'ASC');

        $data = [];
        foreach ($collection as $item) {
            $data[] = [
                'date' => date('Y-m-d H:i', strtotime($item->getChangedAt())),
                'price' => (float)$item->getNewPrice()
            ];
        }

        return $result->setData($data);
    }
}
