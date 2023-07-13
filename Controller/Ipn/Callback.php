<?php

namespace QFPay\PaymentGateway\Controller\Ipn;

use QFPay\PaymentGateway\Helper\Utils;
use Magento\Sales\Model\Order;
use Magento\Framework\App\Action\Context;
use QFPay\PaymentGateway\Logger\Logger;
use QFPay\PaymentGateway\Helper\Data as PaymentHelper;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
/**
 * Class Index
 *
 * @package QFPay\PaymentGateway\Controller\Ipn
 */
class Callback extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{

    /**
     * @var \Magento\Sales\Model\OrderRepository
     */
    private $orderRepository;

    /**
     * @var Logger
     */
    private $log;

    /**
     * @var PaymentHelper
     */
    private $helper;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Invoice\CollectionFactory
     */
    protected $_invoiceCollectionFactory;
    /**
     * @var \Magento\Sales\Api\InvoiceRepositoryInterface
     */
    protected $_invoiceRepository;

    /**
    * @var \Magento\Sales\Model\Service\InvoiceService
    */
    protected $_invoiceService;

    /**
     * @var \Magento\Framework\DB\TransactionFactory
     */
    protected $_transactionFactory;
    /**
     * @var OrderFactory
     */
    protected $orderFactory;
    /**
     * Callback constructor.
     *
     * @param Context $context
     * @param \Magento\Sales\Model\OrderRepository $orderRepository
     * @param Logger $logger
     * @param PaymentHelper $helper
     * @param \Magento\Sales\Model\ResourceModel\Order\Invoice\CollectionFactory $invoiceCollectionFactory
     * @param \Magento\Sales\Model\Service\InvoiceService $invoiceService
     * @param \Magento\Framework\DB\TransactionFactory $transactionFactory
     * @param \Magento\Sales\Api\InvoiceRepositoryInterface $invoiceRepository
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     */
    public function __construct(
        Context $context,
        \Magento\Sales\Model\ResourceModel\Order\Invoice\CollectionFactory $invoiceCollectionFactory,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Magento\Sales\Api\InvoiceRepositoryInterface $invoiceRepository,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        Logger $logger,
        PaymentHelper $helper
    ) {
        $this->_invoiceCollectionFactory = $invoiceCollectionFactory;
        $this->_invoiceService = $invoiceService;
        $this->_transactionFactory = $transactionFactory;
        $this->_invoiceRepository = $invoiceRepository;
        $this->orderRepository = $orderRepository;
        $this->orderFactory = $orderFactory;
        $this->log = $logger;
        $this->helper = $helper;
        parent::__construct($context);
    }

    public function execute()
    {
        // $data = $this->getRequest();
        // md5（除sign以外的所有参数a-z排序 值拼接为字符串 + appscrect）
        $params = $this->getRequest()->getContent();
        $this->log->info('received callback content'.$params);
        $qfsign = $this->getRequest()->getHeader('X-QF-SIGN');
        $appscrect = trim($this->helper->getGeneralConfig('appscrect'));
        $sign = Utils::verifySign($appscrect, $params);
        //判断签名
        if ($qfsign !== $sign){
            $result = 'sign error';
            $this->log->error('signature verified');
            $this->getResponse()->setBody($result);
            return;
        } else {
            $this->log->info('signature verified');
        }
        //写日志
        $this->log->info('received callback content'.json_encode($params));
        //判断订单状态
        $notify_type=json_decode($params, true)["notify_type"];
        if ($notify_type=="refund") {
            // no additional handling for refund notifications
            $this->log->info("Received refund notification.");
            $result="SUCCESS";
            $this->getResponse()->setBody($result);
        } elseif ($notify_type=="payment") {
            $orderStatus=json_decode($params, true)["status"];
            if (!isset($orderStatus) || empty($orderStatus) ){//不存在或者为空
                $this->log->info($orderStatus." is not set");
                $this->logAndDie('OrderStatus is not set.');
                $result = 'OrderStatus is not set';
                $this->getResponse()->setBody($result);
                    
            } else {
                //设置订单状态
                $result=$this->setOrderStatus($params);
                $this->getResponse()->setBody($result);
            }
        }
    }

    /**
     * @param      $msg
     * @param null $order
     */
    private function setOrderStatus($params)
    {
        $data = json_decode($params, true);
        $orderIncId=$data["out_trade_no"];
        $orderStatus=$data["status"];
        $syssn=$data["syssn"];
        $diffAmount = $data["txamt"];
        # get orderid by order increament id
        $orderModel = $this->orderFactory->create();
        $order = $orderModel->loadByIncrementId($orderIncId);
        $orderId = $order->getId();
        //本地订单金额
        $sysAmount=($order->getBaseGrandTotal())*100;
        //判断订单金额
        if ($sysAmount <= $diffAmount){
            //判断支付状态
            if ($orderStatus=="1") {
                $order->setState($this->helper->getGeneralConfig('status_order_paid'));
                $order->setStatus($this->helper->getGeneralConfig('status_order_paid'));
                $order->addCommentToStatusHistory('Merchant Order ID: '.$orderIncId."<br/>Transaction ID: ".$syssn);
                try {
                    $this->log->info("Order number: ".$orderIncId." is OK.");
                    $this->orderRepository->save($order);
                    $autoCreate = $this->helper->getGeneralConfig("auto_invoice_enabled");
                    if ($autoCreate == 1) {
                        $this->log->info("auto invoice requested for order id " . $orderId);
                        $this->createInvoice($orderId);
                    }
                    return "SUCCESS"; # QF sdk required return string
                } catch (\Exception $e) {
                    $this->logger->error($e);
                    return "FAIL";
                }
            }
        } else {
            return "FAIL";
        }

    }

    /**
     * @param      $msg
     * @param null $order
     */
    private function logAndDie($msg, $order = null)
    {
        if ($this->helper->getGeneralConfig('debug')) {
            $messsageString = '';
            if ($order !== null) {
                $messsageString = 'Order ID: ' . $order->getId() . '<br/>';
            }
            $messsageString .= $msg;
            $this->log->info($messsageString);
        }

        return;
    }
    
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    protected function createInvoice($orderId)
    {
        try {
            $order = $this->orderRepository->get($orderId);
            if ($order) {
                $invoices = $this->_invoiceCollectionFactory->create()
                  ->addAttributeToFilter('order_id', array('eq' => $order->getId()));

                $invoices->getSelect()->limit(1);

                if ((int)$invoices->count() !== 0) {
                  $invoices = $invoices->getFirstItem();
                  $invoice = $this->_invoiceRepository->get($invoices->getId());
                  return $invoice;
                }

                if (!$order->canInvoice()) {
                    return null;
                }

                $invoice = $this->_invoiceService->prepareInvoice($order);
                $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
                $invoice->register();
                $invoice->getOrder()->setCustomerNoteNotify(false);
                $invoice->getOrder()->setIsInProcess(true);
                $order->addStatusHistoryComment(__('Automatically INVOICED'), false);
                $transactionSave = $this->_transactionFactory->create()->addObject($invoice)->addObject($invoice->getOrder());
                $transactionSave->save();

                return $invoice;
            }
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __($e->getMessage())
            );
        }
    }

}
