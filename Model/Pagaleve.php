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

namespace Pagaleve\Payment\Model;

use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Quote\Api\Data\CartInterface;

class Pagaleve extends AbstractMethod
{
    /** @var string */
    const PAYMENT_METHOD_PAGALEVE_CODE = 'pagaleve';

    /** @var string $_code */
    protected $_code = self::PAYMENT_METHOD_PAGALEVE_CODE;

    /**
     * @var string
     */
    protected $_infoBlockType = \Pagaleve\Payment\Block\Info\Pagaleve::class;

    /** @var bool $_isOffline */
    protected $_isOffline = false;

    /**
     * @param CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(
        CartInterface $quote = null
    ): bool
    {
        return parent::isAvailable($quote);
    }
}
