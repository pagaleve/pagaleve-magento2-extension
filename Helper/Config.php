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

namespace Pagaleve\Payment\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;
use Pagaleve\Payment\Model\Config\Source\PaymentAction;

class Config extends AbstractHelper
{
    public const ACTIVE = 'payment/pagaleve/active';
    public const PAYMENT_INSTRUCTIONS = 'payment/pagaleve/instructions';
    public const SANDBOX_MODE = 'payment/pagaleve/sandbox_mode';

    public const TOKEN_URL = 'payment/pagaleve/token_url';
    public const TOKEN_URL_SANDBOX = 'payment/pagaleve/token_url_sandbox';

    public const TOKEN_USERNAME = 'payment/pagaleve/token_username';
    public const TOKEN_USERNAME_SANDBOX = 'payment/pagaleve/token_username_sandbox';

    public const TOKEN_PASSWORD = 'payment/pagaleve/token_password';
    public const TOKEN_PASSWORD_SANDBOX = 'payment/pagaleve/token_password_sandbox';

    public const CHECKOUT_URL = 'payment/pagaleve/checkout_url';
    public const CHECKOUT_URL_SANDBOX = 'payment/pagaleve/checkout_url_sandbox';

    public const PAYMENT_URL = 'payment/pagaleve/payment_url';
    public const PAYMENT_URL_SANDBOX = 'payment/pagaleve/payment_url_sandbox';

    public const PAYMENT_CAPTURE_URL = 'payment/pagaleve/capture_url';
    public const PAYMENT_CAPTURE_URL_SANDBOX = 'payment/pagaleve/capture_url_sandbox';

    public const PAYMENT_REFUND_URL = 'payment/pagaleve/refund_url';
    public const PAYMENT_REFUND_URL_SANDBOX = 'payment/pagaleve/refund_url_sandbox';

    public const PAYMENT_RELEASE_URL = 'payment/pagaleve/release_url';
    public const PAYMENT_RELEASE_URL_SANDBOX = 'payment/pagaleve/release_url_sandbox';

    public const PAYMENT_ACTION = 'payment/pagaleve/payment_action';

    public const PAYMENT_STATUS_NEW = 'payment/pagaleve/order_status_new';
    public const PAYMENT_STATUS_PROCESSING = 'payment/pagaleve/order_status_processing';

    /**
     * @return bool
     */
    public function sandboxMode(): bool
    {
        return (bool) $this->getStoreConfig(self::SANDBOX_MODE);
    }

    /**
     * @return mixed
     */
    public function getTokenUrl()
    {
        return $this->getStoreConfig($this->sandboxMode() ? self::TOKEN_URL_SANDBOX : self::TOKEN_URL);
    }

    /**
     * @return mixed
     */
    public function getTokenUserName()
    {
        return $this->getStoreConfig($this->sandboxMode() ? self::TOKEN_USERNAME_SANDBOX : self::TOKEN_USERNAME);
    }

    /**
     * @return mixed
     */
    public function getTokenPassword()
    {
        return $this->getStoreConfig($this->sandboxMode() ? self::TOKEN_PASSWORD_SANDBOX : self::TOKEN_PASSWORD);
    }

    /**
     * @return mixed
     */
    public function getCheckoutUrl()
    {
        return $this->getStoreConfig($this->sandboxMode() ? self::CHECKOUT_URL_SANDBOX : self::CHECKOUT_URL);
    }

    /**
     * @return mixed
     */
    public function getPaymentUrl()
    {
        return $this->getStoreConfig($this->sandboxMode() ? self::PAYMENT_URL_SANDBOX : self::PAYMENT_URL);
    }

    /**
     * @return mixed
     */
    public function getPaymentAction()
    {
        return $this->getStoreConfig(self::PAYMENT_ACTION) ?? PaymentAction::AUTHORIZE_AND_CAPTURE;
    }

    /**
     * @return mixed
     */
    public function getPaymentStatusNew()
    {
        return $this->getStoreConfig(self::PAYMENT_STATUS_NEW) ?? 'pending';
    }

    /**
     * @return mixed
     */
    public function getPaymentStatusProcessing()
    {
        return $this->getStoreConfig(self::PAYMENT_STATUS_PROCESSING) ?? Order::STATE_PROCESSING;
    }

    /**
     * @return mixed
     */
    public function getPaymentCaptureUrl()
    {
        return $this->getStoreConfig(
            $this->sandboxMode() ? self::PAYMENT_CAPTURE_URL_SANDBOX : self::PAYMENT_CAPTURE_URL
        );
    }

    /**
     * @return mixed
     */
    public function getPaymentRefundUrl()
    {
        return $this->getStoreConfig(
            $this->sandboxMode() ? self::PAYMENT_REFUND_URL_SANDBOX : self::PAYMENT_REFUND_URL
        );
    }

    /**
     * @return mixed
     */
    public function getPaymentReleaseUrl()
    {
        return $this->getStoreConfig(
            $this->sandboxMode() ? self::PAYMENT_RELEASE_URL_SANDBOX : self::PAYMENT_RELEASE_URL
        );
    }

    /**
     * @return mixed
     */
    public function getPaymentInstructions()
    {
        return $this->getStoreConfig(self::PAYMENT_INSTRUCTIONS);
    }

    /**
     * @param $path
     * @param $storeCode
     * @return mixed
     */
    private function getStoreConfig($path, $storeCode = null)
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeCode);
    }
}
