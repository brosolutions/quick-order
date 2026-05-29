<?php
/**
 * Copyright (c) 2025 BroSolutions
 * All rights reserved
 */
declare(strict_types=1);

namespace BroSolutions\QuickOrder\Test\Unit\Service;

use BroSolutions\QuickOrder\Service\ConvertCurrency;
use Magento\Directory\Model\Currency;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConvertCurrencyTest extends TestCase
{
    /** @var StoreManager|MockObject */
    private $storeManager;

    /** @var CurrencyFactory|MockObject */
    private $currencyFactory;

    /** @var ConvertCurrency */
    private ConvertCurrency $service;

    protected function setUp(): void
    {
        $this->storeManager = $this->createMock(StoreManager::class);
        $this->currencyFactory = $this->createMock(CurrencyFactory::class);
        $this->service = new ConvertCurrency($this->storeManager, $this->currencyFactory);
    }

    public function testConvertsAmountToTargetCurrency(): void
    {
        $store = $this->createMock(Store::class);
        $store->method('getBaseCurrencyCode')->willReturn('USD');
        $this->storeManager->method('getStore')->willReturn($store);

        $currency = $this->createMock(Currency::class);
        $currency->method('load')->willReturnSelf();
        $currency->method('convert')->with('100', 'EUR')->willReturn(91.5);
        $this->currencyFactory->method('create')->willReturn($currency);

        $result = $this->service->execute('100', 'EUR');

        $this->assertSame(91.5, $result);
    }

    public function testReturnsZeroOnNoSuchEntityException(): void
    {
        $this->storeManager->method('getStore')
            ->willThrowException(new NoSuchEntityException());

        $result = $this->service->execute('100', 'EUR');

        $this->assertSame(0.0, $result);
    }

    public function testLoadsCurrencyFromBaseCurrencyCode(): void
    {
        $store = $this->createMock(Store::class);
        $store->method('getBaseCurrencyCode')->willReturn('GBP');
        $this->storeManager->method('getStore')->willReturn($store);

        $currency = $this->createMock(Currency::class);
        $currency->expects($this->once())
            ->method('load')
            ->with('GBP')
            ->willReturnSelf();
        $currency->method('convert')->willReturn(0.0);
        $this->currencyFactory->method('create')->willReturn($currency);

        $this->service->execute('50', 'USD');
    }
}
