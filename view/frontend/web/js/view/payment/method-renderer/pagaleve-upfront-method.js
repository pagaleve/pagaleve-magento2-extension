define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/payment/additional-validators',
        'mage/url',
        'Magento_Customer/js/customer-data'
    ],
    function (
        $,
        Component,
        placeOrderAction,
        selectPaymentMethodAction,
        customer,
        checkoutData,
        additionalValidators,
        url,
        customerData
    ) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Pagaleve_Payment/payment/pagaleve_upfront'
            },
            getInstructions: function () {
                return window.checkoutConfig.payment.pagaleve.instructions
            },
            /*placeOrder: function (data, event) {
                if (event) {
                    event.preventDefault();
                }

                $('body').loader('show');

                setTimeout(() => {
                    this.beforePlaceOrder()
                }, 1000);

            },*/
            selectPaymentMethod: function () {
                selectPaymentMethodAction(this.getData());
                checkoutData.setSelectedPaymentMethod(this.item.method);
                return true;
            },
            /*beforePlaceOrder: function () {
                var sections = ['cart'];
                customerData.invalidate(sections);
                window.location.replace(url.build('pagaleve/checkout/process'));
            }*/
        });
    }
);
