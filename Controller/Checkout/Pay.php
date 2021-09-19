<?php
namespace TkhConsult\KinaPg\Controller\Checkout;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction;
use Psr\Log\LoggerInterface;
use TkhConsult\KinaBankGateway\KinaBank\Exception;
use TkhConsult\KinaBankGateway\KinaBank\Response;
use TkhConsult\KinaBankGateway\KinaBankGateway;
use TkhConsult\KinaPg\Helper\Gateway\Loader;
use TkhConsult\KinaPg\Logger\Logger;

class Pay extends Action
{
    protected $loader;
    protected $methodTitle;
    protected $_pageFactory;
    /** @var OrderRepositoryInterface  */
    protected $orderRepository;
    protected $_logger;

    public function __construct(
        Context $context,
        PageFactory $pageFactory
    ) {
        parent::__construct($context);
        $this->_logger = $context->getObjectManager()->create(Logger::class);
        $this->_pageFactory = $pageFactory;
        $this->orderRepository = $context->getObjectManager()->create(OrderRepositoryInterface::class);
        $this->loader = new Loader($this->_url->getUrl('kinabank/checkout/pay', ['_query' => $this->getRequest()->getParams()]), $this->getRequest());
        $this->methodTitle = $this->loader->getConfigData('title');
    }

    public function execute()
    {
        $kbdata = $this->getRequest()->getParams();
        $kbdata[Response::RC_MSG] = Response::convertRcMessage($kbdata[Response::RC]);

        try {
            $kinaBankGateway = $this->loader->initPaymentGateway();
            $bankResponse = $kinaBankGateway->getResponseObject($kbdata);
            $check_result = $bankResponse->isValid();
        } catch(Exception $ex) {
            $this->_logger->error($ex);
        }

        $payment_id= $bankResponse->{Response::ORDER};
        $order_id  = KinaBankGateway::deNormalizeOrderId($bankResponse->{Response::ORDER});
        $amount    = $bankResponse->{Response::AMOUNT};
        $currency  = $bankResponse->{Response::CURRENCY};
        $approval  = $bankResponse->{Response::APPROVAL};
        $rrn       = $bankResponse->{Response::RRN};
        $intRef    = $bankResponse->{Response::INT_REF};
        $timeStamp = $bankResponse->{Response::TIMESTAMP};
        $text      = $bankResponse->{Response::TEXT};
        $bin       = $bankResponse->{Response::BIN};
        $card      = $bankResponse->{Response::CARD};
        $tranType  = Transaction::TYPE_PAYMENT;

        $bankParams = array(
            'PAYMENT_ID'=> $payment_id,
            'ORDER'     => $order_id,
            'AMOUNT'    => $amount,
            'CURRENCY'  => $currency,
            'TEXT'      => $text,
            'APPROVAL'  => $approval,
            'RRN'       => $rrn,
            'INT_REF'   => $intRef,
            'TIMESTAMP' => $timeStamp,
            'BIN'       => $bin,
            'CARD'      => $card,
            Response::RC => $kbdata[Response::RC],
            'DESCRIPTION' => $kbdata[Response::RC_MSG],
        );

        if(strlen($order_id) === 0) {
            /* translators: %1$s: Payment method */
            $message = sprintf('Order ID not received from $1$s', $this->methodTitle);
            $this->_logger->error($message);
            $this->messageManager->addErrorMessage($message);
            return $this->addErrorMessage($order_id, $message);
        }

        $order = $this->orderRepository->get($this->getRequest()->getParam('orderId'));
        if(!$order) {
            $message = sprintf('Order #%1$s not found as received from %2$s.', $order_id, $this->methodTitle);
            $this->_logger->error($message);
            $this->messageManager->addErrorMessage($message);
            return $this->addErrorMessage($order_id, $message);
        }

        $this->_logger->info('check result: ' . $check_result);

        if($check_result && $this->loader->checkTransaction($order, $bankResponse, $this->_logger)) {
            switch($bankResponse::TRX_TYPE) {
                case KinaBankGateway::TRX_TYPE_AUTHORIZATION:
                    if($order->getTotalPaid() == $order->getGrandTotal()) {
                        $this->messageManager->addSuccessMessage('This order already paid.');
                        $this->_redirect('sales/order/view/order_id/' . $order_id . '/');

                        return true; //Duplicate callback notification from the bank
                    }

                    /* translators: %1$s: Payment method, %2$s: Bank parameters */
                    $message = sprintf('Payment authorized via %1$s: %2$s', $this->methodTitle, http_build_query($bankParams));
                    $message = $this->loader->getOrderMessage($message);
                    $this->_logger->info($message);
                    $this->_logger->info('!!!! success');

                    $message = '';
                    switch($this->loader->getConfigData('payment_action')) {
                        default:
                            $this->_logger->info('!!!! completed');
                            $this->loader->addTransaction($order, $bankParams, $tranType, true);
                            $this->loader->updatePaidOrder($order, $bankParams);
                            $this->messageManager->addSuccessMessage('Order Paid Successfully.');
                            break;
                    }

                    $this->_redirect('sales/order/view/order_id/' . $order_id . '/');
                    return $this->_pageFactory->create();
                    break;

                case KinaBankGateway::TRX_TYPE_COMPLETION:
                    //Funds successfully transferred on bank side
                    /* translators: %1$s: Payment method, %2$s Bank parameters */
                    $message = sprintf('Payment completed via %1$s: %2$s', $this->methodTitle, http_build_query($bankParams));
                    $message = $this->loader->getOrderMessage($message);
                    $this->_logger->info($message);
                    $this->_logger->info('!!!! paid');
                    $this->loader->addTransaction($order, $bankParams, $tranType, true);
                    $this->loader->updatePaidOrder($order, $bankParams);
                    $this->messageManager->addSuccessMessage('Order Paid Successfully.');
                    $this->_redirect('sales/order/view/order_id/' . $order_id . '/');

                    return $this->_pageFactory->create();
                    break;

                default:
                    $message = sprintf('Unknown bank response TRX_TYPE: %1$s Order ID: %2$s', $bankResponse::TRX_TYPE, $order_id);
                    $this->_logger->error($message);
                    break;
            }
        }
        /* translators: %1$s: Order ID */
        $this->_logger->error(sprintf('Payment transaction check failed for order #%1$s.', $order_id));
        $this->_logger->error(print_r($bankResponse, true));

        /* translators: %1$s: Payment method, %2$s: Error details */
        $message = sprintf('%1$s payment transaction check failed: %2$s', $this->methodTitle, join('; ', $bankResponse->getErrors()) . ' ' . http_build_query($bankParams));
        $message = $this->loader->getOrderMessage($message);
        $this->_logger->info($message);

        $this->loader->addTransaction($order, $bankParams, $tranType, false);
        $order->setStatus(Order::STATE_PENDING_PAYMENT);
        $order->setState(Order::STATE_PENDING_PAYMENT);
        $order->save();
        $this->messageManager->addErrorMessage($bankParams['DESCRIPTION']);
        return $this->addErrorMessage($order_id, $message);
    }

    public function addErrorMessage($order_id, $message) {
        $data = $this->loader->getPaymentData();
        if($data['paymentPageType'] == 'hosted') {
            $this->_redirect('kinabank/checkout/index?orderId=' . $order_id . "&error=true");
            return $this->_pageFactory->create();
        } else {
            return $this->_pageFactory->create();
        }
    }
}
