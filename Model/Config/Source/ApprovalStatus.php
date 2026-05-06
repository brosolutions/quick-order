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

namespace BroSolutions\QuickOrder\Model\Config\Source;

use BroSolutions\QuickOrder\Api\ApprovalStatusInterface;

/**
 * Class ApprovalStatus
 * Provides human-readable labels for QuickOrder approval statuses.
 */
class ApprovalStatus
{
    /**
     * Get options array.
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => ApprovalStatusInterface::STATUS_DRAFT, 'label' => __('Draft')],
            ['value' => ApprovalStatusInterface::STATUS_PENDING_APPROVAL, 'label' => __('Pending Approval')],
            ['value' => ApprovalStatusInterface::STATUS_APPROVED, 'label' => __('Approved')],
            ['value' => ApprovalStatusInterface::STATUS_CHANGES_REQUESTED, 'label' => __('Changes Requested')],
            ['value' => ApprovalStatusInterface::STATUS_ORDERED, 'label' => __('Ordered')]
        ];
    }

    /**
     * Get translated label by status code.
     *
     * @param string $value
     * @return string
     */
    public function getLabel(string $value): string
    {
        foreach ($this->toOptionArray() as $option) {
            if ($option['value'] === $value) {
                return (string)$option['label'];
            }
        }

        // Fallback if status is unknown
        return ucfirst(str_replace('_', ' ', $value));
    }
}
