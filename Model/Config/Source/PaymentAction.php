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

namespace Pagaleve\Payment\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class PaymentAction implements OptionSourceInterface
{
    const AUTHORIZE = 'AUTH';
    const AUTHORIZE_AND_CAPTURE = 'CAPTURE';

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            ['value' => static::AUTHORIZE, 'label' => __('Authorize')],
            ['value' => static::AUTHORIZE_AND_CAPTURE, 'label' => __('Authorize and Capture')],
        ];
    }
}
