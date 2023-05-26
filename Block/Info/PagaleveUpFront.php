<?php
/*
 * @Author: Warley Elias
 * @Email: warley.elias@pentagrama.com.br
 * @Date: 2023-05-18 11:09:51
 * @Last Modified by: Warley Elias
 * @Last Modified time: 2023-05-26 14:47:11
 */

declare(strict_types=1);

namespace Pagaleve\Payment\Block\Info;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Block\Info;
use Magento\Sales\Model\Order;

class PagaleveUpFront extends Info
{
    /**
     * @var string
     */
    protected $_template = 'Pagaleve_Payment::sales/order/info/pagaleve_upfront.phtml';

    /**
     * Retrieve order model object
     *
     * @return Order
     * @throws LocalizedException
     */
    public function getOrder()
    {
        return $this->getInfo()->getOrder();
    }
}
