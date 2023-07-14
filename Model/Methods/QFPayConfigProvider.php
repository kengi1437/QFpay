<?php
namespace QFPay\PaymentGateway\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\View\Asset\Repository;

class QFPayConfigProvider implements ConfigProviderInterface
{
    protected $assetRepo;

    public function __construct(Repository $assetRepo)
    {
        $this->assetRepo = $assetRepo;
    }

    public function getConfig()
    {
        $config = [];
        $config['payment']['qfpay']['logoUrl'] = $this->assetRepo->getUrl('QFPay_PaymentGateway::images/qfpay-logo.png');
        return $config;
    }
}