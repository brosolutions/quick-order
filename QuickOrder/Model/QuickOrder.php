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

namespace BroSolutions\QuickOrder\Model;

use BroSolutions\QuickOrder\Api\Data\QuickOrderInterface;
use Magento\Framework\Model\AbstractModel;

class QuickOrder extends AbstractModel implements QuickOrderInterface
{

    protected function _construct()
    {
        $this->_init('BroSolutions\QuickOrder\Model\ResourceModel\QuickOrder');
    }

    public function getId(): int|null
    {
        return $this->getData(self::ID);
    }

    public function setId($id): QuickOrder
    {
        return $this->setData(self::ID, $id);
    }

    public function getProductId(): int
    {
        return $this->getData(self::PRODUCT_ID);
    }

    public function setProductId(int $productId): QuickOrderInterface
    {
        return $this->setData(self::PRODUCT_ID, $productId);
    }

    public function getTelephone(): string
    {
        return $this->getData(self::TELEPHONE);
    }

    public function setTelephone(string $telephone): QuickOrderInterface
    {
        return $this->setData(self::TELEPHONE, $telephone);
    }

    public function getRequestParams(): string
    {
        return $this->getData(self::REQUEST_PARAMS);
    }
    public function setRequestParams(string $requestParams): QuickOrderInterface
    {
        return $this->setData(self::REQUEST_PARAMS, $requestParams);
    }

    public function getCreatedAt(): string
    {
        return $this->getData(self::CREATED_AT);
    }

    public function setCreatedAt(string $createdAt): QuickOrderInterface
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }
}
