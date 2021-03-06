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

namespace Pagaleve\Payment\Model\Request;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Framework\Math\Random;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\ResourceModel\Quote as ResourceQuote;
use Pagaleve\Payment\Helper\Config as HelperConfig;
use Pagaleve\Payment\Helper\Data as HelperData;
use Pagaleve\Payment\Logger\Logger;
use Pagaleve\Payment\Model\Config\Source\PaymentAction;
use Zend_Http_Client;

class PaymentRequest extends RequestAbstract
{
    /**
     * @var HelperData
     */
    protected HelperData $helperData;

    /**
     * @var ResourceQuote
     */
    protected ResourceQuote $resourceQuote;

    /** @var Logger $logger */
    private Logger $logger;

    /** @var $quote */
    protected $quote = false;

    /**
     * @param ZendClientFactory $httpClientFactory
     * @param Json $json
     * @param HelperConfig $helperConfig
     * @param Random $mathRandom
     * @param HelperData $helperData
     * @param ResourceQuote $resourceQuote
     * @param Logger $logger
     */
    public function __construct(
        ZendClientFactory $httpClientFactory,
        Json $json,
        HelperConfig $helperConfig,
        Random $mathRandom,
        HelperData $helperData,
        ResourceQuote $resourceQuote,
        Logger $logger
    ) {
        parent::__construct($httpClientFactory, $json, $helperConfig, $mathRandom, $helperData);
        $this->resourceQuote = $resourceQuote;
        $this->logger = $logger;
    }

    /**
     * @return array
     * @throws LocalizedException|\Zend_Http_Client_Exception
     */
    public function create(): array
    {
        $this->validate();

        $x = 1;
        do {
            sleep(2);

            $client = $this->getClient($this->helperConfig->getPaymentUrl());
            $body = $this->json->serialize($this->prepare());

            $client->setrawdata($body, 'application/json');
            $client->setmethod(Zend_Http_Client::POST);

            $request = $client->request();
            $requestBody = $request->getbody();

            $this->logger->info(
                'PaymentRequest: ' . $client->getUri() . ' - ' . $requestBody
            );

            if ($request->getstatus() == 201) {
                return $this->success($requestBody);
            }

            $x++;
        } while ($x <= 10);

        return $this->fail($requestBody);
    }

    /**
     * @param $paymentId
     * @return array
     * @throws AlreadyExistsException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws \Zend_Http_Client_Exception
     */
    public function get($paymentId): array
    {
        $client = $this->getClient($this->helperConfig->getPaymentUrl() . "/" . $paymentId);
        $client->setmethod(Zend_Http_Client::GET);

        $request = $client->request();
        $requestBody = $request->getbody();

        $this->logger->info(
            'PaymentRequestGet: ' . $client->getUri() . ' - ' . $requestBody
        );

        if ($request->getstatus() == 200) {
            return $this->json->unserialize($requestBody);
        }
        return [];
    }

    /**
     * @param $requestBody
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws AlreadyExistsException
     */
    protected function success($requestBody): array
    {
        $checkoutData = $this->json->unserialize($requestBody);
        if (isset($checkoutData['id']) && $checkoutData['id']) {
            $quote = $this->getQuote();
            $quote->setData('pagaleve_payment_id', $checkoutData['id']);

            if ($this->helperConfig->getPaymentAction() == PaymentAction::AUTHORIZE) {
                $quote->setData(
                    'pagaleve_expiration_date',
                    $this->helperData->formatDate($checkoutData['authorization']['expiration'])
                );
            }
            $this->resourceQuote->save($quote);
        }
        return $checkoutData;
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
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function prepare() : array
    {
        $quote = $this->getQuote();
        return [
            'amount' => $this->helperData->formatAmount($quote->getGrandTotal()),
            'checkout_id' => $quote->getData('pagaleve_checkout_id'),
            'currency' => 'BRL',
            'intent' => $this->helperConfig->getPaymentAction(),
            'reference' => $quote->getReservedOrderId()
        ];
    }

    /**
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getQuote()
    {
        if ($this->quote) {
            return $this->quote;
        }
        return $this->helperData->getQuote();
    }

    /**
     * @param $quote
     * @return void
     */
    public function setQuote($quote)
    {
        $this->quote = $quote;
    }

    /**
     * @throws LocalizedException
     */
    protected function validate()
    {
        if (!$this->getQuote()->getId()) {
            $message = __('Quote is required');
            throw new LocalizedException(__($message));
        }
    }
}
