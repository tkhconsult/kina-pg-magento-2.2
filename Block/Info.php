<?php
/**
 * Copyright © 2020 TkhConsult. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace TkhConsult\KinaPg\Block;

use Magento\Framework\Phrase;
use Magento\Payment\Block\ConfigurableInfo;
use TkhConsult\KinaPg\Gateway\Response\FraudHandler;

class Info extends ConfigurableInfo
{
    /**
     * Returns label
     *
     * @param string $field
     * @return Phrase
     */
    protected function getLabel($field)
    {
        return __($field);
    }

    /**
     * Returns value view
     *
     * @param string $field
     * @param string $value
     * @return string | Phrase
     */
    protected function getValueView($field, $value)
    {
        switch ($field) {
            case FraudHandler::FRAUD_MSG_LIST:
                return implode('; ', $value);
        }
        return parent::getValueView($field, $value);
    }
}
