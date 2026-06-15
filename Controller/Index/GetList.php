<?php
/**
 * Copyright (c) 2025 BroSolutions
 * All rights reserved
 *
 * This product includes proprietary software developed at BroSolutions, Ukraine
 * For more information see https://www.brosolutions.net/
 *
 * To obtain a valid license for using this software please contact us at
 * contact@brosolutions.net
 */
declare(strict_types=1);

namespace BroSolutions\QuickOrder\Controller\Index;

use BroSolutions\QuickOrder\Model\GetListManagement;
use Exception;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * @copyright  Copyright (c) 2025 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class GetList implements HttpPostActionInterface
{
    /**
     * @var Http
     */
    private $request;

    /**
     * @var FormKeyValidator
     */
    private $formKeyValidator;

    /**
     * @var GetListManagement
     */
    private $getListManagement;

    /**
     * @var FileFactory
     */
    private $fileFactory;

    /**
     * @param Context $context
     * @param Http $request
     * @param FormKeyValidator $formKeyValidator
     * @param GetListManagement $getListManagement
     * @param FileFactory $fileFactory
     * @param SerializerInterface $serializer
     */
    public function __construct(
        Context         $context,
        Http             $request,
        FormKeyValidator $formKeyValidator,
        GetListManagement   $getListManagement,
        FileFactory $fileFactory,
        SerializerInterface $serializer
    ) {
        $this->request = $request;
        $this->formKeyValidator = $formKeyValidator;
        $this->getListManagement = $getListManagement;
        $this->fileFactory = $fileFactory;
    }

    /**
     * @return ResponseInterface|null
     */
    public function execute(): ?ResponseInterface
    {
        if (!$this->formKeyValidator->validate($this->request)) {
            return null;
        }

        try {
            $data = $this->getListManagement->getList($this->request->getParam('jsonData'));

            return $this->fileFactory->create($data['csvFileName'], $data['content'], $data['baseDir']);
        } catch (Exception | LocalizedException| NoSuchEntityException $e) {

            return null;
        }
    }
}
