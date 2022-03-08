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
     * Constructor
     *
     * @param RedirectFactory $resultRedirectFactory
     * @param HelperData $helperData
     * @param PaymentRequest $paymentRequest
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        RedirectFactory $resultRedirectFactory,
        HelperData $helperData,
        PaymentRequest $paymentRequest,
        ManagerInterface $messageManager
    ) {
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->helperData = $helperData;
        $this->paymentRequest = $paymentRequest;
        $this->messageManager = $messageManager;
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
            $checkoutData = $this->paymentRequest->create();
            if (count($checkoutData) <= 0) {
                $resultRedirect->setUrl($this->helperData->getCheckoutPaymentUrl());
                return $resultRedirect;
            }

            $orderId = $this->helperData->createOrder($checkoutData);
            if ($orderId >= 1) {
                $resultRedirect->setPath('checkout/onepage/success');
                return $resultRedirect;
            }

            $resultRedirect->setUrl($this->helperData->getCheckoutPaymentUrl());
            return $resultRedirect;

        } catch (\Zend_Http_Client_Exception | LocalizedException $e) {
            $resultRedirect->setUrl($this->helperData->getCheckoutPaymentUrl());
            return $resultRedirect;
        }
    }
}
