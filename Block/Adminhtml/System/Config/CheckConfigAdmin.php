<?php

namespace TkhConsult\KinaPg\Block\Adminhtml\System\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Store\Model\ScopeInterface;
use TkhConsult\KinaPg\Model\Ui\ConfigProvider;

class CheckConfigAdmin extends \Magento\Framework\View\Element\Template
{
    private $config;
    private $_storeId;
    private $scopeConfig;

    /**
     * SimplePathAdmin constructor.
     *
     * @param Context         $context
     * @param ConfigInterface $config
     * @param array           $data
     */
    public function __construct(
        Context $context,
        ConfigInterface $config,
        ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->config = $config;
        $this->scopeConfig   = $scopeConfig;
    }

    /**
     * Return config value based on scope and scope ID
     */
    public function getConfig($path)
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE);
    }
    /**
     * Return currency
     */
    public function getCurrency()
    {
        return $this->getConfig('currency/options/default') === ConfigProvider::SUPPORT_CURRENCY ? ConfigProvider::SUPPORT_CURRENCY : null;
    }
}
