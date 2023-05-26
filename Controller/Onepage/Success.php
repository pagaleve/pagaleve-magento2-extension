<?php
/*
 * @Author: Warley Elias
 * @Email: warley.elias@pentagrama.com.br
 * @Date: 2022-12-12 08:17:19
 * @Last Modified by: Warley Elias
 * @Last Modified time: 2023-05-26 14:46:51
 */

namespace Pagaleve\Payment\Controller\Onepage;

use Magento\Checkout\Controller\Onepage\Success as CheckoutOnepageSuccess;
use Pagaleve\Payment\Helper\Config as HelperConfig;

class Success extends CheckoutOnepageSuccess {
    //Create a variable and a constructor for class Pagaleve\Payment\Helper\Data as HelperData
    /**
     * @var HelperConfig
     */
    protected $helperConfig;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Customer\Api\AccountManagementInterface $accountManagement
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\Translate\InlineInterface $translateInline
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\View\LayoutFactory $layoutFactory
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param HelperConfig $helperConfig
     *
     * @codeCoverageIgnore
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Api\AccountManagementInterface $accountManagement,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\Translate\InlineInterface $translateInline,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\View\LayoutFactory $layoutFactory,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        HelperConfig $helperConfig
    ) {
        $this->helperConfig = $helperConfig;
        parent::__construct(
            $context, 
            $customerSession, 
            $customerRepository, 
            $accountManagement, 
            $coreRegistry,
            $translateInline,
            $formKeyValidator,
            $scopeConfig,
            $layoutFactory,
            $quoteRepository,
            $resultPageFactory,
            $resultLayoutFactory,
            $resultRawFactory,
            $resultJsonFactory
        );
    }

    public function execute() {
        $session = $this->getOnepage()->getCheckout();
        if (!$this->_objectManager->get(\Magento\Checkout\Model\Session\SuccessValidator::class)->isValid()) {
            return $this->resultRedirectFactory->create()->setPath('checkout/cart');
        }
        
        $order = $session->getLastRealOrder();

        $passthroug = $this->getRequest()->getParam('passthrough');
        $isTransparentCheckoutEnabled = $this->helperConfig->isTransparentCheckoutEnabled();
        if (    ($order->getPayment()->getMethod() == 'pagaleve' || $order->getPayment()->getMethod() == 'pagaleve_upfront')
                && !$passthroug && !$isTransparentCheckoutEnabled) {
            //Redirect to Pagaleve
            $pagaleveCheckoutUrl = $order->getPagaleveCheckoutUrl();
            $this->_redirect($pagaleveCheckoutUrl);
            return;
        }
        return parent::execute();
    }
}