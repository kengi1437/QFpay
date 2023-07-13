<?php

namespace QFPay\PaymentGateway\Controller\Ipn;

use QFPay\PaymentGateway\Helper\Curl;
use QFPay\PaymentGateway\Helper\Utils;
use Magento\Sales\Model\Order;
use Magento\Framework\App\Action\Context;
use QFPay\PaymentGateway\Logger\Logger;
use QFPay\PaymentGateway\Helper\Data as PaymentHelper;

/**
 * Class Index
 *
 * @package QFPay\PaymentGateway\Controller\Ipn
 */
class Index extends \Magento\Framework\App\Action\Action
{
    protected $searchCriteriaBuilder;
    protected $sortBuilder;
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
     * Index constructor.
     *
     * @param Context $context
     * @param \Magento\Sales\Model\OrderRepository $orderRepository
     * @param Logger $logger
     * @param PaymentHelper $helper
     */
    public function __construct(
        Context $context,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Api\SortOrderBuilder $sortBuilder,
        Logger $logger,
        PaymentHelper $helper
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sortBuilder = $sortBuilder;
        $this->orderRepository = $orderRepository;
        $this->log = $logger;
        $this->helper = $helper;
        parent::__construct($context);
    }

    public function execute()
    {
        ob_start();
        $this->log->info("executing pending order check");
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('status','pending','eq')
            ->addSortOrder($this->sortBuilder->setField('entity_id')
                ->setDescendingDirection()->create())
            ->setPageSize(100)->setCurrentPage(1)->create();

        $to = date("Y-m-d h:i:s"); // current date
        $from = strtotime('-60 day', strtotime($to));
        $from = date('Y-m-d h:i:s', $from); // 1 days before

        $ordersList = $this->orderRepository->getList($searchCriteria);
        $ordersList->addFieldToFilter('created_at', array('from'=>$from, 'to'=>$to));
        //所有订单状态为pending的订购单id
        $ids = $ordersList->getAllIds();
        $log = "Response:";
        //遍历ids
        for($i=0;$i<count($ids);$i++){
            //请求支付状态
            $order = $this->orderRepository->get($ids[$i]);
            $orderIncrementId = $order->getIncrementId();
            $data = [
                'out_trade_no' => $orderIncrementId
            ];
            if (!empty($this->helper->getGeneralConfig('merchant_id'))){
                $data['mchntid'] = $this->helper->getGeneralConfig('merchant_id');
            }
            $header = [
                'X-QF-APPCODE: '.($this->helper->getGeneralConfig('code')),
                'X-QF-SIGN: '.(Utils::getSign($this->helper->getGeneralConfig('appscrect'),$data))
            ];
            $url = 'https://openapi-hk.qfapi.com/trade/v1/query';
            //$log .= $url;
            $res = Curl::curlRequest($url, "POST", $data, $header);
            $this->log->info($res);
            if($res){
                $log .= $res;
                $respData = json_decode($res, true)['data'];
                $arr = explode('&',$res);
                //如果支付成功  改变订单状态
                $order = $this->orderRepository->get($ids[$i]);
                //本地订单金额
                $sysAmount=($order->getBaseGrandTotal())*100;
                //$log = '';
                //2表示订单创建，1是成功
                // 2021-04-09 新增如果状态是0，即主动通知ipn未给正确结果，主动查询也更新订单状态
                if (!empty($respData)){
                    if(($respData[0]['respcd'] == "0000" && $respData[0]['txamt'] == $sysAmount)){
                        $order->setState($this->helper->getGeneralConfig('status_order_paid'));
                        $order->setStatus($this->helper->getGeneralConfig('status_order_paid'));
                        try {
                            $this->orderRepository->save($order);
                            $log .= $ids[$i].'ok';
                        } catch (\Exception $e) {
                            $this->logger->error($e);
                            $log .= $ids[$i].'fail';
                        }
                    }
                }
            }
        }
        return $this->getResponse()->setBody($log);

    }

    /**
     * @param $status
     * @param $order
     */
    private function checkStatus($status, $order)
    {
        if ($status < 0) {
            //canceled or timed out
            $order->cancel();
            $order->setState(
                ORDER::STATE_CANCELED,
                true,
                'qfpay Payment Status: ' . $this->getRequest()->getParam(
                    'status_text'
                )
            )->setStatus(ORDER::STATE_CANCELED);
        } else {
            if ($status >= 100 || $status == 2) {
                //order complete or queued for nightly payout
                $str = 'qfpay Payment Status: ' . $this->getRequest()->geпtParam('status_text')
                    . '<br />';
                $str .= 'Transaction ID: ' . $this->getRequest()->getParam('txn_id')
                    . '<br />';
                $str .= 'Original Amount: ' . sprintf('%.08f', $this->getRequest()->getParam('amount1'))
                    . ' ' . $this->getRequest()->getParam('currency1') . '<br />';
                $str .= 'Received Amount: ' . sprintf('%.08f', $this->getRequest()->getParam('amount2'))
                    . ' ' . $this->getRequest()->getParam('currency2');
                $order->addStatusToHistory($this->helper->getGeneralConfig('status_order_paid'), $str, true);
                $order->setState(
                    $this->helper->getGeneralConfig('status_order_paid'),
                    true,
                    $str
                )->setStatus($this->helper->getGeneralConfig('status_order_paid'));
                $this->_objectManager->create('\Magento\Sales\Model\OrderNotifier')->notify($order);
            } else {
                //order pending
                $str = 'qfpay Payment Status: ' . $this->getRequest()->getParam('status_text');
                $order->addStatusToHistory(Order::STATE_NEW, $str, true);
                $order->setState(
                    Order::STATE_NEW,
                    true,
                    $str
                )->setStatus(Order::STATE_PROCESSING);
            }
            $order->save();
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

    /**
     * @return bool
     */
    private function is_ipn_valid()
    {
        $ipn = $this->getRequest()->getParams();
        if (!isset($ipn['ipn_mode'])) {
            $this->logAndDie('IPN received with no ipn_mode.');
        }
        if ($ipn['ipn_mode'] == 'hmac') {
            if ($this->checkHmacIpn($ipn)) {
                return true;
            }
        } else {
            $this->logAndDie('Unknown ipn_mode.');
        }

        return false;
    }

    /**
     * @return bool
     */
    private function checkHmacIpn($ipn)
    {
        if (!isset($_SERVER['HTTP_HMAC']) || empty($_SERVER['HTTP_HMAC'])) {
            $this->logAndDie('No HMAC signature sent.');

            return false;
        }

        $request = file_get_contents('php://input');
        if ($request === false || empty($request)) {
            $this->logAndDie(
                'Error reading POST data: ' . print_r($_SERVER, true) . '/' . print_r(
                    $this->getRequest()->getParams(),
                    true
                )
            );

            return false;
        }

        $merchant = isset($ipn['merchant']) ? $ipn['merchant'] : '';
        if (empty($merchant)) {
            $this->logAndDie('No Merchant ID passed');

            return false;
        }
        if ($merchant != trim($this->helper->getGeneralConfig('merchant_id'))) {
            $this->logAndDie('Invalid Merchant ID');

            return false;
        }

        $hmac = hash_hmac("sha512", $request, trim($this->helper->getGeneralConfig('ipn_secret')));
        if ($hmac != $_SERVER['HTTP_HMAC']) {
            $this->logAndDie('HMAC signature does not match');

            return false;
        }

        return true;
    }
}
