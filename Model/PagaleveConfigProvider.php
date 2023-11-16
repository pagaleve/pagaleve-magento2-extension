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

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Helper\Data as PaymentHelper;
use Pagaleve\Payment\Helper\Config as HelperConfig;

class PagaleveConfigProvider implements ConfigProviderInterface
{
    /**
     * @var string[]
     */
    protected $methodCode = Pagaleve::PAYMENT_METHOD_PAGALEVE_CODE;

    /**
     * @var Pagaleve
     */
    protected $method;

    /**
     * @var Escaper
     */
    protected $escaper;

    /** @var HelperConfig $helperConfig */
    protected $helperConfig;

    /**
     * @param PaymentHelper $paymentHelper
     * @param Escaper $escaper
     * @param HelperConfig $helperConfig
     * @throws LocalizedException
     */
    public function __construct(
        PaymentHelper $paymentHelper,
        Escaper $escaper,
        HelperConfig $helperConfig
    ) {
        $this->escaper = $escaper;
        $this->method = $paymentHelper->getMethodInstance($this->methodCode);
        $this->helperConfig = $helperConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        return $this->method->isAvailable() ? [
            'payment' => [
                $this->methodCode => [
                    'mailingAddress' => $this->getMailingAddress(),
                    'payableTo' => $this->getPayableTo(),
                    'instructions' => $this->getInstructions()
                ],
            ],
        ] : [];
    }

    /**
     * Get mailing address from config
     *
     * @return string
     */
    protected function getMailingAddress()
    {
        return nl2br($this->escaper->escapeHtml($this->method->getMailingAddress()));
    }

    /**
     * Get payable to from config
     *
     * @return string
     */
    protected function getPayableTo()
    {
        return $this->method->getPayableTo();
    }

    /**
     * Get instructions text from config
     *
     * @return string
     */
    protected function getInstructions(): string
    {
        return nl2br($this->escaper->escapeHtml($this->helperConfig->getPaymentInstructions()));
    }
}
