<?php

namespace QFPay\PaymentGateway\Block;

/**
 * Base payment iformation block
 */
class Info extends \Magento\Framework\View\Element\Template
{
    /**
     * @var string
     */
    protected $_template = 'QFPay_PaymentGateway::PaymentGateway/info/default.phtml';

    /**
     * @return mixed
     */
    public function getPaymentGatewayInvoiceUrl()
    {
        return true;
    }
}
