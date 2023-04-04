<?php
/*
 * @Author: Warley Elias
 * @Email: warley.elias@pentagrama.com.br
 * @Date: 2023-04-03 09:25:34
 * @Last Modified by: Warley Elias
 * @Last Modified time: 2023-04-03 13:42:36
 */

namespace Pagaleve\Payment\Controller\Checkout;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Api\CartRepositoryInterface;

class Abandon extends Action
{

    /**
     * @var OrderRepositoryInterface
     */
    protected OrderRepositoryInterface $orderRepository;

    /**
     * @var CheckoutSession
     */
    protected CheckoutSession $checkoutSession;

    /**
     * @var CartRepositoryInterface
     */
    protected CartRepositoryInterface $cartRepository;

    /**
     * @var QuoteFactory
     */
    protected QuoteFactory $quoteFactory;

    /**
     * Constructor
     * @param Context $context
     * @param OrderRepositoryInterface $orderRepository
     * @param CheckoutSession $checkoutSession
     * @param CartRepositoryInterface $cartRepository
     * @param QuoteFactory $quoteFactory
     */
    public function __construct(
        Context $context,
        OrderRepositoryInterface $orderRepository,
        CheckoutSession $checkoutSession,
        CartRepositoryInterface $cartRepository,
        QuoteFactory $quoteFactory
    ) {
        $this->orderRepository = $orderRepository;
        $this->checkoutSession = $checkoutSession;
        $this->cartRepository = $cartRepository;
        $this->quoteFactory = $quoteFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $orderId = $this->getRequest()->getParam('orderId');
        $redirectUrl = '/';
        try {
            $order = $this->orderRepository->get($orderId);
            $quoteId = $order->getQuoteId();
            $quote = $this->quoteFactory->create()->load($quoteId);
            $quote->setIsActive(true)->setReservedOrderId(null);
            $this->cartRepository->save($quote);
            $this->checkoutSession->replaceQuote($quote);
            $redirectUrl = 'checkout';
        } catch (NoSuchEntityException $e) {
            $redirectUrl = '/';
        }
        $this->_redirect($redirectUrl);
    }
}