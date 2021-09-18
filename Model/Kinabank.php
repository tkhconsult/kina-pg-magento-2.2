<?php

namespace TkhConsult\KinaPg\Model;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\Adapter;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction;
use TkhConsult\KinaBankGateway\KinaBank\Response;
use TkhConsult\KinaPg\Helper\Gateway\Loader;
use TkhConsult\KinaPg\Logger\Logger;
use TkhConsult\KinaPg\Model\Ui\ConfigProvider;

class Kinabank extends Adapter {
    /**
     * @param string $paymentAction
     * @param DataObject $stateObject
     * @return Adapter|\Magento\Payment\Model\MethodInterface
     */
    public function initialize($paymentAction, $stateObject)
    {
        $stateObject->setData('state', Order::STATE_PENDING_PAYMENT);
        $stateObject->setData('status', Order::STATE_PROCESSING);

        return $this;
    }

    public function capture(InfoInterface $payment, $amount)
    {
        return $this;
    }
}
