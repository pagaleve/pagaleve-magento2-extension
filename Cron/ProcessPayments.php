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

namespace Pagaleve\Payment\Cron;

use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory as QuoteCollectionFactory;
use Pagaleve\Payment\Helper\Config as HelperConfig;
use Pagaleve\Payment\Model\Request\PaymentRequest;
use Psr\Log\LoggerInterface;

class ProcessPayments
{
    /** @var LoggerInterface $logger */
    protected LoggerInterface $logger;

    /** @var QuoteCollectionFactory $quoteCollectionFactory */
    private QuoteCollectionFactory $quoteCollectionFactory;

    /**
     * @var HelperConfig
     */
    protected HelperConfig $helperConfig;

    /**
     * @var PaymentRequest
     */
    protected PaymentRequest $paymentRequest;

    /**
     * Constructor
     *
     * @param LoggerInterface $logger
     * @param QuoteCollectionFactory $quoteCollectionFactory
     * @param HelperConfig $helperConfig
     * @param PaymentRequest $paymentRequest
     */
    public function __construct(
        LoggerInterface $logger,
        QuoteCollectionFactory $quoteCollectionFactory,
        HelperConfig $helperConfig,
        PaymentRequest $paymentRequest
    ) {
        $this->logger = $logger;
        $this->quoteCollectionFactory = $quoteCollectionFactory;
        $this->helperConfig = $helperConfig;
        $this->paymentRequest = $paymentRequest;
    }

    /**
     * Execute the cron
     *
     * @return void
     */
    public function execute()
    {
        $quoteList = $this->quoteCollectionFactory->create();
        $quoteList->addFieldToFilter('pagaleve_checkout_id', ['notnull' => true]);
        $quoteList->addFieldToFilter('pagaleve_payment_id', ['null' => true]);

        $deadLine = $this->helperConfig->getRetryDeadline();
        $fromDate = date('Y-m-d H:i:s', strtotime('- ' . $deadLine . ' minute'));
        $toDate = date('Y-m-d H:i:s');
        $quoteList->addFieldToFilter('updated_at', ['from' => $fromDate, 'to' => $toDate]);

        /** @var Quote $quote */
        foreach ($quoteList as $quote) {
            try {
                $this->paymentRequest->setQuote($quote);
                $checkoutData = $this->paymentRequest->create();
                if (count($checkoutData) >= 1) {
                    $this->logger->info("Payment create success to quoteId: " . $quote->getId());
                }
            } catch (\Zend_Http_Client_Exception | LocalizedException $e) {
                $this->logger->error($e->getMessage());
            }
        }
    }
}
