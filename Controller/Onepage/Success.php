<?php
/*
 * @Author: Warley Elias
 * @Email: warley.elias@pentagrama.com.br
 * @Date: 2022-12-12 08:17:19
 * @Last Modified by: Warley Elias
 * @Last Modified time: 2022-12-12 12:49:29
 */

namespace Pagaleve\Payment\Controller\Onepage;

use Magento\Checkout\Controller\Onepage\Success as CheckoutOnepageSuccess;

class Success extends CheckoutOnepageSuccess {
    public function execute()
    {
        $session = $this->getOnepage()->getCheckout();
        $order = $session->getLastRealOrder();
        //get param
        $passthroug = $this->getRequest()->getParam('passthrough');
        if ($order->getPayment()->getMethod() == 'pagaleve' && !$passthroug) {
            //Redirect to Pagaleve
            $pagaleveCheckoutUrl = $order->getPagaleveCheckoutUrl();
            $this->_redirect($pagaleveCheckoutUrl);
            return;
        }
        return parent::execute();
    }
}