<?php

namespace TkhConsult\KinaPg\Block\Adminhtml\System\Config\Form;

use Magento\Framework\Data\Form\Element\AbstractElement;
use TkhConsult\KinaPg\Block\Adminhtml\System\Config\CheckConfigAdmin;

class CheckConfig extends \Magento\Config\Block\System\Config\Form\Field
{

    /**
     * Render element value
     *
     * @param AbstractElement $element
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function render(AbstractElement $element)
    {
        $html = $this->_layout
            ->createBlock(CheckConfigAdmin::class)
            ->setTemplate('TkhConsult_KinaPg::system/config/checkconfig_admin.phtml')
            ->setCacheable(false)
            ->toHtml();

        return $html;
    }
}
