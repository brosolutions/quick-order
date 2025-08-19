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

namespace BroSolutions\QuickOrder\Controller\Adminhtml\Index;

use BroSolutions\QuickOrder\Model\QuickOrderRepository;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * @copyright  Copyright (c) 2025 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class Delete extends Action
{
    /**
     * @var QuickOrderRepository
     */
    private $quickOrderRepository;

    /**
     * @var RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * @param Context $context
     * @param QuickOrderRepository $quickOrderRepository
     * @param RedirectFactory $resultRedirectFactory
     */
    public function __construct(
        Context $context,
        QuickOrderRepository $quickOrderRepository,
        RedirectFactory $resultRedirectFactory
    ) {
        parent::__construct($context);
        $this->quickOrderRepository = $quickOrderRepository;
        $this->resultRedirectFactory = $resultRedirectFactory;
    }

    /**
     * @return Redirect
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function execute(): Redirect
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $entityId = $this->_request->getParam('entity_id');
        $this->quickOrderRepository->deleteById($entityId);
        $resultRedirect->setPath('quickorder/index/index');
        return $resultRedirect;

    }
}
