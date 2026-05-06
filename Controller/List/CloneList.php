<?php
/**
 * Copyright (c) 2026 BroSolutions
 * All rights reserved
 *
 * This product includes proprietary software developed at BroSolutions, Ukraine
 * For more information see https://www.brosolutions.net/
 *
 * To obtain a valid license for using this software please contact us at
 * contact@brosolutions.net
 */
declare(strict_types=1);

namespace BroSolutions\QuickOrder\Controller\List;

use BroSolutions\QuickOrder\Model\ProductList;
use BroSolutions\QuickOrder\Model\ResourceModel\ProductList as ProductListResource;
use BroSolutions\QuickOrder\Model\ResourceModel\ProductList\CollectionFactory;
use BroSolutions\QuickOrder\Model\ResourceModel\ProductListItem;
use Magento\Customer\Model\CustomerFactory as CustomerModelFactory;
use Magento\Customer\Model\ResourceModel\Customer as CustomerModelResource;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Class CloneList
 * Handles the cloning process for QuickOrder lists.
 *
 * @copyright  Copyright (c) 2026 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class CloneList implements HttpPostActionInterface
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var ProductListResource
     */
    private $listResource;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var RedirectFactory
     */
    private $redirectFactory;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var FormKeyValidator
     */
    private $formKeyValidator;

    /**
     * @var CustomerModelFactory
     */
    private $customerModelFactory;

    /**
     * @var CustomerModelResource
     */
    private $customerModelResource;

    /**
     * CloneList constructor.
     *
     * @param Context $context
     * @param ResourceConnection $resource
     * @param ProductListResource $listResource
     * @param CustomerSession $customerSession
     * @param RedirectFactory $redirectFactory
     * @param RequestInterface $request
     * @param CollectionFactory $collectionFactory
     * @param LoggerInterface $logger
     * @param ManagerInterface $messageManager
     * @param FormKeyValidator $formKeyValidator
     * @param CustomerModelFactory $customerModelFactory
     * @param CustomerModelResource $customerModelResource
     */
    public function __construct(
        Context $context,
        ResourceConnection $resource,
        ProductListResource $listResource,
        CustomerSession $customerSession,
        RedirectFactory $redirectFactory,
        RequestInterface $request,
        CollectionFactory $collectionFactory,
        LoggerInterface $logger,
        ManagerInterface $messageManager,
        FormKeyValidator $formKeyValidator,
        CustomerModelFactory $customerModelFactory,
        CustomerModelResource $customerModelResource
    ) {
        $this->request = $request;
        $this->resource = $resource;
        $this->listResource = $listResource;
        $this->customerSession = $customerSession;
        $this->redirectFactory = $redirectFactory;
        $this->collectionFactory = $collectionFactory;
        $this->logger = $logger;
        $this->messageManager = $messageManager;
        $this->formKeyValidator = $formKeyValidator;
        $this->customerModelFactory = $customerModelFactory;
        $this->customerModelResource = $customerModelResource;
    }

    /**
     * Execute clone action.
     *
     * @return Redirect
     */
    public function execute(): Redirect
    {
        $redirect = $this->redirectFactory->create();
        $connection = $this->resource->getConnection();
        $listId = (int)$this->request->getParam('list_id');

        try {
            if (!$this->customerSession->isLoggedIn()) {
                return $redirect->setPath('customer/account/login');
            }

            if (!$this->formKeyValidator->validate($this->request)) {
                throw new LocalizedException(__('Invalid Form Key. Please refresh the page.'));
            }

            if (!$listId) {
                throw new LocalizedException(__('Invalid list.'));
            }

            $itemTable = $this->resource->getTableName(ProductListItem::QUICK_ORDER_LIST_ITEM_TABLE);
            $collection = $this->collectionFactory->create();
            $collection->addFieldToFilter('id', $listId);

            /** @var ProductList $sourceList */
            $sourceList = $collection->getFirstItem();

            if (!$sourceList->getId()) {
                throw new LocalizedException(__('The list no longer exists.'));
            }

            $customerId = (int)$this->customerSession->getCustomerId();

            if ((int)$sourceList->getCustomerId() !== $customerId) {
                throw new LocalizedException(__('You are not allowed to clone this list.'));
            }

            $customerModel = $this->customerModelFactory->create();
            $this->customerModelResource->load($customerModel, $customerId);

            $companyId = $customerModel->getData('company_id');
            $role = $customerModel->getData('company_role');

            $approvalStatus = ($companyId && $role === 'company_user') ? 'draft' : 'approved';

            $connection->beginTransaction();

            $newList = clone $sourceList;
            $newList->setId(null);
            $newList->setListName($sourceList->getListName());
            $newList->setApprovalStatus($approvalStatus);
            $newList->setApprovalManagerId(null);
            $newList->setCreatedAt(null);
            $newList->setUpdatedAt(null);

            $this->listResource->save($newList);
            $newListId = (int)$newList->getId();

            $items = $connection->fetchAll(
                $connection->select()
                    ->from($itemTable)
                    ->where('list_id = ?', $listId)
                    ->order('id ASC')
            );

            if (!empty($items)) {
                $insertData = [];
                $idMap = [];

                foreach ($items as $item) {
                    $oldId = (int)$item['id'];
                    unset($item['id'], $item['created_at'], $item['updated_at']);
                    $item['list_id'] = $newListId;
                    $item['parent_id'] = null;
                    $insertData[] = $item;
                    $idMap[$oldId] = null;
                }

                $connection->insertMultiple($itemTable, $insertData);
                $lastInsertId = (int)$connection->lastInsertId($itemTable);
                $index = 0;

                foreach (array_keys($idMap) as $oldId) {
                    $idMap[$oldId] = $lastInsertId + $index;
                    $index++;
                }

                $updateData = [];
                foreach ($items as $item) {
                    if ($item['parent_id']) {
                        $updateData[$idMap[(int)$item['id']]] = $idMap[(int)$item['parent_id']];
                    }
                }

                if (!empty($updateData)) {
                    $cases = [];
                    $ids = [];

                    foreach ($updateData as $itemId => $parentId) {
                        $cases[] = sprintf('WHEN %d THEN %d', (int)$itemId, (int)$parentId);
                        $ids[] = (int)$itemId;
                    }

                    $sql = sprintf(
                        'UPDATE %s SET parent_id = CASE id %s END WHERE id IN (%s)',
                        $connection->quoteIdentifier($itemTable),
                        implode(' ', $cases),
                        implode(',', $ids)
                    );

                    $connection->query($sql);
                }
            }

            $connection->commit();
            $this->messageManager->addSuccessMessage(__('The list has been cloned.'));

        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (Throwable $e) {
            if (isset($connection)) {
                $connection->rollBack();
            }
            $this->logger->error(sprintf('Error cloning list: %s', $e->getMessage()), $e->getTrace());
        }

        return $redirect->setPath(
            'customer/account/productlistview',
            ['list_id' => $newListId ?? $listId]
        );
    }
}
