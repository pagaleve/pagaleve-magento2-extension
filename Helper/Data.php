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
use Magento\Framework\Module\ModuleListInterface;

class Data extends AbstractHelper
{
    const MODULE_NAME = 'Pagaleve_Payment';

    /** @var QuoteManagement $quoteManagement */
    protected $quoteManagement;

    /** @var CheckoutSession $checkoutSession */
    protected $checkoutSession;

    /** @var UrlInterface $urlBuilder */
    private $urlBuilder;

    /** @var InvoiceService $invoiceService */
    protected $invoiceService;

    /** @var Transaction $transaction */
    protected $transaction;

    /** @var InvoiceSender $invoiceSender */
    protected $invoiceSender;

    /** @var OrderRepositoryInterface $orderRepository */
    protected $orderRepository;

    /** @var Config $helperConfig */
    private $helperConfig;

    /** @var Logger $logger */
    private $logger;

    /** 
     * @var ModuleListInterface $_moduleList 
     */
    protected $_moduleList;

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
     * @param ModuleListInterface $moduleList
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
        Logger $logger,
        ModuleListInterface $moduleList
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
        $this->_moduleList = $moduleList;
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

        $order->setState(Order::STATE_NEW);
        $order->setStatus($this->helperConfig->getPaymentStatusNew());

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
        $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
        $invoice->register();

        $captureId = $this->getCaptureIdByCheckoutData($checkoutData);
        if ($captureId) {
            $invoice->setData('pagaleve_capture_id', $checkoutData['id']);
        }

        $invoice->save();
        $transactionSave = $this->transaction->addObject($invoice)->addObject($invoice->getOrder());
        $transactionSave->save();
        $this->invoiceSender->send($invoice);

        $orderState = Order::STATE_PROCESSING;
        $order->setState($orderState)->setStatus($orderState);
        $order->addStatusHistoryComment(
                __('Notified customer about invoice creation #%1.', $invoice->getId())
            )->setIsCustomerNotified(true);
        $order->save();
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
        $amount = (float) $amount;
        $amount = round($amount, 2) * 100;
        $amount = (string) $amount;

        return $this->onlyNumbers($amount);
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

    public function getModuleVersion()
    {
        return $this->_moduleList->getOne(self::MODULE_NAME)['setup_version'];
    }
}
