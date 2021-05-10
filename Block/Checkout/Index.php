<?php

namespace TkhConsult\KinaPg\Block\Checkout;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use TkhConsult\KinaPg\Helper\Gateway\Loader;

class Index extends Template
{
    protected $loader;
    protected $orderRepository;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    public function __construct(
        Template\Context $context,
        ScopeConfigInterface $scopeConfig,
        OrderRepositoryInterface $orderRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->orderRepository = $orderRepository;
        $this->loader = new Loader($this->getUrl('kinabank/checkout/pay', ['_query' => $this->getRequest()->getParams()]), $this->getRequest());
        $this->scopeConfig = $scopeConfig;
    }

    public function getForm() {
        $data = $this->loader->getPaymentData();
        $order = $this->getOrder();
        $kinaBankGateway = $this->loader->initPaymentGateway();
        $kinaBankGateway->requestAuthorization(
            $order['id'],
            $order['amount'],
            $data['backRefUrl'],
            $order['currency'],
            $order['description'],
            $order['email'],
            $data['lang']);
    }

    public function getOrder() {
        /** @var Order $order */
        $order = $this->orderRepository->get($this->getRequest()->getParam('orderId'));
        $address = $order->getShippingAddress()->getData();
        $data =  [
            'id' => $order->getId(),
            'amount' => $order->getTotalDue(),
            'description' => 'Order #' . $order->getId(),
            'currency' => $order->getOrderCurrencyCode(),
            'email' => $order->getCustomerEmail(),
            'country' => $address['country_id'],
            'title' => $this->loader->getConfigData('title'),
        ];

        return $data;
    }
}
