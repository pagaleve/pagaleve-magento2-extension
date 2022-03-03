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

namespace Pagaleve\Payment\Block\Widget\Catalog\Product\View;

use Magento\Catalog\Helper\Data as CatalogHelper;
use Magento\Catalog\Model\Product;
use Magento\Framework\View\Element\Template;
use Magento\Widget\Block\BlockInterface;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;

class Pagaleve extends Template implements BlockInterface
{
    /** @var string $_template */
    protected $_template = 'Pagaleve_Payment::catalog/product/view/widget/pagaleve.phtml';

    /** @var CatalogHelper $catalogHelper */
    private CatalogHelper $catalogHelper;

    /** @var PricingHelper $pricingHelper */
    private PricingHelper $pricingHelper;

    /**
     * @param Template\Context $context
     * @param CatalogHelper $catalogHelper
     * @param PricingHelper $pricingHelper
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        CatalogHelper $catalogHelper,
        PricingHelper $pricingHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->catalogHelper = $catalogHelper;
        $this->pricingHelper = $pricingHelper;
    }

    /**
     * @return Product|null
     */
    public function getProduct(): ?Product
    {
        return $this->catalogHelper->getProduct();
    }

    /**
     * @return string
     */
    public function getInstalmentPrice(): string
    {
        $instalmentPrice = $this->getProduct()->getFinalPrice() / 4;
        return $this->pricingHelper->currency($instalmentPrice);
    }
}
