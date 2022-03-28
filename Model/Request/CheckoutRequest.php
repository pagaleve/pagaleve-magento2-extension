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
use Magento\Framework\UrlInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\ResourceModel\Quote as ResourceQuote;
use Pagaleve\Payment\Helper\Config as HelperConfig;
use Pagaleve\Payment\Helper\Data as HelperData;
use Pagaleve\Payment\Logger\Logger;
use Zend_Http_Client;

class CheckoutRequest extends RequestAbstract
{
    /**
     * @var HelperData
     */
    public HelperData $helperData;

    /**
     * @var UrlInterface
     */
    private UrlInterface $urlBuilder;

    /**
     * @var ResourceQuote
     */
    private ResourceQuote $resourceQuote;

    /** @var Logger $logger */
    private Logger $logger;

    /**
     * @param ZendClientFactory $httpClientFactory
     * @param Json $json
     * @param HelperConfig $helperConfig
     * @param Random $mathRandom
     * @param HelperData $helperData
     * @param UrlInterface $urlBuilder
     * @param ResourceQuote $resourceQuote
     * @param Logger $logger
     */
    public function __construct(
        ZendClientFactory $httpClientFactory,
        Json $json,
        HelperConfig $helperConfig,
        Random $mathRandom,
        HelperData $helperData,
        UrlInterface $urlBuilder,
        ResourceQuote $resourceQuote,
        Logger $logger
    ) {
        parent::__construct($httpClientFactory, $json, $helperConfig, $mathRandom, $helperData);
        $this->urlBuilder = $urlBuilder;
        $this->resourceQuote = $resourceQuote;
        $this->logger = $logger;
    }

    /**
     * @return array
     * @throws localizedexception|\Zend_Http_Client_Exception
     */
    public function create(): array
    {
        $this->validate();

        $client = $this->getClient($this->helperConfig->getCheckoutUrl());
        $body = $this->json->serialize($this->prepare());

        $client->setrawdata($body, 'application/json');
        $client->setmethod(Zend_Http_Client::POST);

        $request = $client->request();
        $requestBody = $request->getbody();

        $this->logger->info(
            'CheckoutRequest: ' . $client->getUri() . ' - ' . $requestBody
        );

        if ($request->getstatus() == 201) {
            return $this->success($requestBody);
        } else {
            return $this->fail($requestBody);
        }
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
            $quote->setData('pagaleve_checkout_id', $checkoutData['id']);
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
        $this->getQuote()->reserveOrderId();
        $billingAddress = $quote->getBillingAddress();

        $content = [
            'provider' => 'MAGENTO_2',
            'metadata' => [
                'transactionId' => $quote->getReservedOrderId(),
                'merchantName' => $quote->getStore()->getName(),
            ],
            'order' => [
                'reference' => $quote->getReservedOrderId(),
                'tax' => 0,
                'amount' => $this->helperData->formatAmount($quote->getGrandTotal()),
            ],
            'reference' => $quote->getStore()->getName() . ' - ' . $quote->getReservedOrderId(),
            'shopper' => [
                'first_name' => $billingAddress->getFirstname(),
                'last_name' => $billingAddress->getLastname(),
                'phone' => $this->formatPhone($billingAddress->getTelephone()),
                'email' => $billingAddress->getEmail(),
                'cpf' => $quote->getCustomerTaxvat(),
                'billing_address' => [
                    'name' => $billingAddress->getFirstname() .' ' . $billingAddress->getLastname(),
                    'city' => $billingAddress->getCity(),
                    'state' => $billingAddress->getRegionCode(),
                    'zip_code' => $billingAddress->getPostcode(),
                    'street' => $billingAddress->getStreetLine(1),
                    'number' => $billingAddress->getStreetLine(2),
                    'neighborhood' => $billingAddress->getStreetLine(3),
                    'complement' => $billingAddress->getStreetLine(4),
                    'phone_number' => $this->formatPhone($billingAddress->getTelephone())
                ]
            ],
            'webhook_url' => $this->urlBuilder->getUrl('pagaleve/webhook'),
            'approve_url' => $this->urlBuilder->getUrl('pagaleve/checkout/approve'),
            'cancel_url' => $this->urlBuilder->getUrl('pagaleve/checkout/cancel')
        ];

        $items = [];
        foreach ($quote->getAllVisibleItems() as $item) {
            $items[] = [
                'name' => $item->getName(),
                'quantity' => $item->getQty(),
                'price' => $this->helperData->formatAmount($item->getPrice()),
                'reference' => $item->getSku()
            ];
        }

        $content['order']['items'] = $items;

        return $content;
    }

    /**
     * @return Quote
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getQuote(): Quote
    {
        return $this->helperData->getQuote();
    }

    /**
     * @throws localizedexception
     */
    protected function validate()
    {
        if (!$this->getQuote()->getId()) {
            $message = __('Quote is required');
            throw new localizedexception(__($message));
        }
    }
}
