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

use BroSolutions\QuickOrder\Service\GetQuickOrderEnable;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\Controller\Result\Forward;

/**
 * @copyright  Copyright (c) 2025 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class Index implements HttpGetActionInterface
{
    /**
     * @var PageFactory
     */
    private $pageFactory;

    /**
     * @var GetQuickOrderEnable
     */
    private $getQuickOrderEnable;

    /**
     * @var ForwardFactory
     */
    private $forwardFactory;

    /**
     * @param PageFactory $pageFactory
     * @param GetQuickOrderEnable $getQuickOrderEnable
     * @param ForwardFactory $forwardFactory
     */
    public function __construct(
        PageFactory $pageFactory,
        GetQuickOrderEnable $getQuickOrderEnable,
        ForwardFactory $forwardFactory
    ) {
        $this->pageFactory = $pageFactory;
        $this->getQuickOrderEnable = $getQuickOrderEnable;
        $this->forwardFactory = $forwardFactory;
    }

    /**
     * Order controller
     *
     * @return Page|Forward
     */
    public function execute(): Page|Forward
    {
        if (!$this->getQuickOrderEnable->execute()) {
            $resultForward = $this->forwardFactory->create();
            return $resultForward->forward('noroute');
        }
        return $this->pageFactory->create();
    }
}
