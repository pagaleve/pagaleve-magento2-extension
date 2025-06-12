<?php
/*
 * @Author: Warley Elias
 * @Email: warley.elias@pentagrama.com.br
 * @Date: 2023-08-10 09:27:02
 * @Last Modified by: Warley Elias
 * @Last Modified time: 2023-08-10 13:33:13
 */

namespace Pagaleve\Payment\Controller\Webhook;

use Pagaleve\Payment\Logger\Logger;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Pagaleve\Payment\Helper\Data as HelperData;
use Pagaleve\Payment\Model\Request\PaymentRequest;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\Exception\LocalizedException;
use Pagaleve\Payment\Helper\Config as HelperConfig;
use Pagaleve\Payment\Model\Request\CheckoutRequest;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;

// class Index extends Action
class Index extends Action implements CsrfAwareActionInterface, HttpPostActionInterface {
    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    /** 
     * @var Logger $logger 
     */
    protected $logger;

    /** 
     * @var OrderCollectionFactory $orderCollectionFactory 
     */
    protected $orderCollectionFactory;

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
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param Logger $logger
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param HelperData $helperData
     * @param HelperConfig $helperConfig
     * @param PaymentRequest $paymentRequest
     * @param CheckoutRequest $checkoutRequest
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        Logger $logger,
        OrderCollectionFactory $orderCollectionFactory,
        HelperData $helperData,
        HelperConfig $helperConfig,
        PaymentRequest $paymentRequest,
        CheckoutRequest $checkoutRequest
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->logger = $logger;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->helperData = $helperData;
        $this->helperConfig = $helperConfig;
        $this->paymentRequest = $paymentRequest;
        $this->checkoutRequest = $checkoutRequest;
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(
        RequestInterface $request
    ): ?InvalidRequestException {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    protected function getSignatureFromRequest() {
        // Tentar diferentes formas de capturar o header
        $methods = [
            'X-Pagaleve-Signature',
            'HTTP_X_PAGALEVE_SIGNATURE'
        ];
        
        $headers = $this->getRequest()->getHeaders();
        foreach ($methods as $method) {
            $signature = $this->getRequest()->getHeader($method);
            if (!empty($signature)) {
                return $signature;
            }
            
            $signature = $this->getRequest()->getServer($method);
            if (!empty($signature)) {
                return $signature;
            }
            if ($headers->has($method)) {
                return $headers->get($method)->getFieldValue();
            }
        }
        
        return null;
    }
        
    /**
     * return bool
     */
    protected function _isAllowed() {
        $body = $this->getRequest()->getContent();
        $secret = $this->helperConfig->getSecretKey();
        if (empty($secret)) {
            $this->logger->info('Secret key is not set');
            return false;
        }
        $signatureHeader = $this->getSignatureFromRequest();
        if (empty($signatureHeader)) {
            $this->logger->info('Signature header is not set');
            return false;
        }
        if (empty($body)) {
            $this->logger->info('Request body is empty');
            return false;
        }
        // Validate the signature
        $signature = hash_hmac('sha256', $body, $secret);
        // Use hash_equals to prevent timing attacks
        if (!hash_equals($signature, $signatureHeader)) {
            $this->logger->info('Pagaleve: Signature does not match');
            return false;
        }
        return true;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json
     * @throws LocalizedException
     * @throws \Laminas\Http\Client\Exception\RuntimeException
     */
    public function execute() {
        //get body request
        $body = $this->getRequest()->getContent();
        $postData = json_decode($body, true);
        if (!$this->_isAllowed()) {
            $this->logger->info('Pagaleve: Unauthorized request');
            return $this->jsonFactory->create()->setData(['error' => 'Unauthorized request']);
        }
        if (empty($postData)) {
            $this->logger->info('Pagaleve: No data received');
            return $this->jsonFactory->create()->setData(['error' => 'No data received']);
        }
        if(!isset($postData['id'])) {
            $this->logger->info('Pagaleve: No checkout id received');
            return $this->jsonFactory->create()->setData(['error' => 'No checkout id received']);
        }
        if (!isset($postData['state'])) {
            $this->logger->info('Pagaleve: No state received');
            return $this->jsonFactory->create()->setData(['error' => 'No state received']);
        }

        $_orderCollection = $this->orderCollectionFactory->create()
            ->addAttributeToSelect('*');
        $_orderCollection->addFieldToFilter('pagaleve_checkout_id', $postData['id']);

        if ($_orderCollection->getSize() == 0) {
            $this->logger->info('Pagaleve: Order not found');
            return $this->jsonFactory->create()->setData(['success' => false, 'message' => 'Order not found']);
        }

        $_order = $_orderCollection->getFirstItem();
        $checkoutState = $postData['state'];
        if ($checkoutState == 'AUTHORIZED') {
            $this->paymentRequest->setOrder($_order);
            $paymentData = $this->paymentRequest->create();
            if (count($paymentData) >= 1) {
                $this->helperData->createInvoice($_order, $paymentData);
                $this->logger->info("Payment create success to orderId: " . $_order->getId());
            } else {
                $this->logger->info("Payment create error to orderId: " . $_order->getId());
                return $this->jsonFactory->create()->setData(['success' => false, 'message' => 'Payment create error']);
            }
        } elseif ($checkoutState == 'COMPLETED') {
            if ($_order->getPagalevePaymentId()) {
                $paymentData = $this->paymentRequest->get($_order->getPagalevePaymentId());
                if (count($paymentData) >= 1) {
                    $this->helperData->createInvoice($_order, $paymentData);
                } else {
                    $this->logger->info("Payment get error to orderId: " . $_order->getId());
                    return $this->jsonFactory->create()->setData(['success' => false, 'message' => 'Payment get error']);
                }
            }
        } elseif ($checkoutState == 'EXPIRED' || $checkoutState == 'CANCELED') {
            if ($_order->canCancel()) {
                $_order->cancel()->save();
                $this->logger->info("Order was canceled because checkout was '" . $checkoutState . "'. orderId: " . $_order->getId());
            } else {
                $this->logger->info("Order can't be canceled. orderId: " . $_order->getId());
                return $this->jsonFactory->create()->setData(['success' => false, 'message' => 'Order can\'t be canceled']);
            }
        }

        return $this->jsonFactory->create()->setData(['success' => true]);
    }
}