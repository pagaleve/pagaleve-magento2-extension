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

namespace Pagaleve\Payment\Block\Info;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Block\Info;
use Magento\Sales\Model\Order;

class Pagaleve extends Info
{
    /**
     * @var string
     */
    protected $_template = 'Pagaleve_Payment::sales/order/info/pagaleve.phtml';

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
