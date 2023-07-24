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

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Invoice;
use Pagaleve\Payment\Helper\Config as HelperConfig;
use Pagaleve\Payment\Model\Config\Source\PaymentAction;
use Pagaleve\Payment\Model\Pagaleve;
use Pagaleve\Payment\Model\Request\Payment\CaptureRequest;

class InvoicePay implements ObserverInterface
{
    /** @var HelperConfig $helperConfig */
    protected HelperConfig $helperConfig;

    /** @var CaptureRequest $captureRequest */
    private CaptureRequest $captureRequest;

    /**
     * @param HelperConfig $helperConfig
     * @param CaptureRequest $captureRequest
     */
    public function __construct(HelperConfig $helperConfig, CaptureRequest $captureRequest)
    {
        $this->helperConfig = $helperConfig;
        $this->captureRequest = $captureRequest;
    }

    /**
     * Update inviter balance if possible
     *
     * @param Observer $observer
     * @return InvoicePay
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        /* @var $invoice Invoice */
        $invoice = $observer->getEvent()->getData('invoice');
        $order = $invoice->getOrder();

        if ($this->helperConfig->getPaymentAction() == PaymentAction::AUTHORIZE_AND_CAPTURE) {
            return $this;
        }

        if ($order->getPayment()->getMethod() != Pagaleve::PAYMENT_METHOD_PAGALEVE_CODE) {
            return $this;
        }

        try {
            $captureData = $this->captureRequest->create(
                $order->getData('pagaleve_payment_id'),
                $order->getGrandTotal(),
                $invoice->getGrandTotal()
            );

            if (isset($captureData['id']) && $captureData['id']) {
                $invoice->setData('pagaleve_capture_id', $captureData['id']);
                return $this;
            }

        } catch (AlreadyExistsException|LocalizedException|\Laminas\Http\Client\Exception\RuntimeException $e) {
            throw new LocalizedException(__($e->getMessage()));
        }

        if (isset($captureData['exception']) && $captureData['exception'] == 'InvalidTransactionException') {
            throw new LocalizedException(
                __('This transaction could not be performed, please contact Pagaleve.')
            );
        }

        throw new LocalizedException(
            __('It was not possible to complete this transaction at this time, please try again in a few moments.')
        );
    }
}
