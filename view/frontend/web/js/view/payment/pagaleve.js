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
                type: 'pagaleve',
                component: 'Pagaleve_Payment/js/view/payment/method-renderer/pagaleve-method'
            }
        );
        return Component.extend({});
    }
);