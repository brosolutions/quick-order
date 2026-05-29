<?php
/**
 * Copyright (c) 2025 BroSolutions
 * All rights reserved
 */
declare(strict_types=1);

namespace BroSolutions\QuickOrder\Test\Unit\Controller\Index;

use BroSolutions\QuickOrder\Controller\Index\AddToCart;
use BroSolutions\QuickOrder\Model\CartManagement;
use Exception;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AddToCartTest extends TestCase
{
    /** @var Http|MockObject */
    private $request;

    /** @var JsonFactory|MockObject */
    private $resultJsonFactory;

    /** @var FormKeyValidator|MockObject */
    private $formKeyValidator;

    /** @var CartManagement|MockObject */
    private $cartManagement;

    /** @var Json|MockObject */
    private $resultJson;

    /** @var AddToCart */
    private AddToCart $controller;

    protected function setUp(): void
    {
        $this->request = $this->createMock(Http::class);
        $this->resultJsonFactory = $this->createMock(JsonFactory::class);
        $this->formKeyValidator = $this->createMock(FormKeyValidator::class);
        $this->cartManagement = $this->createMock(CartManagement::class);
        $this->resultJson = $this->createMock(Json::class);

        $this->resultJsonFactory->method('create')->willReturn($this->resultJson);

        $this->controller = new AddToCart(
            $this->request,
            $this->resultJsonFactory,
            $this->formKeyValidator,
            $this->cartManagement
        );
    }

    public function testReturnsErrorOnInvalidFormKey(): void
    {
        $this->formKeyValidator->method('validate')->willReturn(false);

        $this->cartManagement->expects($this->never())->method('addToCart');

        $this->resultJson->expects($this->once())
            ->method('setData')
            ->with($this->callback(fn ($data) => $data['success'] === false))
            ->willReturnSelf();

        $this->controller->execute();
    }

    public function testReturnsSuccessOnValidRequest(): void
    {
        $this->formKeyValidator->method('validate')->willReturn(true);
        $this->request->method('getParam')->with('jsonData')->willReturn('[{"sku":"ABC-123"}]');

        $this->cartManagement->expects($this->once())->method('addToCart');

        $this->resultJson->expects($this->once())
            ->method('setData')
            ->with(['success' => true])
            ->willReturnSelf();

        $this->controller->execute();
    }

    public function testReturnsErrorOnCartManagementException(): void
    {
        $this->formKeyValidator->method('validate')->willReturn(true);
        $this->request->method('getParam')->willReturn('[]');

        $this->cartManagement->method('addToCart')
            ->willThrowException(new Exception('Out of stock'));

        $this->resultJson->expects($this->once())
            ->method('setData')
            ->with($this->callback(
                fn ($data) => $data['success'] === false && $data['message'] === 'Out of stock'
            ))
            ->willReturnSelf();

        $this->controller->execute();
    }
}
