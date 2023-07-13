<?php

namespace QFPay\PaymentGateway\Controller\Iframe;

class QfCheckout extends \Magento\Framework\App\Action\Action
{
    private $configResource;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    private $quoteFactory;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $cart;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Index constructor.
     *
     * @param \Magento\Framework\App\Action\Context                     $context
     * @param \Magento\Framework\App\Config\MutableScopeConfigInterface $config
     * @param \Magento\Checkout\Model\Cart                              $cart
     * @param \Magento\Quote\Model\QuoteFactory                         $quoteFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\MutableScopeConfigInterface $config,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->config       = $config;
        $this->cart         = $cart;
        $this->quoteFactory = $quoteFactory;
        $this->scopeConfig  = $scopeConfig;
        parent::__construct($context);
    }

    /**
     * 选择支付方式页面html
     * @route PaymentGateway/iframe/index
     */
    public function execute()
    {
        $html = __("Please use Qf Secure Checkout to Pay");
        $this->getResponse()->setBody(json_encode(['html' => $html]));
    }
}
