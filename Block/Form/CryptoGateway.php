<?php

namespace QFPay\PaymentGateway\Block\Form;

use Magento\Customer\Helper\Session\CurrentCustomer;

class PaymentGateway extends \Magento\Payment\Block\Form
{
    /**
     * Payment method code
     *
     * @var string
     */
    protected $_methodCode = 'crypto_gateway';

    /**
     * @var null
     */
    protected $_config;

    /**
     * @var CurrentCustomer
     */
    protected $currentCustomer;

    protected function _construct()
    {
        $template = 'QFPay_PaymentGateway::PaymentGateway/form/PaymentGateway.phtml';
        $this->setTemplate($template);

        parent::__construct();
    }
}
