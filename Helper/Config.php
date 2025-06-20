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

    public const PAYMENT_RETRY_DEADLINE = 'payment/pagaleve/retry_deadline';

    public const PAYMENT_SECRET_KEY = 'payment/pagaleve/secret_key';

    public const PAYMENT_ACTIVE_TRANSPARENT_CHECKOUT = 'payment/pagaleve/enabled_transparent_checkout';

    public const PAYMENT_STATUS = 'payment/pagaleve/order_status';
    public const PAYMENT_STATUS_NEW = 'payment/pagaleve/order_status_new';
    public const PAYMENT_CONFIRMED_STATUS = 'payment/pagaleve/payment_confirmed_status';

    public const UPFRONT_PAYMENT_STATUS = 'payment/pagaleve_upfront/order_status';
    public const UPFRONT_PAYMENT_STATUS_NEW = 'payment/pagaleve_upfront/order_status_new';
    public const UPFRONT_PAYMENT_CONFIRMED_STATUS = 'payment/pagaleve_upfront/payment_confirmed_status';

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
    public function getPaymentStatus()
    {
        return [$this->getStoreConfig(self::PAYMENT_STATUS), $this->getStoreConfig(self::UPFRONT_PAYMENT_STATUS), 'pending'];
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
    public function getPaymentConfirmedStatus()
    {
        return $this->getStoreConfig(self::PAYMENT_CONFIRMED_STATUS) ?? Order::STATE_PROCESSING;
    }

    /**
     * @return mixed
     */
    public function getUpfrontPaymentConfirmedStatus()
    {
        return $this->getStoreConfig(self::UPFRONT_PAYMENT_CONFIRMED_STATUS) ?? Order::STATE_PROCESSING;
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
     * @return mixed
     */
    public function getRetryDeadline()
    {
        return $this->getStoreConfig(self::PAYMENT_RETRY_DEADLINE) ?? 15;
    }

    /**
     * @return mixed
     */
    public function getSecretKey()
    {
        return $this->getStoreConfig(self::PAYMENT_SECRET_KEY);
    }

    /**
     * @return bool
     */
    public function enabledLog()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function isTransparentCheckoutEnabled()
    {
        return (bool) $this->getStoreConfig(self::PAYMENT_ACTIVE_TRANSPARENT_CHECKOUT);
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
