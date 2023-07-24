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
use Magento\Framework\HTTP\LaminasClientFactory;
use Magento\Framework\Math\Random;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\UrlInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\ResourceModel\Quote as ResourceQuote;
use Pagaleve\Payment\Helper\Config as HelperConfig;
use Pagaleve\Payment\Helper\Data as HelperData;
use Pagaleve\Payment\Logger\Logger;
use Magento\Framework\Module\ModuleListInterface;

class CheckoutRequest extends RequestAbstract
{
    const MODULE_NAME = 'Pagaleve_Payment';
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
    protected Logger $logger;

    /** @var $order */
    protected $order = false;

    /** 
     * @var ModuleListInterface $_moduleList 
     */
    protected $_moduleList;

    /**
     * @param LaminasClientFactory $httpClientFactory
     * @param Json $json
     * @param HelperConfig $helperConfig
     * @param Random $mathRandom
     * @param HelperData $helperData
     * @param UrlInterface $urlBuilder
     * @param ResourceQuote $resourceQuote
     * @param Logger $logger
     * @param ModuleListInterface $moduleList
     */
    public function __construct(
        LaminasClientFactory $httpClientFactory,
        Json $json,
        HelperConfig $helperConfig,
        Random $mathRandom,
        HelperData $helperData,
        UrlInterface $urlBuilder,
        ResourceQuote $resourceQuote,
        Logger $logger,
        ModuleListInterface $moduleList
    ) {
        parent::__construct($httpClientFactory, $json, $helperConfig, $mathRandom, $helperData, $logger);
        $this->urlBuilder = $urlBuilder;
        $this->resourceQuote = $resourceQuote;
        $this->logger = $logger;
        $this->_moduleList = $moduleList;
    }

    public function getVersion()
    {
        return $this->_moduleList
            ->getOne(self::MODULE_NAME)['setup_version'];
    }

    /**
     * @return array
     * @throws \Laminas\Http\Client\Exception\RuntimeException|LocalizedException
     */
    public function create($pixUpFront = false): array
    {
        $this->validate();

        $body = $this->json->serialize($this->prepare($pixUpFront));
        $response = $this->makeRequest($this->helperConfig->getCheckoutUrl(), \Laminas\Http\Request::METHOD_POST, $body);

        return $this->success($response);
    }

    /**
     * @param $checkoutId
     * @return array
     * @throws \Laminas\Http\Client\Exception\RuntimeException|LocalizedException
     */
    public function get($checkoutId): array
    {
        $response = $this->makeRequest($this->helperConfig->getCheckoutUrl() . "/" . $checkoutId, \Laminas\Http\Request::METHOD_GET);

        return $response;
    }

    /**
     * @param $checkoutData
     * @return array
     * @throws \Laminas\Http\Client\Exception\RuntimeException|LocalizedException
     */
    protected function success($checkoutData): array
    {
        if (isset($checkoutData['id']) && $checkoutData['id']) {
            $order = $this->getOrder();
            $order->setData('pagaleve_checkout_id', $checkoutData['id']);
            $order->setData('pagaleve_checkout_url', $checkoutData['checkout_url']);
            //$this->resourceQuote->save($quote);
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
    protected function prepare($pixUpFront) : array
    {
        $order = $this->getOrder();
        $billingAddress = $order->getBillingAddress();

        $content = [
            'provider' => 'MAGENTO_2',
            'metadata' => [
                'transactionId' => $order->getIncrementId(),
                'merchantName' => $order->getStore()->getName(),
                'version' => $this->getVersion()
            ],
            'order' => [
                'reference' => $order->getIncrementId(),
                'tax' => 0,
                'amount' => $this->helperData->formatAmount($order->getGrandTotal()),
            ],
            'reference' => $order->getStore()->getName() . ' - ' . $order->getIncrementId(),
            'is_pix_upfront' => $pixUpFront,
            'shopper' => [
                'first_name' => $billingAddress->getFirstname(),
                'last_name' => $billingAddress->getLastname(),
                'phone' => $this->formatPhone($billingAddress->getTelephone()),
                'email' => $billingAddress->getEmail(),
                'cpf' => $order->getCustomerTaxvat(),
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
        foreach ($order->getAllVisibleItems() as $item) {
            $items[] = [
                'name' => $item->getName(),
                'quantity' => $item->getQtyOrdered(),
                'price' => $this->helperData->formatAmount($item->getPrice()),
                'reference' => $item->getSku()
            ];
        }

        $content['order']['items'] = $items;

        return $content;
    }

    /**
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getOrder()
    {
        if (!$this->order) {
            $message = __('Order is required');
            throw new LocalizedException(__($message));
        }
        return $this->order;
    }

    /**
     * @param $order
     * @return void
     */
    public function setOrder($order)
    {
        $this->order = $order;
    }

    /**
     * @throws LocalizedException
     */
    protected function validate()
    {
        $order = $this->getOrder();
        if (!$order->getIncrementId()) {
            $message = __('Order is required');
            throw new LocalizedException(__($message));
        }
    }
}
