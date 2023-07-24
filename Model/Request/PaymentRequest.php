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
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\ResourceModel\Quote as ResourceQuote;
use Pagaleve\Payment\Helper\Config as HelperConfig;
use Pagaleve\Payment\Helper\Data as HelperData;
use Pagaleve\Payment\Logger\Logger;
use Pagaleve\Payment\Model\Config\Source\PaymentAction;

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
    protected Logger $logger;

    /** @var $order */
    protected $order = false;

    /**
     * @param LaminasClientFactory $httpClientFactory
     * @param Json $json
     * @param HelperConfig $helperConfig
     * @param Random $mathRandom
     * @param HelperData $helperData
     * @param ResourceQuote $resourceQuote
     * @param Logger $logger
     */
    public function __construct(
        LaminasClientFactory $httpClientFactory,
        Json $json,
        HelperConfig $helperConfig,
        Random $mathRandom,
        HelperData $helperData,
        ResourceQuote $resourceQuote,
        Logger $logger
    ) {
        parent::__construct($httpClientFactory, $json, $helperConfig, $mathRandom, $helperData, $logger);
        $this->resourceQuote = $resourceQuote;
        $this->logger = $logger;
    }

    /**
     * @return array
     * @throws \Laminas\Http\Client\Exception\RuntimeException|LocalizedException
     */
    public function create(): array
    {
        $this->validate();

        $body = $this->json->serialize($this->prepare());
        $response = $this->makeRequest($this->helperConfig->getPaymentUrl(), \Laminas\Http\Request::METHOD_POST, $body);

        return $this->success($response);
    }

    /**
     * @param $paymentId
     * @return array
     * @throws \Laminas\Http\Client\Exception\RuntimeException|LocalizedException
     */
    public function get($paymentId): array
    {
        $response = $this->makeRequest($this->helperConfig->getPaymentUrl() . "/" . $paymentId, \Laminas\Http\Request::METHOD_GET);
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
            $order->setData('pagaleve_payment_id', $checkoutData['id']);

            /*if ($this->helperConfig->getPaymentAction() == PaymentAction::AUTHORIZE) {
                $order->setData(
                    'pagaleve_expiration_date',
                    $this->helperData->formatDate($checkoutData['authorization']['expiration'])
                );
            }*/
            $order->save();
            //$this->resourceQuote->save($order);
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
        $order = $this->getOrder();
        return [
            'amount' => $this->helperData->formatAmount($order->getGrandTotal()),
            'checkout_id' => $order->getData('pagaleve_checkout_id'),
            'currency' => 'BRL',
            //'intent' => $this->helperConfig->getPaymentAction(),
            'intent' => \Pagaleve\Payment\Model\Config\Source\PaymentAction::AUTHORIZE_AND_CAPTURE,
            'reference' => $order->getIncrementId()
        ];
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
