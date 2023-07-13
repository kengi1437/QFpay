<?php

namespace QFPay\PaymentGateway\Plugin;

class ConfigPlugin
{
    public function aroundSave(
    \Magento\Config\Model\Config $subject,
    \Closure $proceed
) {
    // your custom logic
    return $proceed();
}
}