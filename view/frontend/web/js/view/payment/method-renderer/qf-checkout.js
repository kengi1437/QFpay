define(
    [
        'ko',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/quote',
        'jquery',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/payment/additional-validators',
        'mage/url',
        'QFPay_PaymentGateway/js/action/set-payment-method',
        'Magento_Checkout/js/model/full-screen-loader'
    ],
    function (ko,Component, quote,$,placeOrderAction,selectPaymentMethodAction,customer, checkoutData, additionalValidators, url,setPaymentMethodAction,fullScreenLoader) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'QFPay_PaymentGateway/payment/crypto_payment'
            },
            getCode: function() {
                return 'qf_checkout';
            },
            isActive: function() {
                return true;
            },
            afterPlaceOrder: function () {
                window.location.replace(url.build('PaymentGateway/invoice/index/'));
            },
            getRedirectionText: function () {

                var iframeHtml;
                jQuery.ajax( {
                    url: url.build('PaymentGateway/iframe/qfcheckout/'),
                    async: false ,
                    dataType: "json",
                    success: function(a) {
                        iframeHtml = a.html;
                    }

                });
                return iframeHtml;
            },
            selectPaymentMethod: function() {
                selectPaymentMethodAction(this.getData());
                checkoutData.setSelectedPaymentMethod(this.item.method);
                return true;
            },
            placeOrder: function (data, event) {
                if (event) {
                    event.preventDefault();
                }
                var self = this,
                    placeOrder,
                    emailValidationResult = customer.isLoggedIn(),
                    loginFormSelector = 'form[data-role=email-with-possible-login]';
                if (!customer.isLoggedIn()) {
                    $(loginFormSelector).validation();
                    emailValidationResult = Boolean($(loginFormSelector + ' input[name=username]').valid());
                }
                if (emailValidationResult && this.validate() && additionalValidators.validate()) {
                    this.isPlaceOrderActionAllowed(false);
                    placeOrder = placeOrderAction(this.getData(), false, this.messageContainer);

                    $.when(placeOrder).fail(function () {
                        self.isPlaceOrderActionAllowed(true);
                    }).done(this.afterPlaceOrder.bind(this));
                    return true;
                }
                return false;
            },
            getMailingAddress: function () {
                return window.checkoutConfig.payment.checkmo.mailingAddress;
            },
        });
    }
);
