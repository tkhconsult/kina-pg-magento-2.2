<?php

namespace TkhConsult\KinaPg\Helper\Gateway;

use DateTime;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DB\Transaction as DBTransaction;
use Magento\Framework\Filesystem;
use Magento\Payment\Model\Method\Adapter;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Framework\App\RequestInterface;
use TkhConsult\KinaBankGateway\KinaBank\Response;
use TkhConsult\KinaBankGateway\KinaBankGateway;
use TkhConsult\KinaPg\Model\Ui\ConfigProvider;
use Magento\Framework\Exception\LocalizedException;

class Loader {
    /**
     * @var InvoiceService
     */
    protected $invoiceService;
    /**
     * @var InvoiceSender
     */
    protected $invoiceSender;
    /**
     * @var DBTransaction
     */
    protected $transaction;
    /**
     * @var OrderSender
     */
    protected $orderSender;
    /**
     * @var Repository
     */
    protected $assetRepo;
    /**
     * @var RequestInterface
     */
    protected $request;

    protected $filesystem;
    protected $backRefUrl;

    const TRANSACTION_TYPE_CHARGE = 'order';
    const TRANSACTION_TYPE_PAYMENT = 'payment';
    const TRANSACTION_TYPE_AUTHORIZATION = 'authorize';

    const TEST_URL = 'https://devegateway.kinabank.com.pg';
    const PROD_URL = 'https://ipg.kinabank.com.pg';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    const XML_PATH_PREFIX = 'payment/kinabank_gateway/';

    public function __construct($backRefUrl, $request = null) {
        $objectManager = ObjectManager::getInstance();
        $this->scopeConfig = $objectManager->create(ScopeConfigInterface::class);
        $this->filesystem = $objectManager->create(Filesystem::class);
        $this->backRefUrl = $backRefUrl;
        $this->orderSender = $objectManager->create(OrderSender::class);
        $this->invoiceService = $objectManager->create(InvoiceService::class);
        $this->invoiceSender = $objectManager->create(InvoiceSender::class);
        $this->transaction = $objectManager->create(DBTransaction::class);
        $this->assetRepo = $objectManager->create(Repository::class);
        $this->request = $request;
        if(false) (new Adapter())->initialize('','');
    }


    public function getTestKeyPath() {
        $mediapath = $this->filesystem->getDirectoryRead('media')->getAbsolutePath();

        return $mediapath . 'test_key/' . $this->getConfigData('merchant/test_key');
    }

    public function getConfigData($key) {
        $storeScope = ScopeInterface::SCOPE_STORE;

        return $this->scopeConfig->getValue(self::XML_PATH_PREFIX . $key, $storeScope);
    }

    public function getPaymentData() {
        $debug = $this->getConfigData('debug');
        $testmode = $this->getConfigData('test_mode');
        $paymentPageType = $this->getConfigData('payment_page_type');
        $merchantId = $this->getConfigData('merchant/card_acceptor_id');
        $merchantTerminal = $this->getConfigData('merchant/terminal_id');
        $merchantUrl = $this->getConfigData('merchant/merchant_url');
        $merchantName = $this->getConfigData('merchant/merchant_name');
        $merchantAddress = $this->getConfigData('merchant/merchant_address');
        $timezone = $this->getTimezone();
        $lang = 'en';
        $keyPath = ($testmode) ? $this->getTestKeyPath() : $this->getProdKeyPath();
        $gatewayUrl = $this->getHost($testmode) . '/cgi-bin/cgi_link';
        $sslVerify  = !$testmode;
        $defaultCurrency =  $this->getConfigData('currency');
        $backRefUrl = $this->backRefUrl;

        return compact('debug', 'testmode', 'paymentPageType', 'merchantId', 'merchantTerminal', 'merchantUrl', 'merchantName', 'merchantAddress', 'timezone', 'lang', 'keyPath', 'gatewayUrl', 'sslVerify', 'defaultCurrency', 'backRefUrl');
    }

    public function getHost($testmode) {
        $host = self::PROD_URL;
        if($testmode) {
            $host = self::TEST_URL;
        }

        return $host;
    }

    /**
     * @param Order $order
     * @param $bankResponse
     * @return bool
     */
    public function checkTransaction($order, $bankResponse, $logger = null) {
        $amount   = $bankResponse->{Response::AMOUNT};
        $currency = $bankResponse->{Response::CURRENCY};
        $trxType  = $bankResponse::TRX_TYPE;

        $order_total = $order->getGrandTotal();
        $order_currency = $order->getOrderCurrencyCode();

        //Validate currency
        if(strtolower($currency) !== strtolower($order_currency)) {
            if(!is_null($logger)) $logger->error('currency not matched: ' . strtolower($currency) . '!==' . strtolower($order_currency));
            return false;
        }

        //Validate amount
        if($amount <= 0) {
            if(!is_null($logger)) $logger->error('amount less than 0: ' . $amount);
            return false;
        }

        $diff = abs($order_total - $amount);

        if(!is_null($logger)) $logger->info('amount, order total, diff: "' . $amount . '", "' . $order_total . '", "' . $diff . '"');

        if($trxType === KinaBankGateway::TRX_TYPE_REVERSAL)
            return $amount <= $order_total || $diff < 1;

        return $amount == $order_total || $diff < 1;
    }

    public function getOrderMessage($message) {
        if($this->getConfigData('test_mode'))
            $message = 'TEST: ' . $message;

        return $message;
    }

    public function initPaymentGateway() {
        $data = $this->getPaymentData();
        $kinaBankGateway = new KinaBankGateway();

        //Set basic info
        $kinaBankGateway
            ->setGatewayUrl($data['gatewayUrl'])
            ->setSslVerify($data['sslVerify'])
            ->setMerchantId($data['merchantId'])
            ->setMerchantTerminal($data['merchantTerminal'])
            ->setMerchantUrl($data['merchantUrl'])
            ->setMerchantName($data['merchantName'])
            ->setMerchantAddress($data['merchantAddress'])
            ->setTimezone($data['timezone'])
            ->setDebug($data['debug'])
            ->setDefaultLanguage($data['lang'])
            ->setAcceptUrl($this->getAcceptLogoUrl())
            ->setPaymentPageType($data['paymentPageType'])
            ->setSubmitButtonLabel('Click here to pay');

        $kinaBankGateway->setSecurityOptions($data['keyPath']);

        return $kinaBankGateway;
    }

    public function getProdKeyPath() {
        $mediapath = $this->filesystem->getDirectoryRead('media')->getAbsolutePath();

        return  $mediapath . 'prod_key/' . $this->getConfigData('merchant/prod_key');
    }

    public function getTimezone() {
        $storeScope = ScopeInterface::SCOPE_STORE;

        return DateTime::createFromFormat('e', $this->scopeConfig->getValue('general/locale/timezone', $storeScope))->format('P');
    }

    /**
     * @param Order $order
     * @param array $bankParams
     * @param bool $isSuccess
     */
    public function addTransaction($order, $bankParams, $type, $isSuccess = true) {
        /** @var \Magento\Sales\Model\Order\Payment\Interceptor $payment */
        $payment = $order->getPayment();

        foreach($bankParams as $key => $val) {
            $payment->setAdditionalInformation($key, $val);
        }
        $payment->setTransactionId(htmlentities($bankParams['PAYMENT_ID']));
        $payment->setTransactionAdditionalInfo(Transaction::RAW_DETAILS, $bankParams);
        $payment->setIsTransactionClosed($isSuccess);
        $payment->addTransaction($type);

        $payment->save();
        $order->setPayment($payment);
        if($isSuccess) $order->setCanSendNewEmailFlag(1);
        $order->save();
        if($isSuccess) $this->orderSender->send($order);
    }

    /**
     * @param Order $order
     * @param array $bankParams
     */
    public function updatePaidOrder($order, $bankParams) {
        $order->setTotalPaid($bankParams[Response::AMOUNT]);
        $order->setStatus(Order::STATE_PROCESSING);
        $order->setState(Order::STATE_COMPLETE);
        /** @var Invoice $invoice */
        $invoice = $this->invoiceService->prepareInvoice($order);
        $invoice->setCustomerNoteNotify(1);
        $invoice->setEmailSent(1);
        $invoice->register();
        $invoice->capture();
        $invoice->save();
        $transaction = $this->transaction->addObject(
            $invoice
        )->addObject(
            $invoice->getOrder()
        );
        $transaction->save();
        $this->invoiceSender->send($invoice);
        $order->addStatusToHistory(false, __('Invoice #%1 created', $invoice->getIncrementId()), true);
        $order->save();
    }

    public function getAcceptLogoUrl()
    {
        return $this->getViewFileUrl('TkhConsult_KinaPg::accept.png');
    }

    /**
     * Retrieve url of a view file
     *
     * @param string $fileId
     * @param array $params
     * @return string
     */
    public function getViewFileUrl($fileId, array $params = [])
    {
        try {
            if(is_null($this->request)) return '';
            $params = array_merge(['_secure' => $this->request->isSecure()], $params);
            return $this->assetRepo->getUrlWithParams($fileId, $params);
        } catch (LocalizedException $e) {
            return '';
        }
    }
}
