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

namespace Pagaleve\Payment\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteManagement;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\Transaction;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Pagaleve\Payment\Model\Config\Source\PaymentAction;
use Pagaleve\Payment\Model\Pagaleve;
use Pagaleve\Payment\Logger\Logger;

class Data extends AbstractHelper
{
    /** @var QuoteManagement $quoteManagement */
    protected QuoteManagement $quoteManagement;

    /** @var CheckoutSession $checkoutSession */
    protected CheckoutSession $checkoutSession;

    /** @var UrlInterface $urlBuilder */
    private UrlInterface $urlBuilder;

    /** @var InvoiceService $invoiceService */
    protected InvoiceService $invoiceService;

    /** @var Transaction $transaction */
    protected Transaction $transaction;

    /** @var InvoiceSender $invoiceSender */
    protected InvoiceSender $invoiceSender;

    /** @var OrderRepositoryInterface $orderRepository */
    protected OrderRepositoryInterface $orderRepository;

    /** @var Config $helperConfig */
    private Config $helperConfig;

    /** @var Logger $logger */
    private Logger $logger;

    /**
     * @param Context $context
     * @param QuoteManagement $quoteManagement
     * @param CheckoutSession $checkoutSession
     * @param UrlInterface $urlBuilder
     * @param InvoiceService $invoiceService
     * @param InvoiceSender $invoiceSender
     * @param Transaction $transaction
     * @param OrderRepositoryInterface $orderRepository
     * @param Config $helperConfig
     * @param Logger $logger
     */
    public function __construct(
        Context $context,
        QuoteManagement $quoteManagement,
        CheckoutSession $checkoutSession,
        UrlInterface $urlBuilder,
        InvoiceService $invoiceService,
        InvoiceSender $invoiceSender,
        Transaction $transaction,
        OrderRepositoryInterface $orderRepository,
        Config $helperConfig,
        Logger $logger
    ) {
        $this->quoteManagement = $quoteManagement;
        $this->checkoutSession = $checkoutSession;
        $this->urlBuilder = $urlBuilder;
        $this->invoiceService = $invoiceService;
        $this->invoiceSender = $invoiceSender;
        $this->transaction = $transaction;
        $this->orderRepository = $orderRepository;
        $this->helperConfig = $helperConfig;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * @return Quote
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getQuote(): Quote
    {
        return $this->checkoutSession->getQuote();
    }

    /**
     * Create Order On Your Store
     *
     * @param $checkoutData
     * @param null $quote
     * @return integer
     *
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function createOrder($checkoutData, $quote = null): int
    {

        if (!$quote) {
            $quote = $this->checkoutSession->getQuote();
        }

        $quote->setPaymentMethod(Pagaleve::PAYMENT_METHOD_PAGALEVE_CODE);
        $quote->getPayment()->importData(['method' => Pagaleve::PAYMENT_METHOD_PAGALEVE_CODE]);
        $quote->setData('trigger_recollect', 1)->setTotalsCollectedFlag(true);
        $quote->collectTotals()->save();

        if ($checkoutData['amount'] != $this->formatAmount($quote->getGrandTotal())) {
            $this->logger->info(
                'HelperData: Order totals divergent in magento and Pagaleve payment: '. $checkoutData['amount']
                . ' | ' . $this->formatAmount($quote->getGrandTotal())
            );
            return 0;
        }

        if (!in_array($checkoutData['state'], ['AUTHORIZED', 'CAPTURED'])) {
            $this->logger->info(
                'HelperData: Pagaleve payment not AUTHORIZED or CAPTURED: ' . $checkoutData['state']
            );
            return 0;
        }

        $order = $this->quoteManagement->submit($quote);

        $order->setEmailSent(0);
        if (!$order->getEntityId()) {
            $this->logger->info('HelperData: Error on create order');
            return 0;
        }

        if ($this->helperConfig->getPaymentAction() == PaymentAction::AUTHORIZE_AND_CAPTURE) {
            $this->createInvoice($order, $checkoutData);
            $order->setState(Order::STATE_PROCESSING);
            $order->setStatus($this->helperConfig->getPaymentStatusProcessing());
        } else {
            $order->setState(Order::STATE_NEW);
            $order->setStatus($this->helperConfig->getPaymentStatusNew());
        }

        $this->orderRepository->save($order);

        $this->checkoutSession->setLastQuoteId($quote->getId());
        $this->checkoutSession->setLastSuccessQuoteId($quote->getId());
        $this->checkoutSession->setLastOrderId($order->getId());
        $this->checkoutSession->setLastRealOrderId($order->getIncrementId());
        $this->checkoutSession->setLastOrderStatus($order->getStatus());
        return (int) $order->getId();
    }

    /**
     * @param $order
     * @param $checkoutData
     * @return void
     * @throws LocalizedException
     */
    public function createInvoice($order, $checkoutData)
    {
        if (!$order->canInvoice()) {
            return;
        }

        $invoice = $this->invoiceService->prepareInvoice($order);
        $invoice->register();

        $captureId = $this->getCaptureIdByCheckoutData($checkoutData);
        if ($captureId) {
            $invoice->setData('pagaleve_capture_id', $checkoutData['id']);
        }

        $invoice->save();
        $transactionSave = $this->transaction->addObject($invoice)->addObject($invoice->getOrder());
        $transactionSave->save();
        $this->invoiceSender->send($invoice);

        $order->addStatusHistoryComment(
            __('Notified customer about invoice creation #%1.', $invoice->getId())
        )->setIsCustomerNotified(true)->save();
    }

    /**
     * @param $checkoutData
     * @return string
     */
    protected function getCaptureIdByCheckoutData($checkoutData): string
    {
        if (isset($checkoutData['authorization']['captures'])
            && count($checkoutData['authorization']['captures']) >= 1) {
            $result = reset($checkoutData['authorization']['captures']);
            return $result['id'] ?? '';
        }
        return '';
    }

    /**
     * @return string
     */
    public function getCheckoutPaymentUrl(): string
    {
        return rtrim($this->urlBuilder->getUrl('checkout#payment'), '/');
    }

    /**
     * @param $amount
     * @return int
     */
    public function formatAmount($amount): ?int
    {
        return $this->onlyNumbers(round($amount, 2) * 100);
    }

    /**
     * @param $string
     * @return int
     */
    public function onlyNumbers($string): ?int
    {
        return (int) preg_replace('/[^0-9]/', '', $string);
    }

    /**
     * @param $date
     * @return false|string
     */
    public function formatDate($date)
    {
        return date('Y-m-d H:i:s', strtotime($date));
    }
}
