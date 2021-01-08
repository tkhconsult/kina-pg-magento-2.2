<?php
/**
 * Copyright © 2020 TkhConsult. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace TkhConsult\KinaPg\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\Payment\Model\Method\AbstractMethod;

/**
 * Class PaymentAction
 */
class PaymentAction implements ArrayInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => AbstractMethod::ACTION_ORDER,
                'label' => __('Charge (Purchase/Sale)')
            ],
//            [
//                'value' => AbstractMethod::ACTION_AUTHORIZE,
//                'label' => __('Authorize')
//            ]
        ];
    }
}
