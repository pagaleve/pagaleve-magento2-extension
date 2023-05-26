define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'pagaleve_upfront',
                component: 'Pagaleve_Payment/js/view/payment/method-renderer/pagaleve-upfront-method'
            }
        );
        return Component.extend({});
    }
);