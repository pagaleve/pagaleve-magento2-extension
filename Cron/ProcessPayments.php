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
use Pagaleve\Payment\Helper\Config as HelperConfig;
use Pagaleve\Payment\Helper\Data as HelperData;
use Pagaleve\Payment\Model\Request\PaymentRequest;
use Pagaleve\Payment\Model\Request\CheckoutRequest;
use Pagaleve\Payment\Logger\Logger;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;

class ProcessPayments
{
    /** @var Logger $logger */
    protected $logger;

    /** @var OrderCollectionFactory $orderCollectionFactory */
    private $orderCollectionFactory;

    /**
     * @var HelperConfig
     */
    protected $helperConfig;

    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var PaymentRequest
     */
    protected $paymentRequest;

    /**
     * @var CheckoutRequest
     */
    protected $checkoutRequest;

    /**
     * Constructor
     *
     * @param Logger $logger
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param HelperData $helperData
     * @param HelperConfig $helperConfig
     * @param PaymentRequest $paymentRequest
     * @param CheckoutRequest $checkoutRequest
     */
    public function __construct(
        Logger $logger,
        OrderCollectionFactory $orderCollectionFactory,
        HelperData $helperData,
        HelperConfig $helperConfig,
        PaymentRequest $paymentRequest,
        CheckoutRequest $checkoutRequest
    )
    {
        $this->logger = $logger;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->helperData = $helperData;
        $this->helperConfig = $helperConfig;
        $this->paymentRequest = $paymentRequest;
        $this->checkoutRequest = $checkoutRequest;
    }

    /**
     * Execute the cron
     *
     * @return void
     */
    public function execute()
    {
        $collection = $this->orderCollectionFactory->create()
            ->addAttributeToSelect('*');
        $deadLine = $this->helperConfig->getRetryDeadline();
        $fromDate = date('Y-m-d H:i:s', strtotime('- ' . $deadLine . ' days'));
        $toDate = date('Y-m-d H:i:s');
        $paymentMethod = ['pagaleve', 'pagaleve_upfront'];
        $status = $this->helperConfig->getPaymentStatus();
        $collection
            ->addFieldToFilter('status', ['in' => $status])
            ->addFieldToFilter(
                'created_at',
                ['from' => $fromDate, 'to' => $toDate]
            );
        $collection->getSelect()
            ->join(
                ["sop" => "sales_order_payment"],
                'main_table.entity_id = sop.parent_id',
                array('method')
            )
            ->where('sop.method IN (?)', $paymentMethod);
        $this->logger->info($collection->getSelect()->__toString());
        
        foreach ($collection as $order) {
            try {
                //get checkout
                if($order->getPagaleveCheckoutId() != '') {
                    $checkoutData = $this->checkoutRequest->get($order->getPagaleveCheckoutId());
                    $this->logger->info(print_r($checkoutData, true));
                    if(is_array($checkoutData) && isset($checkoutData['state'])) {
                        if($checkoutData['state'] == 'AUTHORIZED') {
                            $this->paymentRequest->setOrder($order);
                            $paymentData = $this->paymentRequest->create();
                            if (count($paymentData) >= 1) {
                                $this->helperData->createInvoice($order, $paymentData);
                                $this->logger->info("Payment create success to orderId: " . $order->getId());
                            }
                        } elseif($checkoutData['state'] == 'COMPLETED') {
                            $paymentData = $this->paymentRequest->get($order->getPagalevePaymentId());
                            if (count($paymentData) >= 1) {
                                $this->helperData->createInvoice($order, $paymentData);
                            }
                        } elseif($checkoutData['state'] == 'EXPIRED' || $checkoutData['state'] == 'CANCELED') {
                            if ($order->canCancel()) {
                                $order->cancel()->save();
                                $this->logger->info("Order was canceled because checkout was '" . $checkoutData['state'] . "'. orderId: " . $order->getId());
                            }
                        }
                    }
                }
            } catch (\Zend_Http_Client_Exception | LocalizedException $e) {
                $this->logger->error($e->getMessage());
            }

        }
    }
}
