<?php

namespace QFPay\PaymentGateway\Model\Methods;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Payment\Model\Method\ConfigInterface;
use Magento\Payment\Model\Method\TransparentInterface;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Sales\Model\Order;

class PaymentGateway extends AbstractMethod
{

    /**
     * Method code
     */
    const CODE = 'crypto_gateway';

    /**
     * @var string
     */
    protected $_code = self::CODE;


    /* Uncomment if need using blocks

    protected $_formBlockType               = 'QFPay\PaymentGateway\Block\Form\PaymentGateway';

    protected $_infoBlockType               = 'QFPay\PaymentGateway\Block\Info';*/


    /**
     * @param CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        return parent::isAvailable($quote);
    }

    /**
     * @param null $storeId
     * @return bool
     */
    public function isActive($storeId = null)
    {
        return (bool)(int)$this->getConfigData('active', $storeId);
    }
}
