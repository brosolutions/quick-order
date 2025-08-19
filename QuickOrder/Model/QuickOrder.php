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

/**
 * @copyright  Copyright (c) 2025 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class QuickOrder extends AbstractModel implements QuickOrderInterface
{

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\BroSolutions\QuickOrder\Model\ResourceModel\QuickOrder::class);
    }

    /**
     * @return int|null
     */
    public function getId(): int|null
    {
        $id = $this->getData(self::ID);
        if ($id !== null) {
            return (int)$id;
        }
        return null;
    }

    /**
     * @param $id
     * @return QuickOrder
     */
    public function setId($id): QuickOrder
    {
        return $this->setData(self::ID, $id);
    }

    /**
     * @return int
     */
    public function getProductId(): int
    {
        return (int)$this->getData(self::PRODUCT_ID);
    }

    /**
     * @param int $productId
     * @return QuickOrderInterface
     */
    public function setProductId(int $productId): QuickOrderInterface
    {
        return $this->setData(self::PRODUCT_ID, $productId);
    }

    /**
     * @return string
     */
    public function getTelephone(): string
    {
        return $this->getData(self::TELEPHONE);
    }

    /**
     * @param string $telephone
     * @return QuickOrderInterface
     */
    public function setTelephone(string $telephone): QuickOrderInterface
    {
        return $this->setData(self::TELEPHONE, $telephone);
    }

    /**
     * @return string
     */
    public function getRequestParams(): string
    {
        return $this->getData(self::REQUEST_PARAMS);
    }

    /**
     * @param string $requestParams
     * @return QuickOrderInterface
     */
    public function setRequestParams(string $requestParams): QuickOrderInterface
    {
        return $this->setData(self::REQUEST_PARAMS, $requestParams);
    }

    /**
     * @return string
     */
    public function getCreatedAt(): string
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * @param string $createdAt
     * @return QuickOrderInterface
     */
    public function setCreatedAt(string $createdAt): QuickOrderInterface
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }
}
