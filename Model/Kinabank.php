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

    /**
     * @inheritdoc
     */
    public function refund(InfoInterface $payment, $amount)
    {
        /** @var \Magento\Sales\Model\Order\Payment\Interceptor $payment */
        $loader = new Loader('');
        $objectManager = ObjectManager::getInstance();
        /** @var Logger $logger */
        $logger = $objectManager->create(Logger::class);
        $kinaBankGateway = $loader->initPaymentGateway();
        try {
            $bankParams = $kinaBankGateway->requestRefund($payment->getOrder()->getId(), $amount, $payment->getAdditionalInformation(Response::RRN), $payment->getAdditionalInformation(Response::INT_REF), $payment->getAdditionalInformation(Response::CURRENCY));
            $message = sprintf('Refund of %1$s %2$s via %3$s approved: %4$s', $amount, $payment->getOrder()->getOrderCurrencyCode(), ConfigProvider::CODE, http_build_query($bankParams));
            $logger->info($message);

            if (isset($bankParams)) {
                $info = $payment->getOrder()->getPayment();
                foreach ($bankParams as $key => $val) {
                    $info->setAdditionalInformation($key, $val);
                }
                $info->save();

                $payment->setTransactionId(htmlentities($bankParams['INT_REF']));
                $payment->setTransactionAdditionalInfo(Transaction::RAW_DETAILS, $bankParams);
                $payment->save();
            }
        } catch (\Exception $ex) {
            $message = sprintf('Refund of %1$s %2$s via %3$s failed: %4$s', $amount, $payment->getOrder()->getOrderCurrencyCode(), ConfigProvider::CODE, $ex->getMessage());
            $logger->error($message);

            throw $ex;
        }
        return $this;
    }

    public function capture(InfoInterface $payment, $amount)
    {
        return $this;
    }
}
