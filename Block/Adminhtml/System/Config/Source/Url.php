<?php

namespace TkhConsult\KinaPg\Block\Adminhtml\System\Config\Source;

use TkhConsult\KinaPg\Helper\Gateway\Loader;

/**
 * @api
 * @since 100.0.2
 */
class Url implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [['value' => 'test', 'label' => Loader::TEST_URL], ['value' => 'prod', 'label' =>  Loader::PROD_URL]];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return ['test' => Loader::TEST_URL, 'prod' =>  Loader::PROD_URL];
    }
}
