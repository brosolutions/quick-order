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

namespace BroSolutions\QuickOrder\Model;

use Exception;
use BroSolutions\QuickOrder\Api\CartManagementInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Checkout\Model\Session;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Checkout\Model\Cart;

/**
 * @copyright  Copyright (c) 2025 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class CartManagement implements CartManagementInterface
{
    /**
     * @var Session
     */
    private $session;

    /**
     * @var Data
     */
    private $dataHelper;

    /**
     * @var Cart
     */
    private $cart;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var FormKey
     */
    private $formKey;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var ManagerInterface
     */
    private $_eventManager;

    /**
     * @var Json
     */
    private $json;

    /**
     * @param Session $session
     * @param Data $dataHelper
     * @param ProductRepositoryInterface $productRepository
     * @param LoggerInterface $logger
     * @param FormKey $formKey
     * @param CartRepositoryInterface $cartRepository
     * @param ManagerInterface $eventManager
     */
    public function __construct(
        Session                    $session,
        ProductRepositoryInterface $productRepository,
        LoggerInterface            $logger,
        FormKey                    $formKey,
        CartRepositoryInterface    $cartRepository,
        ManagerInterface           $eventManager,
        Json $json
    ) {
        $this->session = $session;
        $this->productRepository = $productRepository;
        $this->logger = $logger;
        $this->formKey = $formKey;
        $this->cartRepository = $cartRepository;
        $this->_eventManager = $eventManager;
        $this->json = $json;
    }

    /**
     * Add to cart
     *
     * @param string $params
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function addToCart(string $params): void
    {

        $params = $this->json->unserialize($params);
        $this->cart = $this->session->getQuote();

        if (!empty($params)) {
            foreach ($params as $param) {
                switch ($param['type_id']) {
                    case 'simple':
                    case 'downloadable':
                        $this->simpleAddToCart($param);
                        break;
                    case 'configurable':
                        $this->configurableAddToCart($param);
                        break;
                    case 'bundle':
                        $this->bundleAddToCart($param);
                        break;
                    case 'grouped':
                        $this->groupedAddToCart($param);
                        break;
                    default:
                        break;
                }
            }
        }

        $this->cartRepository->save($this->cart);
    }

    /**
     * Add simple product to cart
     *
     * @param array $item
     * @return void
     */
    private function simpleAddToCart(array $item): void
    {
        try {
            $product = $this->productRepository->get($item['sku']);
            $request = [];
            $request['qty'] = $item['qty'];
            $request['product'] = $product['entity_id'];
            $request['item'] = $product['entity_id'];
            $request['form_key'] = $this->formKey->getFormKey();
            $request['options'] = $this->getOptions($item);
            $requestObj = new DataObject();
            $requestObj->setData($request);
            $this->cart->addProduct($product, $requestObj);
        } catch (Exception $e) {
            $this->logger->error(
                sprintf(
                    'It is not possible to add simple/virtual product with sku %s to cart. Original error: %s',
                    $item['sku'],
                    $e->getMessage()
                ),
                $e->getTrace()
            );
        }
    }

    /**
     * Add configurable product to cart
     *
     * @param array $item
     * @return void
     */
    private function configurableAddToCart(array $item): void
    {
        $activeProduct = $item['active_product'];

        try {
            $product = $this->productRepository->get($item['sku']);
            $request = [];
            $request['qty'] = $item['qty'];
            $request['form_key'] = $this->formKey->getFormKey();
            $request['options'] = $this->getOptions($item);
            $superAttributes = [];
            foreach ($item['attributes'] as $attribute) {
                $superAttributes[$attribute['attribute_id']] = $activeProduct[$attribute['attribute_code']];
            }
            $request['super_attribute'] = $superAttributes;
            $request['product'] = $activeProduct['entity_id'];
            $request['item'] = $activeProduct['entity_id'];

            $requestObj = new DataObject();
            $requestObj->setData($request);

            $this->_eventManager->dispatch(
                'checkout_cart_product_add_before',
                ['info' => $requestObj, 'product' => $product]
            );
            $this->cart->addProduct($product, $requestObj);
        } catch (Exception $e) {
            $this->logger->error(
                sprintf(
                    'It is not possible to add configurable  product with sku %s to cart. Original error: %s',
                    $item['sku'],
                    $e->getMessage()
                ),
                $e->getTrace()
            );
        }
    }

    /**
     * Add bundle product to cart
     *
     * @param array $item
     * @return void
     */
    private function bundleAddToCart(array $item): void
    {
        $activeSelections = $item['active_selections'];

        try {
            $product = $this->productRepository->get($item['sku']);
            $request = [];
            $request['qty'] = $item['qty'];
            $request['form_key'] = $this->formKey->getFormKey();
            $request['bundle_option'] = $this->getBundleOption($activeSelections)['bundleOptions'];
            $request['bundle_option_qty'] = $this->getBundleOption($activeSelections)['qty'];

            $request['product'] = $product['entity_id'];
            $request['item'] = $product['entity_id'];

            $requestObj = new DataObject();
            $requestObj->setData($request);

            $this->_eventManager->dispatch(
                'checkout_cart_product_add_before',
                ['info' => $requestObj, 'product' => $product]
            );
            $this->cart->addProduct($product, $requestObj);
        } catch (Exception $e) {
            $this->logger->error(
                sprintf(
                    'It is not possible to add bundle product with sku %s to cart. Original error: %s',
                    $item['sku'],
                    $e->getMessage()
                ),
                $e->getTrace()
            );
        }
    }

    /**
     * Add grouped product to cart
     *
     * @param array $item
     * @return void
     */
    private function groupedAddToCart(array $item): void
    {
        $quickGroupedProducts = $item['quick_grouped_products'];
        $activeSelections = $item['active_selections'];
        try {
            foreach ($quickGroupedProducts as $productItem) {
                $product = $this->productRepository->get($productItem['sku']);
                $request = [];

                $filterBy = $productItem['entity_id'];
                $filteredProductData = array_filter($activeSelections, function ($var) use ($filterBy) {
                    return ($var['id'] == $filterBy);
                });
                $qty = 0;
                foreach ($filteredProductData as $filteredProduct) {
                    $qty = (int)$filteredProduct['qty'];
                }

                if ($qty > 0 && $product) {
                    $requestObj = new DataObject();
                    $request['qty'] = $qty;
                    $requestObj->setData($request);

                    $this->_eventManager->dispatch(
                        'checkout_cart_product_add_before',
                        ['info' => $requestObj, 'product' => $product]
                    );
                    $this->cart->addProduct($product, $requestObj);
                }
            }
        } catch (Exception $e) {
            $this->logger->error(
                sprintf(
                    'It is not possible to add grouped product with sku %s to cart. Original error: %s',
                    $item['sku'],
                    $e->getMessage()
                ),
                $e->getTrace()
            );
        }
    }

    /**
     * Get options for product
     *
     * @param array $item
     * @return array
     */
    private function getOptions(array $item): array
    {
        $innerOptions = [];
        foreach ($item['active_custom_options'] as $option) {
            if (!is_array($option['option_value'])) {
                if ($option['option_type'] === 'file') {
                    continue;
                }
                $innerOptions[$option['option_id']] = $option['option_value'];
            } else {
                $innerOptionsTemp = [];
                foreach ($option['option_value'] as $optionInner) {
                    if ($optionInner['value'] === false) {
                        continue;
                    }
                    if ($optionInner['value'] === true) {
                        $innerOptionsTemp[] = $optionInner['value_id'];
                    } else {
                        $innerOptionsTemp[$optionInner['value_id']] = $optionInner['value'];
                    }
                }

                $innerOptions[$option['option_id']] = $innerOptionsTemp;
            }
        }

        return $innerOptions;
    }

    /**
     * Get bundle options
     *
     * @param array $items
     * @return array
     */
    private function getBundleOption(array $items): array
    {
        $data = [];
        $bundleOptionsTemp = [];
        $bundleQtyTemp = [];

        foreach ($items as $item) {
            $itemTmp = [];
            foreach ($item['selection_value'] as $internalItem) {
                if ($internalItem['value'] === false) {
                    continue;
                }
                $itemTmp[$item['id']][] = $internalItem['value_id'];
                if (!empty($internalItem['change_qty']) && $internalItem['change_qty'] !== '0') {
                    $bundleQtyTemp[$item['id']] = $internalItem['qty'];
                }
            }

            if ($this->checkIfArrayHasOneRecord($itemTmp)) {
                $keyTmp = array_key_first($itemTmp);
                $valueTmp = $itemTmp[$keyTmp];
                $bundleOptionsTemp[$keyTmp] = $valueTmp[0];
            } else {
                foreach ($itemTmp as $key => $itemTmpInner) {
                    $itemTmpTmp = [];
                    foreach ($itemTmpInner as $itemTmpInnerTmp) {
                        $itemTmpTmp[$itemTmpInnerTmp] = $itemTmpInnerTmp;
                    }
                    $bundleOptionsTemp[$key] = $itemTmpTmp;
                }
            }
        }

        $data ['bundleOptions'] = $bundleOptionsTemp;
        $data ['qty'] = $bundleQtyTemp;
        return $data;
    }

    /**
     *  Check if array has one record
     *
     * @param array $itemTmp
     * @return bool
     */
    private function checkIfArrayHasOneRecord(array $itemTmp): bool
    {
        $key = array_key_first($itemTmp);
        $value = $itemTmp[$key];
        if (count($value) === 1) {
            return true;
        }

        return false;
    }
}
