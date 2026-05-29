<?php
/**
 * Copyright (c) 2025 BroSolutions
 * All rights reserved
 */
declare(strict_types=1);

namespace BroSolutions\QuickOrder\Test\Unit\Service;

use BroSolutions\QuickOrder\Service\GetStoreId;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GetStoreIdTest extends TestCase
{
    /** @var StoreManager|MockObject */
    private $storeManager;

    /** @var GetStoreId */
    private GetStoreId $service;

    protected function setUp(): void
    {
        $this->storeManager = $this->createMock(StoreManager::class);
        $this->service = new GetStoreId($this->storeManager);
    }

    public function testReturnsStoreId(): void
    {
        $store = $this->createMock(Store::class);
        $store->method('getId')->willReturn('2');

        $this->storeManager->method('getStore')
            ->with('en')
            ->willReturn($store);

        $this->assertSame(2, $this->service->execute('en'));
    }

    public function testReturnsZeroWhenStoreNotFound(): void
    {
        $this->storeManager->method('getStore')
            ->willThrowException(new NoSuchEntityException());

        $this->assertSame(0, $this->service->execute('nonexistent'));
    }

    public function testCastsIdToInt(): void
    {
        $store = $this->createMock(Store::class);
        $store->method('getId')->willReturn('5');

        $this->storeManager->method('getStore')->willReturn($store);

        $result = $this->service->execute('default');

        $this->assertIsInt($result);
    }
}
