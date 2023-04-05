<?php
/*
 * @Author: Warley Elias
 * @Email: warley.elias@pentagrama.com.br
 * @Date: 2023-03-30 15:55:37
 * @Last Modified by: Warley Elias
 * @Last Modified time: 2023-04-04 16:58:51
 */

namespace Pagaleve\Payment\Block\Order;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\Order\Config;
use Magento\Framework\UrlInterface;
use Pagaleve\Payment\Helper\Config as HelperConfig;

class Success extends Template {
    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var Config
     */
    protected $orderConfig;

    /**
     * @var HttpContext
     */
    protected $httpContext;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var HelperConfig
     */
    protected $helperConfig;


    /**
     * @param Context     $context
     * @param Session     $checkoutSession
     * @param Config      $orderConfig
     * @param HttpContext $httpContext
     * @param UrlInterface $urlBuilder
     * @param HelperConfig $helperConfig
     * @param array       $data
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        Config $orderConfig,
        HttpContext $httpContext,
        UrlInterface $urlBuilder,
        HelperConfig $helperConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->checkoutSession = $checkoutSession;
        $this->orderConfig = $orderConfig;
        $this->httpContext = $httpContext;
        $this->urlBuilder = $urlBuilder;
        $this->helperConfig = $helperConfig;
    }

    /**
     * Get Payment.
     *
     * @return \Magento\Payment\Model\MethodInterface
     */
    public function getPayment()
    {
        $order = $this->checkoutSession->getLastRealOrder();

        return $order->getPayment()->getMethodInstance();
    }

    /**
     * Method Code.
     *
     * @return string
     */
    public function getMethodCode()
    {
        return $this->getPayment()->getCode();
    }

    /**
     * Info payment.
     *
     * @param string $info
     *
     * @return string
     */
    public function getInfo(string $info)
    {
        return  $this->getPayment()->getInfoInstance()->getAdditionalInformation($info);
    }

    /**
     * Get Order.
     *
     */
    public function getOrder() {
        return $this->checkoutSession->getLastRealOrder();
    }

    public function getRetrieveAbandonedCartUrl($orderId) {
        return $this->urlBuilder->getUrl('pagaleve/checkout/abandon', ['orderId' => $orderId]);
    }

    public function isTransparentCheckoutEnabled() {
        return $this->helperConfig->isTransparentCheckoutEnabled();
    }
}
