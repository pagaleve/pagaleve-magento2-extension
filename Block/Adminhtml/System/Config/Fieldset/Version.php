<?php
/*
 * @Author: Warley Elias
 * @Email: warley.elias@pentagrama.com.br
 * @Date: 2023-08-10 14:08:10
 * @Last Modified by: Warley Elias
 * @Last Modified time: 2023-08-10 14:22:37
 */

namespace Pagaleve\Payment\Block\Adminhtml\System\Config\Fieldset;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Pagaleve\Payment\Helper\Data as PaymentHelper;

class Version extends Field
{
    /**
     * @var PaymentHelper
     */
    protected $paymentHelper;

    /**
     * Version constructor.
     *
     * @param Context       $context
     * @param PaymentHelper $paymentHelper
     * @param array         $data
     */
    public function __construct(
        Context $context,
        PaymentHelper $paymentHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->paymentHelper = $paymentHelper;
    }

    /**
     * Render the fieldset element.
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $moduleVersion = $this->paymentHelper->getModuleVersion(); // Implement the logic to get the module version from your PaymentHelper class
        $html = '<div>';
        $html .= '<p>' . __('Current Module Version: %1', $moduleVersion) . '</p>';
        $html .= '</div>';

        return $html;
    }
}
