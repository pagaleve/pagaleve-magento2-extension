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

namespace Pagaleve\Payment\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Pagaleve\Payment\Helper\Config as HelperConfig;
use Pagaleve\Payment\Model\Pagaleve;
use Pagaleve\Payment\Model\PagaleveUpFront;
use Pagaleve\Payment\Model\Request\Payment\ReleaseRequest;
use Magento\Sales\Api\OrderRepositoryInterface;

class OrderCancel implements ObserverInterface
{
    /** @var HelperConfig $helperConfig */
    protected HelperConfig $helperConfig;

    /** @var ReleaseRequest $releaseRequest */
    private ReleaseRequest $releaseRequest;

    /** @var OrderRepositoryInterface $orderRepository */
    private OrderRepositoryInterface $orderRepository;

    /**
     * @param HelperConfig $helperConfig
     * @param ReleaseRequest $releaseRequest
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        HelperConfig $helperConfig,
        ReleaseRequest $releaseRequest,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->helperConfig = $helperConfig;
        $this->releaseRequest = $releaseRequest;
        $this->orderRepository = $orderRepository;
    }

    /**
     * Update inviter balance if possible
     *
     * @param Observer $observer
     * @return OrderCancel
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        /* @var $order Order */
        $order = $observer->getEvent()->getData('order');

        if ($order->canCancel()) {
            return $this;
        }

        if (
            $order->getPayment()->getMethod() != Pagaleve::PAYMENT_METHOD_PAGALEVE_CODE &&
            $order->getPayment()->getMethod() != PagaleveUpFront::PAYMENT_METHOD_PAGALEVE_CODE
        ) {
            return $this;
        }

        try {
            if($order->getData('pagaleve_payment_id')) {
                $releaseData = $this->releaseRequest->create(
                    $order->getData('pagaleve_payment_id'),
                    $order->getGrandTotal()
                );
            }

            if (isset($releaseData['id']) && $releaseData['id']) {
                $order->setData('pagaleve_release_id', $releaseData['id']);
                $this->orderRepository->save($order);
            }

        } catch (AlreadyExistsException|LocalizedException|\Laminas\Http\Client\Exception\RuntimeException $e) {
            throw new LocalizedException(__($e->getMessage()));
            //return $this;
        }

        return $this;
    }
}
