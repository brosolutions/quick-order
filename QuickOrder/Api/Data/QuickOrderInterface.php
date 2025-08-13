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

namespace BroSolutions\QuickOrder\Api\Data;

interface QuickOrderInterface
{
    public const ID = 'entity_id';

    public const NAME = 'name';

    public const CREATED_AT = 'created_at';

    public const PRODUCT_ID = 'product_id';

    public const REQUEST_PARAMS = 'request_params';

    public const TELEPHONE = 'telephone';

    public function getId(): int|null;
    public function setId($id): QuickOrderInterface;

    public function getProductId(): int;
    public function setProductId(int $productId): QuickOrderInterface;

    public function getTelephone(): string;
    public function setTelephone(string $telephone): QuickOrderInterface;

    public function getRequestParams(): string;
    public function setRequestParams(string $requestParams): QuickOrderInterface;

    public function getCreatedAt(): string;
    public function setCreatedAt(string $createdAt): QuickOrderInterface;
}
