<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace TkhConsult\KinaPg\Model\Config\Source;

/**
 * @api
 * @since 100.0.2
 */
class PaymentPageType implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [['value' => 'embedded', 'label' => __('Embedded Payment Page')], ['value' => 'hosted', 'label' => __('Hosted Payment Page')]];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return ['embedded' => __('Embedded Payment Page'), 'hosted' => __('Hosted Payment Page')];
    }
}
