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

namespace Pagaleve\Payment\Model\Request\Payment;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Framework\Math\Random;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\ResourceModel\Order\Invoice as ResourceInvoice;
use Pagaleve\Payment\Helper\Config as HelperConfig;
use Pagaleve\Payment\Helper\Data as HelperData;
use Pagaleve\Payment\Logger\Logger;
use Zend_Http_Client;
use Pagaleve\Payment\Model\Request\RequestAbstract;

class RefundRequest extends RequestAbstract
{
    /** @var HelperData $helperData */
    protected $helperData;

    /** @var ResourceInvoice $resourceInvoice */
    protected $resourceInvoice;

    /** @var Invoice $invoice */
    protected $invoice;

    /** @var Logger $logger */
    private $logger;

    /**
     * @param ZendClientFactory $httpClientFactory
     * @param Json $json
     * @param HelperConfig $helperConfig
     * @param Random $mathRandom
     * @param HelperData $helperData
     * @param ResourceInvoice $resourceInvoice
     * @param Logger $logger
     */
    public function __construct(
        ZendClientFactory $httpClientFactory,
        Json $json,
        HelperConfig $helperConfig,
        Random $mathRandom,
        HelperData $helperData,
        ResourceInvoice $resourceInvoice,
        Logger $logger
    ) {
        parent::__construct($httpClientFactory, $json, $helperConfig, $mathRandom, $helperData);
        $this->resourceInvoice = $resourceInvoice;
        $this->logger = $logger;
    }

    /**
     * @param $paymentId
     * @param $amount
     * @param $reason
     * @param $description
     * @return array
     * @throws LocalizedException
     * @throws \Zend_Http_Client_Exception
     */
    public function create($paymentId, $amount, $reason, $description): array
    {
        $uri = sprintf($this->helperConfig->getPaymentRefundUrl(), $paymentId);

        $client = $this->getClient($uri);
        $body = $this->json->serialize($this->prepare($amount, $reason, $description));

        $client->setrawdata($body, 'application/json');
        $client->setmethod(Zend_Http_Client::POST);

        $request = $client->request();
        $requestBody = $request->getbody();

        $this->logger->info(
            'RefundRequest: ' . $client->getUri() . ' - ' . $requestBody
        );

        if ($request->getstatus() == 200) {
            return $this->success($requestBody);
        } else {
            return $this->fail($requestBody);
        }
    }

    /**
     * @param $requestBody
     * @return array
     */
    protected function success($requestBody): array
    {
        return $this->json->unserialize($requestBody);
    }

    /**
     * @param $response
     * @return array
     */
    protected function fail($response): array
    {
        return [];
    }

    /**
     * @param $amount
     * @param $reason
     * @param $description
     * @return array
     */
    protected function prepare($amount, $reason, $description) : array
    {
        return [
            'amount' => $this->helperData->formatAmount($amount),
            'reason' => $reason,
            'description' => $description
        ];
    }
}
