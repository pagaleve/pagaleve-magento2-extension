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
use Magento\Framework\HTTP\LaminasClientFactory;
use Magento\Framework\Math\Random;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\ResourceModel\Order\Invoice as ResourceInvoice;
use Pagaleve\Payment\Helper\Config as HelperConfig;
use Pagaleve\Payment\Helper\Data as HelperData;
use Pagaleve\Payment\Logger\Logger;
use Pagaleve\Payment\Model\Request\RequestAbstract;

class RefundRequest extends RequestAbstract
{
    /** @var HelperData $helperData */
    protected HelperData $helperData;

    /** @var ResourceInvoice $resourceInvoice */
    protected ResourceInvoice $resourceInvoice;

    /** @var Invoice $invoice */
    protected Invoice $invoice;

    /** @var Logger $logger */
    protected Logger $logger;

    /**
     * @param LaminasClientFactory $httpClientFactory
     * @param Json $json
     * @param HelperConfig $helperConfig
     * @param Random $mathRandom
     * @param HelperData $helperData
     * @param ResourceInvoice $resourceInvoice
     * @param Logger $logger
     */
    public function __construct(
        LaminasClientFactory $httpClientFactory,
        Json $json,
        HelperConfig $helperConfig,
        Random $mathRandom,
        HelperData $helperData,
        ResourceInvoice $resourceInvoice,
        Logger $logger
    ) {
        parent::__construct($httpClientFactory, $json, $helperConfig, $mathRandom, $helperData, $logger);
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
     * @throws \Laminas\Http\Client\Exception\RuntimeException
     */
    public function create($paymentId, $amount, $reason, $description): array
    {
        $uri = sprintf($this->helperConfig->getPaymentRefundUrl(), $paymentId);
        $body = $this->json->serialize($this->prepare($amount, $reason, $description));
        $response = $this->makeRequest($uri, \Laminas\Http\Request::METHOD_POST, $body);

        return $this->success($response);
    }

    /**
     * @param $requestBody
     * @return array
     */
    protected function success($requestBody): array
    {
        return $requestBody;
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
