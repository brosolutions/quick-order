<?php
/**
 * Copyright (c) 2025 BroSolutions
 * All rights reserved
 */
declare(strict_types=1);

namespace BroSolutions\QuickOrder\Test\Unit\Controller\Index;

use BroSolutions\QuickOrder\Controller\Index\Upload;
use BroSolutions\QuickOrder\Service\FileUploader;
use Exception;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Laminas\Stdlib\Parameters;

class UploadTest extends TestCase
{
    /** @var Http|MockObject */
    private $request;

    /** @var JsonFactory|MockObject */
    private $resultJsonFactory;

    /** @var FormKeyValidator|MockObject */
    private $formKeyValidator;

    /** @var FileUploader|MockObject */
    private $fileUploader;

    /** @var Json|MockObject */
    private $resultJson;

    /** @var Upload */
    private Upload $controller;

    protected function setUp(): void
    {
        $this->request = $this->createMock(Http::class);
        $this->resultJsonFactory = $this->createMock(JsonFactory::class);
        $this->formKeyValidator = $this->createMock(FormKeyValidator::class);
        $this->fileUploader = $this->createMock(FileUploader::class);
        $this->resultJson = $this->createMock(Json::class);

        $this->resultJsonFactory->method('create')->willReturn($this->resultJson);

        $this->controller = new Upload(
            $this->request,
            $this->resultJsonFactory,
            $this->formKeyValidator,
            $this->fileUploader
        );
    }

    public function testReturnsErrorOnInvalidFormKey(): void
    {
        $this->formKeyValidator->method('validate')->willReturn(false);

        $this->fileUploader->expects($this->never())->method('execute');

        $this->resultJson->expects($this->once())
            ->method('setData')
            ->with($this->callback(fn ($data) => $data['success'] === false))
            ->willReturnSelf();

        $this->controller->execute();
    }

    public function testReturnsErrorWhenNoFileUploaded(): void
    {
        $this->formKeyValidator->method('validate')->willReturn(true);
        $this->request->method('isPost')->willReturn(true);

        $files = $this->createMock(Parameters::class);
        $files->method('toArray')->willReturn([]);
        $this->request->method('getFiles')->willReturn($files);

        $this->resultJson->expects($this->once())
            ->method('setData')
            ->with($this->callback(fn ($data) => $data['success'] === false))
            ->willReturnSelf();

        $this->controller->execute();
    }

    public function testReturnsErrorOnInvalidFileName(): void
    {
        $this->formKeyValidator->method('validate')->willReturn(true);
        $this->request->method('isPost')->willReturn(true);

        $files = $this->createMock(Parameters::class);
        $files->method('toArray')->willReturn([
            'file' => ['name' => '../etc/passwd', 'error' => 0]
        ]);
        $this->request->method('getFiles')->willReturn($files);

        $this->resultJson->expects($this->once())
            ->method('setData')
            ->with($this->callback(fn ($data) => $data['success'] === false))
            ->willReturnSelf();

        $this->controller->execute();
    }

    public function testReturnsSuccessWithFilePath(): void
    {
        $this->formKeyValidator->method('validate')->willReturn(true);
        $this->request->method('isPost')->willReturn(true);

        $files = $this->createMock(Parameters::class);
        $files->method('toArray')->willReturn([
            'file' => ['name' => 'image.jpg', 'error' => 0]
        ]);
        $this->request->method('getFiles')->willReturn($files);

        $this->fileUploader->method('execute')
            ->with('image.jpg')
            ->willReturn('quickorder_files/i/1234567890/image.jpg');

        $this->resultJson->expects($this->once())
            ->method('setData')
            ->with($this->callback(
                fn ($data) => $data['success'] === true
                    && str_contains($data['file_path'], 'image.jpg')
            ))
            ->willReturnSelf();

        $this->controller->execute();
    }

    public function testReturnsErrorOnUploaderException(): void
    {
        $this->formKeyValidator->method('validate')->willReturn(true);
        $this->request->method('isPost')->willReturn(true);

        $files = $this->createMock(Parameters::class);
        $files->method('toArray')->willReturn([
            'file' => ['name' => 'file.pdf', 'error' => 0]
        ]);
        $this->request->method('getFiles')->willReturn($files);

        $this->fileUploader->method('execute')
            ->willThrowException(new Exception('Disk full'));

        $this->resultJson->expects($this->once())
            ->method('setData')
            ->with($this->callback(fn ($data) => $data['success'] === false))
            ->willReturnSelf();

        $this->controller->execute();
    }
}
