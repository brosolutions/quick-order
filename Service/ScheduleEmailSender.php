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

namespace BroSolutions\QuickOrder\Service;

use Exception;
use Magento\Framework\App\Area;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Psr\Log\LoggerInterface;

/**
 * Service responsible for sending automated schedule email notifications.
 *
 * @copyright  Copyright (c) 2026 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class ScheduleEmailSender
{
    /**
     * @var TransportBuilder
     */
    private $transportBuilder;

    /**
     * @var StateInterface
     */
    private $inlineTranslation;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor.
     *
     * @param TransportBuilder $transportBuilder
     * @param StateInterface $inlineTranslation
     * @param LoggerInterface $logger
     */
    public function __construct(
        TransportBuilder $transportBuilder,
        StateInterface $inlineTranslation,
        LoggerInterface $logger
    ) {
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->logger = $logger;
    }

    /**
     * Send success email notification.
     *
     * @param string $customerEmail
     * @param string $customerName
     * @param string $scheduleName
     * @param string $orderIncrementId
     * @param int $storeId
     * @return void
     */
    public function sendSuccessEmail(
        string $customerEmail,
        string $customerName,
        string $scheduleName,
        string $orderIncrementId,
        int $storeId
    ): void {
        $templateVars = [
            'customerName' => $customerName,
            'scheduleName' => $scheduleName,
            'orderId'      => $orderIncrementId
        ];

        $this->sendEmail(
            'brosolutions_quickorder_schedule_success',
            $customerEmail,
            $customerName,
            $templateVars,
            $storeId
        );
    }

    /**
     * Send error email notification.
     *
     * @param string $customerEmail
     * @param string $customerName
     * @param string $scheduleName
     * @param string $errorMessage
     * @param int $storeId
     * @return void
     */
    public function sendErrorEmail(
        string $customerEmail,
        string $customerName,
        string $scheduleName,
        string $errorMessage,
        int $storeId
    ): void {
        $templateVars = [
            'customerName' => $customerName,
            'scheduleName' => $scheduleName,
            'errorMessage' => $errorMessage
        ];

        $this->sendEmail(
            'brosolutions_quickorder_schedule_error',
            $customerEmail,
            $customerName,
            $templateVars,
            $storeId
        );
    }

    /**
     * Send price change email notification.
     *
     * @param string $customerEmail
     * @param string $customerName
     * @param string $scheduleId
     * @param string $changesText
     * @param int $storeId
     * @return void
     */
    public function sendPriceChangeEmail(
        string $customerEmail,
        string $customerName,
        string $scheduleId,
        string $changesText,
        int $storeId
    ): void {
        $templateVars = [
            'customerName' => $customerName,
            'scheduleId'   => $scheduleId,
            'changesText'  => $changesText
        ];

        $this->sendEmail(
            'brosolutions_quickorder_price_change',
            $customerEmail,
            $customerName,
            $templateVars,
            $storeId
        );
    }

    /**
     * Execute the email sending process.
     *
     * @param string $templateId
     * @param string $email
     * @param string $name
     * @param array $templateVars
     * @param int $storeId
     * @return void
     */
    private function sendEmail(
        string $templateId,
        string $email,
        string $name,
        array $templateVars,
        int $storeId
    ): void {
        try {
            $this->inlineTranslation->suspend();

            $transport = $this->transportBuilder
                ->setTemplateIdentifier($templateId)
                ->setTemplateOptions([
                    'area' => Area::AREA_FRONTEND,
                    'store' => $storeId
                ])
                ->setTemplateVars($templateVars)
                ->setFromByScope('sales', $storeId)
                ->addTo($email, $name)
                ->getTransport();

            $transport->sendMessage();
        } catch (Exception $e) {
            $this->logger->error('Failed to send schedule notification email: ' . $e->getMessage());
        } finally {
            $this->inlineTranslation->resume();
        }
    }
}
