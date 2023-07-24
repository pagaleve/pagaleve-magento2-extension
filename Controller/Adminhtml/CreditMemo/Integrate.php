<?php

namespace Pagaleve\Payment\Controller\Adminhtml\CreditMemo;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Model\OrderRepository;
use mysql_xdevapi\Exception;
use Pagaleve\Payment\Helper\Config as HelperConfig;
use Pagaleve\Payment\Model\Pagaleve;
use Pagaleve\Payment\Model\Request\Payment\RefundRequest;
use Psr\Log\LoggerInterface;

class Integrate extends Action
{
    /** @var LoggerInterface $logger */
    private LoggerInterface $logger;

    /** @var HelperConfig $helperConfig */
    protected HelperConfig $helperConfig;

    /** @var RefundRequest $refundRequest */
    private RefundRequest $refundRequest;

    /** @var CreditmemoRepositoryInterface $creditMemoRepository */
    private CreditmemoRepositoryInterface $creditMemoRepository;

    /** @var OrderRepository $orderRepository */
    private OrderRepository $orderRepository;

    /**
     * @param Context $context
     * @param LoggerInterface $logger
     * @param HelperConfig $helperConfig
     * @param RefundRequest $refundRequest
     * @param CreditmemoRepositoryInterface $creditMemoRepository
     * @param OrderRepository $orderRepository
     */
    public function __construct(
        Context $context,
        LoggerInterface $logger,
        HelperConfig $helperConfig,
        RefundRequest $refundRequest,
        CreditmemoRepositoryInterface $creditMemoRepository,
        OrderRepository $orderRepository
    ) {
        parent::__construct($context);
        $this->logger = $logger;
        $this->helperConfig = $helperConfig;
        $this->refundRequest = $refundRequest;
        $this->creditMemoRepository = $creditMemoRepository;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @throws LocalizedException
     */
    public function execute()
    {

        $creditMemoId = $this->getRequest()->getParam('creditmemo_id');

        try {
            $creditMemo = $this->creditMemoRepository->get($creditMemoId);

            $orderId = $creditMemo->getOrderId();
            $order = $this->orderRepository->get($orderId);
        } catch (InputException | NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__($e->getMessage()));
            throw new LocalizedException(__($e->getMessage()));
        }

        if ($order->getPayment()->getMethod() != Pagaleve::PAYMENT_METHOD_PAGALEVE_CODE) {
            /** @var Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('sales/order_creditmemo/view', ['creditmemo_id' => $creditMemoId]);
        }

        try {
            $refundData = $this->refundRequest->create(
                $order->getData('pagaleve_payment_id'),
                $creditMemo->getGrandTotal(),
                'REQUESTED_BY_CUSTOMER',
                $creditMemo->getCustomerNote()
            );

            if (isset($refundData['id']) && $refundData['id']) {
                $creditMemo->setData('pagaleve_refund_id', $refundData['id']);
            }

            $creditMemo->save();

        } catch (AlreadyExistsException|LocalizedException|\Laminas\Http\Client\Exception\RuntimeException $e) {
            throw new LocalizedException(__($e->getMessage()));
        }

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath('sales/order_creditmemo/view', ['creditmemo_id' => $creditMemoId]);
    }
}
