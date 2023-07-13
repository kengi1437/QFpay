
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (Component,
              rendererList) {
        'use strict';
        rendererList.push(
            {
                type: 'qf_checkout',
                component: 'QFPay_PaymentGateway/js/view/payment/method-renderer/qf-checkout'
            }
        );
        return Component.extend({});
    }
);