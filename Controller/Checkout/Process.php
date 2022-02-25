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
use Magento\Framework\Exception\NoSuchEntityException;
use Pagaleve\Payment\Helper\Data as HelperData;
use Pagaleve\Payment\Model\Request\CheckoutRequest;

class Process implements HttpGetActionInterface
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
     * @var CheckoutRequest
     */
    protected CheckoutRequest $checkoutRequest;

    /**
     * Constructor
     *
     * @param RedirectFactory $resultRedirectFactory
     * @param HelperData $helperData
     * @param CheckoutRequest $checkoutRequest
     */
    public function __construct(
        RedirectFactory $resultRedirectFactory,
        HelperData $helperData,
        CheckoutRequest $checkoutRequest
    ) {
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->helperData = $helperData;
        $this->checkoutRequest = $checkoutRequest;
    }

    /**
     * @return Redirect
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws \Zend_Http_Client_Exception
     */
    public function execute(): Redirect
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        $quote = $this->helperData->getQuote();
        if (!$quote->getId()) {
            $resultRedirect->setUrl($this->helperData->getCheckoutPaymentUrl());
            return $resultRedirect;
        }

        $checkoutData = $this->checkoutRequest->create();
        if (count($checkoutData) <= 0) {
            $resultRedirect->setUrl($this->helperData->getCheckoutPaymentUrl());
            return $resultRedirect;
        }

        $resultRedirect->setUrl($checkoutData['checkout_url']);
        return $resultRedirect;
    }
}
