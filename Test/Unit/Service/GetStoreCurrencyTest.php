<?php
/**
 * Copyright (c) 2025 BroSolutions
 * All rights reserved
 */
declare(strict_types=1);

namespace BroSolutions\QuickOrder\Test\Unit\Service;

use BroSolutions\QuickOrder\Service\GetStoreCurrency;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GetStoreCurrencyTest extends TestCase
{
    /** @var StoreManager|MockObject */
    private $storeManager;

    /** @var GetStoreCurrency */
    private GetStoreCurrency $service;

    protected function setUp(): void
    {
        $this->storeManager = $this->createMock(StoreManager::class);
        $this->service = new GetStoreCurrency($this->storeManager);
    }

    public function testReturnsCurrencyCode(): void
    {
        $store = $this->createMock(Store::class);
        $store->method('getCurrentCurrencyCode')->willReturn('USD');

        $this->storeManager->method('getStore')
            ->with('default')
            ->willReturn($store);

        $this->assertSame('USD', $this->service->execute('default'));
    }

    public function testReturnsEmptyStringWhenStoreNotFound(): void
    {
        $this->storeManager->method('getStore')
            ->willThrowException(new NoSuchEntityException());

        $this->assertSame('', $this->service->execute('nonexistent'));
    }

    public function testForwardsStoreCodeToStoreManager(): void
    {
        $store = $this->createMock(Store::class);
        $store->method('getCurrentCurrencyCode')->willReturn('EUR');

        $this->storeManager->expects($this->once())
            ->method('getStore')
            ->with('de')
            ->willReturn($store);

        $this->service->execute('de');
    }
}
