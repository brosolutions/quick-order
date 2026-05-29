<?php
/**
 * Copyright (c) 2025 BroSolutions
 * All rights reserved
 */
declare(strict_types=1);

namespace BroSolutions\QuickOrder\Test\Unit\Service;

use BroSolutions\QuickOrder\Service\GetCurrencySymbol;
use Magento\Framework\Currency;
use Magento\Framework\Currency\Exception\CurrencyException;
use Magento\Framework\Locale\CurrencyInterface;
use Magento\Framework\Phrase;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GetCurrencySymbolTest extends TestCase
{
    /** @var CurrencyInterface|MockObject */
    private $localeCurrency;

    /** @var GetCurrencySymbol */
    private GetCurrencySymbol $service;

    protected function setUp(): void
    {
        $this->localeCurrency = $this->createMock(CurrencyInterface::class);
        $this->service = new GetCurrencySymbol($this->localeCurrency);
    }

    public function testReturnsSymbol(): void
    {
        $currency = $this->createMock(Currency::class);
        $currency->method('getSymbol')->willReturn('$');

        $this->localeCurrency->method('getCurrency')
            ->with('USD')
            ->willReturn($currency);

        $this->assertSame('$', $this->service->execute('USD'));
    }

    public function testReturnsEmptyStringOnCurrencyException(): void
    {
        $this->localeCurrency->method('getCurrency')
            ->willThrowException(new CurrencyException(new Phrase('unknown currency')));

        $this->assertSame('', $this->service->execute('XYZ'));
    }

    public function testForwardsCurrencyCodeToLocale(): void
    {
        $currency = $this->createMock(Currency::class);
        $currency->method('getSymbol')->willReturn('€');

        $this->localeCurrency->expects($this->once())
            ->method('getCurrency')
            ->with('EUR')
            ->willReturn($currency);

        $this->service->execute('EUR');
    }
}
