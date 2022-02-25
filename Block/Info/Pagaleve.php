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

use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Payment\Block\Info;

class Pagaleve extends Info
{
    /**
     * Core registry
     *
     * @var ?Registry
     */
    protected ?Registry $_coreRegistry = null;

    public function __construct(
        Template\Context $context,
        Registry $registry,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->_coreRegistry = $registry;
    }

    /**
     * @var string
     */
    protected $_template = 'Pagaleve_Payment::sales/order/info/pagaleve.phtml';

    /**
     * Retrieve order model object
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->getInfo()->getOrder();
    }

    /**
     * Retrieve invoice model instance
     *
     * @return \Magento\Sales\Model\Order\Invoice
     */
    public function getInvoice()
    {
        return $this->_coreRegistry->registry('current_invoice');
    }

    /**
     * Retrieve creditmemo model instance
     *
     * @return \Magento\Sales\Model\Order\Creditmemo
     */
    public function getCreditmemo()
    {
        return $this->_coreRegistry->registry('current_creditmemo');
    }
}
