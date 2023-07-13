<?php

namespace QFPay\PaymentGateway\Block;
use QFPay\PaymentGateway\Helper\Utils;
use QFPay\PaymentGateway\Logger\Logger;

class Iframe extends \Magento\Framework\View\Element\Template
{
    const PATH_TO_PAYMENT_CONFIG = 'payment/crypto_gateway/';

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;


    /**
     * @var \Magento\Quote\Model\QuoteRepository
     */
    protected $quoteRepository;
    private $log;

    /**
     * Iframe constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Quote\Model\QuoteRepository $quoteRepository
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Quote\Model\QuoteRepository $quoteRepository,
        Logger $logger,
        array $data = []
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->log = $logger;
        $this->_coreRegistry = $coreRegistry;
        parent::__construct($context, $data);
    }

    /**
     * @return mixed
     */
    public function getLastOrderId()
    {
        $lastSuccessOrderId = $this->_coreRegistry->registry('last_success_order_id');
        $this->log->info("Last Success Order ID: " . $lastSuccessOrderId);
        return $lastSuccessOrderId;
    }

    public function getRealOrderId()
    {
        $lastSuccessOrderId = $this->_coreRegistry->registry('last_real_order_id');
        $this->log->info("Last Real Order ID: " . $lastSuccessOrderId);
        return $lastSuccessOrderId;
    }

    public function getOrderIncrementalId()
    {
        $quoteModel = $this->getQuote();
        $orderIncrementalId = $quoteModel->getReservedOrderId();
        $this->log->info("Order Incremental ID: " . $orderIncrementalId);
        return $orderIncrementalId;
    }

    /**
     * create an invoice and return the url so that iframe.phtml can display it
     *
     * @return string
     */
    public function getFrameActionUrl()
    {
        return $this->getUrl('PaymentGateway/form/index', ['_secure' => true]);
    }

    /**
     * @return string
     */
    public function getIpnUrl()
    {
        $quoteModel = $this->getQuote();
        return $this->getUrl('PaymentGateway/ipn/index');
    }

    /**
     * @return array
     */
    public function getPaymentData()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
		$quoteModel     = $this->quoteRepository->get($this->_coreRegistry->registry('last_success_quote_id'));
        $this->log->info("Current Quote Model info: " . json_encode($quoteModel->debug()));
        //获取订单支付类型
        $payTag = '';
        $paysource = 'magento_checkout';
        $sign_type = 'MD5';
        $data = [
            'merchant_id' => $this->_scopeConfig->getValue(self::PATH_TO_PAYMENT_CONFIG . "merchant_id", $storeScope),
            'ipn_secret' => $this->_scopeConfig->getValue(self::PATH_TO_PAYMENT_CONFIG . "ipn_secret", $storeScope),
            'code' => $this->_scopeConfig->getValue(self::PATH_TO_PAYMENT_CONFIG . "code", $storeScope),
            'item_name' => $this->_scopeConfig->getValue('general/store_information/name', $storeScope),
            'store_id' => $this->_storeManager->getStore()->getId(),
            'currency_code' => $this->_storeManager->getStore()->getCurrentCurrencyCode(),
            'appscrect' => $this->_scopeConfig->getValue(self::PATH_TO_PAYMENT_CONFIG . "appscrect", $storeScope),
            'paysource' => $paysource,
            'sign_type' => $sign_type
        ];
        if($payTag) {
            $data['pay_tag'] = $payTag;
        }

        return $data;
    }

    /**
     * @return \Magento\Quote\Api\Data\CartInterface|\Magento\Quote\Model\Quote
     */
    public function getQuote()
    {
        $quoteModel = $this->quoteRepository->get($this->_coreRegistry->registry('last_success_quote_id'));
        return $quoteModel;
    }

     /**
     * @return int
     */
    public function getShippingAmount()
    {
        $quoteModel     = $this->quoteRepository->get($this->_coreRegistry->registry('last_success_quote_id'));
        $shippingAmount = $quoteModel->getShippingAddress()->getShippingAmount();
        if ($quoteModel->getShippingAddress()->getShippingDiscountAmount()) {
            $shippingAmount = $shippingAmount - $quoteModel->getShippingAddress()->getShippingDiscountAmount();
        }

        return $shippingAmount;
    }

    /**
     * @return string
     */
    public function getSuccessUrl()
    {
        return $this->getUrl('checkout/onepage/success');
    }

    /**
     * @return string
     */
    public function getFailUrl()
    {
        return $this->getUrl('PaymentGateway/checkout/failure');
    }

    /**
     * @return string
     */
    public function getCallbackUrl()
    {
        return $this->getUrl('PaymentGateway/ipn/callback');
    }


    /**
     * @return bool
     */
    public function isMobile()
    {
        return Utils::isMobile();
    }

    /**
     * @return bool
     */
    public function isWeixin()
    {
        return Utils::isWexin();
    }
}
