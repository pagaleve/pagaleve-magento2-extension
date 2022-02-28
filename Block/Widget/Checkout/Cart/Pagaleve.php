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

namespace Pagaleve\Payment\Block\Widget\Checkout\Cart;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Pagaleve\Payment\Helper\Data as DataHelper;
use Magento\Framework\View\Element\Template;
use Magento\Widget\Block\BlockInterface;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;

class Pagaleve extends Template implements BlockInterface
{
    /** @var string $_template */
    protected $_template = 'Pagaleve_Payment::checkout/cart/widget/pagaleve.phtml';

    /** @var DataHelper $dataHelper */
    private DataHelper $dataHelper;

    /** @var PricingHelper $pricingHelper */
    private PricingHelper $pricingHelper;

    /**
     * @param Template\Context $context
     * @param DataHelper $dataHelper
     * @param PricingHelper $pricingHelper
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        DataHelper $dataHelper,
        PricingHelper $pricingHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->dataHelper = $dataHelper;
        $this->pricingHelper = $pricingHelper;
    }

    /**
     * @return Quote
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getQuote(): Quote
    {
        return $this->dataHelper->getQuote();
    }

    /**
     * @return float|string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getInstalmentPrice()
    {
        $instalmentPrice = $this->getQuote()->getGrandTotal() / 4;
        return $this->pricingHelper->currency($instalmentPrice);
    }
}
