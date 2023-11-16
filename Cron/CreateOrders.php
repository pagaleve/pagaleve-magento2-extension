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
use Pagaleve\Payment\Helper\Data as HelperData;
use Pagaleve\Payment\Model\Request\PaymentRequest;
use Psr\Log\LoggerInterface;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory as QuoteCollectionFactory;

class CreateOrders
{
    /** @var LoggerInterface $logger */
    protected $logger;

    /** @var QuoteCollectionFactory $quoteCollectionFactory */
    private $quoteCollectionFactory;

    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var PaymentRequest
     */
    protected $paymentRequest;

    /**
     * Constructor
     *
     * @param LoggerInterface $logger
     * @param QuoteCollectionFactory $quoteCollectionFactory
     * @param HelperData $helperData
     * @param PaymentRequest $paymentRequest
     */
    public function __construct(
        LoggerInterface $logger,
        QuoteCollectionFactory $quoteCollectionFactory,
        HelperData $helperData,
        PaymentRequest $paymentRequest
    )
    {
        $this->logger = $logger;
        $this->quoteCollectionFactory = $quoteCollectionFactory;
        $this->helperData = $helperData;
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
        $quoteList->addFieldToFilter('is_active', 1);
        $quoteList->addFieldToFilter('pagaleve_checkout_id', ['notnull' => true]);
        $quoteList->addFieldToFilter('pagaleve_payment_id', ['notnull' => true]);

        foreach ($quoteList as $quote) {

            try {

                $checkoutData = $this->paymentRequest->get($quote->getData('pagaleve_payment_id'));
                $orderId = $this->helperData->createOrder($checkoutData, $quote);

                if ($orderId >= 1) {
                    $this->logger->info("Order created success." . $orderId);
                }
            } catch (\Zend_Http_Client_Exception | LocalizedException $e) {
                $this->logger->error($e->getMessage());
            }

        }

        $this->logger->info("Cronjob CreateOrders is executed.");
    }
}
