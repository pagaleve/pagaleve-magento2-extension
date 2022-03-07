<?php

namespace Pagaleve\Payment\Plugin;

use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Block\Adminhtml\Order\Creditmemo\View;

/**
 * Class View
 *
 * @package Pagaleve\Payment\Plugin\CreditmemoViewAddButton
 */
class CreditmemoViewAddButton
{

    /** @var CreditmemoRepositoryInterface $creditMemoRepository */
    private CreditmemoRepositoryInterface $creditMemoRepository;

    /**
     * @param CreditmemoRepositoryInterface $creditMemoRepository
     */
    public function __construct(CreditmemoRepositoryInterface $creditMemoRepository)
    {
        $this->creditMemoRepository = $creditMemoRepository;
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

        if ($creditMemo->getPagaleveRefundId()) {
            return [$layout];
        }

        if (!$creditMemoId) {
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
