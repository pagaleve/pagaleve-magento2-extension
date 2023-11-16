<?php
/**
 * @author      FCamara - Formação e Consultoria <contato@fcamara.com.br>
 * @author      Guilherme Miguelete <guilherme.miguelete@fcamara.com.br>
 * @license     Pagaleve Tecnologia Financeira | Copyright
 * @copyright   2022 Pagaleve Tecnologia Financeira (http://www.pagaleve.com.br)
 *
 * @link        http://www.pagaleve.com.br
 */

declare(strict_types=1);

namespace Pagaleve\Payment\Observer;

use Magento\Framework\DataObject\Copy;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Pagaleve\Payment\Model\Pagaleve;

class SaveOrderBeforeSalesModelQuoteObserver implements ObserverInterface
{
    /**
     * @var Copy
     */
    protected $objectCopyService;

    /**
     * @param Copy $objectCopyService
     */
    public function __construct(
        Copy $objectCopyService
    ) {
        $this->objectCopyService = $objectCopyService;
    }

    /**
     * @param Observer $observer
     * @return SaveOrderBeforeSalesModelQuoteObserver
     */
    public function execute(Observer $observer)
    {
        /* @var Quote $quote */
        $quote = $observer->getEvent()->getData('quote');

        /* @var Order $order */
        $order = $observer->getEvent()->getData('order');

        if ($order->getPayment()->getMethod() != Pagaleve::PAYMENT_METHOD_PAGALEVE_CODE) {
            return $this;
        }

        $order->setData('pagaleve_checkout_id', $quote->getData('pagaleve_checkout_id'));
        $order->setData('pagaleve_payment_id', $quote->getData('pagaleve_payment_id'));
        $order->setData('pagaleve_expiration_date', $quote->getData('pagaleve_expiration_date'));

        $this->objectCopyService->copyFieldsetToTarget('sales_convert_quote', 'to_order', $quote, $order);

        return $this;
    }
}
