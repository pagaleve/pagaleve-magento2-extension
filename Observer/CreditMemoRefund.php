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
use Magento\Sales\Model\Order\Creditmemo;
use Pagaleve\Payment\Helper\Config as HelperConfig;
use Pagaleve\Payment\Model\Pagaleve;
use Pagaleve\Payment\Model\PagaleveUpFront;
use Pagaleve\Payment\Model\Request\Payment\RefundRequest;

class CreditMemoRefund implements ObserverInterface
{
    /** @var HelperConfig $helperConfig */
    protected $helperConfig;

    /** @var RefundRequest $refundRequest */
    private $refundRequest;

    /**
     * @param HelperConfig $helperConfig
     * @param $refundRequest
     */
    public function __construct(HelperConfig $helperConfig, RefundRequest $refundRequest)
    {
        $this->helperConfig = $helperConfig;
        $this->refundRequest = $refundRequest;
    }

    /**
     * Update inviter balance if possible
     *
     * @param Observer $observer
     * @return CreditMemoRefund
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        /* @var $creditMemo Creditmemo */
        $creditMemo = $observer->getEvent()->getData('creditmemo');
        $order = $creditMemo->getOrder();

        if (
            $order->getPayment()->getMethod() != Pagaleve::PAYMENT_METHOD_PAGALEVE_CODE &&
            $order->getPayment()->getMethod() != PagaleveUpFront::PAYMENT_METHOD_PAGALEVE_CODE
        ) {
            return $this;
        }

        try {
            $refundData = $this->refundRequest->create(
                $order->getData('pagaleve_payment_id'),
                $creditMemo->getGrandTotal(),
                'REQUESTED_BY_CUSTOMER',
                $creditMemo->getCustomerNote()
            );

            if (isset($refundData['id']) && $refundData['id']) {
                $creditMemo->setData('pagaleve_refund_id', $refundData['id']);
            }

            $creditMemo->save();

        } catch (AlreadyExistsException|LocalizedException|\Zend_Http_Client_Exception $e) {
            throw new LocalizedException(__($e->getMessage()));
            //return $this;
        }

        return $this;
    }
}
