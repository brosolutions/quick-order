<?php
/**
 * Copyright (c) 2025 BroSolutions
 * All rights reserved
 */
declare(strict_types=1);

namespace BroSolutions\QuickOrder\Test\Unit\Service;

use BroSolutions\QuickOrder\Service\GetQuickOrderEnable;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GetQuickOrderEnableTest extends TestCase
{
    private const CONFIG_PATH = 'brosolution_quick_order/general/enable';

    /** @var ScopeConfigInterface|MockObject */
    private $scopeConfig;

    /** @var GetQuickOrderEnable */
    private GetQuickOrderEnable $service;

    protected function setUp(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->service = new GetQuickOrderEnable($this->scopeConfig);
    }

    public function testReturnsTrueWhenEnabled(): void
    {
        $this->scopeConfig->method('getValue')
            ->with(self::CONFIG_PATH, ScopeInterface::SCOPE_STORE)
            ->willReturn('1');

        $this->assertTrue($this->service->execute());
    }

    public function testReturnsFalseWhenDisabled(): void
    {
        $this->scopeConfig->method('getValue')
            ->with(self::CONFIG_PATH, ScopeInterface::SCOPE_STORE)
            ->willReturn('0');

        $this->assertFalse($this->service->execute());
    }

    public function testReturnsFalseWhenNull(): void
    {
        $this->scopeConfig->method('getValue')
            ->willReturn(null);

        $this->assertFalse($this->service->execute());
    }
}
