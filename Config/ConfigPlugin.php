<?php

namespace QFPay\PaymentGateway\Config;

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