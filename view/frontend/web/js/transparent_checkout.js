/*
 * @Author: Warley Elias
 * @Email: warley.elias@pentagrama.com.br
 * @Date: 2023-03-30 16:29:11
 * @Last Modified by: Warley Elias
 * @Last Modified time: 2023-04-04 16:02:54
 */

define([
    'jquery'
], function ($) {
    'use strict';

    return function (config) {
        function initPagaLeve(urlWithParameter) {
            parent.postMessage({ action: 'pagaleve-checkout-init', url: urlWithParameter }, '*')
        }

        function retrieveAbandonedCart() {
            window.location.replace(config.retrieveAbandonedCartUrl);
        }
    
        const checkoutURL = config.checkoutUrl;
        const urlWithParameter = checkoutURL + '&t=pagaleve';
        initPagaLeve(urlWithParameter);
        
        window.addEventListener('message', function (event) {
            if (event.data.action === 'pagaleve-checkout-finish') {
                let pagaleveData = event.data.data;

                //console.log(pagaleveData.reason) // cancel/confirm
                //console.log(pagaleveData.value) // https://sualoja.com.br/cancelament / https://sualoja.com.br/aprovacao // { reason: 'cancel', value: 'https://sualoja.com.br/cancelamento' }
                if (pagaleveData.reason === 'cancel') {
                    retrieveAbandonedCart();
                }
            }
        });
    };
});