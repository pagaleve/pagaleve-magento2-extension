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

namespace Pagaleve\Payment\Controller\Checkout;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Pagaleve\Payment\Helper\Data as HelperData;
use Pagaleve\Payment\Model\Request\PaymentRequest;
use Pagaleve\Payment\Model\Request\CheckoutRequest;
use Magento\Checkout\Model\Session;

class Approve implements HttpGetActionInterface
{
    /**
     * @var RedirectFactory
     */
    protected RedirectFactory $resultRedirectFactory;

    /**
     * @var HelperData
     */
    protected HelperData $helperData;

    /**
     * @var PaymentRequest
     */
    protected PaymentRequest $paymentRequest;

    /**
     * @var ManagerInterface
     */
    protected ManagerInterface $messageManager;

    /**
     * @var CheckoutRequest
     */
    protected CheckoutRequest $checkoutRequest;

    /**
     * @var Session
     */
    protected Session $checkoutSession;

    /**
     * Constructor
     *
     * @param RedirectFactory $resultRedirectFactory
     * @param HelperData $helperData
     * @param PaymentRequest $paymentRequest
     * @param ManagerInterface $messageManager
     * @param CheckoutRequest $checkoutRequest
     * @param Session $checkoutSession
     */
    public function __construct(
        RedirectFactory $resultRedirectFactory,
        HelperData $helperData,
        PaymentRequest $paymentRequest,
        ManagerInterface $messageManager,
        CheckoutRequest $checkoutRequest,
        Session $checkoutSession
    ) {
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->helperData = $helperData;
        $this->paymentRequest = $paymentRequest;
        $this->messageManager = $messageManager;
        $this->checkoutRequest = $checkoutRequest;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Execute view action
     *
     * @return Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        try {
            $order = $this->checkoutSession->getLastRealOrder();
            if($order) {
                $checkoutData = $this->checkoutRequest->get($order->getPagaleveCheckoutId());
                if (is_array($checkoutData) && isset($checkoutData['state'])) {
                    if ($checkoutData['state'] == 'AUTHORIZED') {
                        $this->paymentRequest->setOrder($order);
                        $paymentData = $this->paymentRequest->create();
                        if (count($paymentData) >= 1) {
                            $this->helperData->createInvoice($order, $paymentData);
                        }
                    }
                }
            }
            
            $resultRedirect->setPath('checkout/onepage/success?passthrough=true');
            return $resultRedirect;

        } catch (\Zend_Http_Client_Exception | LocalizedException $e) {
            $resultRedirect->setUrl($this->helperData->getCheckoutPaymentUrl());
            return $resultRedirect;
        }
    }
}
