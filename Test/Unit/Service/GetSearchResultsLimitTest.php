<?php
/**
 * Copyright (c) 2025 BroSolutions
 * All rights reserved
 */
declare(strict_types=1);

namespace BroSolutions\QuickOrder\Test\Unit\Service;

use BroSolutions\QuickOrder\Service\GetSearchResultsLimit;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GetSearchResultsLimitTest extends TestCase
{
    private const CONFIG_PATH = 'brosolution_quick_order/general/search_results_limit';

    /** @var ScopeConfigInterface|MockObject */
    private $scopeConfig;

    /** @var GetSearchResultsLimit */
    private GetSearchResultsLimit $service;

    protected function setUp(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->service = new GetSearchResultsLimit($this->scopeConfig);
    }

    public function testReturnsConfiguredLimit(): void
    {
        $this->scopeConfig->method('getValue')
            ->with(self::CONFIG_PATH, ScopeInterface::SCOPE_STORE)
            ->willReturn('10');

        $this->assertSame(10, $this->service->execute());
    }

    public function testReturnsZeroWhenNotConfigured(): void
    {
        $this->scopeConfig->method('getValue')
            ->willReturn(null);

        $this->assertSame(0, $this->service->execute());
    }

    public function testCastsStringToInt(): void
    {
        $this->scopeConfig->method('getValue')
            ->willReturn('25');

        $result = $this->service->execute();

        $this->assertIsInt($result);
        $this->assertSame(25, $result);
    }
}
