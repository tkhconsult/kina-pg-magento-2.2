<?php
namespace TkhConsult\KinaPg\Controller\Checkout;

use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Api\OrderRepositoryInterface;

class Index extends \Magento\Framework\App\Action\Action
{
    protected $_pageFactory;
    /** @var OrderRepositoryInterface */
    protected $orderRepository;

    public function __construct(
        Context $context,
        PageFactory $pageFactory)
    {
        $this->_pageFactory = $pageFactory;
        parent::__construct($context);
        $this->orderRepository = $context->getObjectManager()->create(OrderRepositoryInterface::class);
    }

    public function execute()
    {
        try{
            $order = $this->orderRepository->get($this->getRequest()->getParam('orderId'));
            if($order->getTotalPaid() == $order->getGrandTotal()) {
                $this->messageManager->addSuccessMessage(__('Checkout session already expired, please re-order again.'));
                $this->_redirect('');
                return $this->_pageFactory->create();
            }
        } catch(\Exception $ex) {
            $this->messageManager->addSuccessMessage(__('Checkout session already expired, please re-order again.'));
            $this->_redirect('');
        }


        return $this->_pageFactory->create();
    }
}
