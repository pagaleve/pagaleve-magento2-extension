<?php
/**
 * @author      FCamara - Formação e Consultoria <contato@fcamara.com.br>
 * @author      Guilherme Miguelete <guilherme.miguelete@fcamara.com.br>
 * @license     Pagaleve Tecnologia Financeira | Copyright
 * @copyright   2022 Pagaleve Tecnologia Financeira (http://www.pagaleve.com.br)
 *
 * @link        http://www.pagaleve.com.br
 */
namespace Pagaleve\Payment\Plugin;

use Magento\Backend\Model\View\Result\Redirect;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Block\Adminhtml\Order\Creditmemo\View;
use Magento\Sales\Model\OrderRepository;
use Pagaleve\Payment\Model\Pagaleve;

/**
 * Class View
 *
 * @package Pagaleve\Payment\Plugin\CreditmemoViewAddButton
 */
class CreditmemoViewAddButton
{

    /** @var CreditmemoRepositoryInterface $creditMemoRepository */
    private CreditmemoRepositoryInterface $creditMemoRepository;

    /** @var OrderRepository $orderRepository */
    private OrderRepository $orderRepository;

    /**
     * @param CreditmemoRepositoryInterface $creditMemoRepository
     * @param OrderRepository $orderRepository
     */
    public function __construct(CreditmemoRepositoryInterface $creditMemoRepository, OrderRepository $orderRepository)
    {
        $this->creditMemoRepository = $creditMemoRepository;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param View $subject
     * @param $layout
     * @return array
     */
    public function beforeSetLayout(
        View $subject,
        $layout
    ) {

        $creditMemoId = $subject->getRequest()->getParam('creditmemo_id');
        $creditMemo = $this->creditMemoRepository->get($creditMemoId);

        if (!$creditMemoId || $creditMemo->getPagaleveRefundId()) {
            return [$layout];
        }

        $order = $this->orderRepository->get($creditMemo->getOrderId());
        if ($order->getPayment()->getMethod() != Pagaleve::PAYMENT_METHOD_PAGALEVE_CODE) {
            return [$layout];
        }

        $creditMemoUrl = $subject->getUrl('pagaleve/creditmemo/integrate', ['creditmemo_id' => $creditMemoId]);
        $subject->addButton(
            'pagaleve_integration_credit_memo',
            [
                'label' => __('Integrate to Pagaleve'),
                'onclick' => 'setLocation(\'' . $creditMemoUrl . '\')',
                'class' => 'action-default action-warranty-order',
            ]
        );

        return [$layout];
    }
}
