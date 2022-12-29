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

use Magento\Framework\Registry;
use Magento\Payment\Helper\Data;
use Magento\Framework\Model\Context;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\Logger;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Pagaleve\Payment\Model\Request\CheckoutRequest;

class Pagaleve extends AbstractMethod
{
    /** @var string */
    const PAYMENT_METHOD_PAGALEVE_CODE = 'pagaleve';
    /** @var string $_code */
    protected $_code = self::PAYMENT_METHOD_PAGALEVE_CODE;
    protected $_supportedCurrencyCodes    = ['BRL'];
    protected $_canOrder                = true;
    protected $_canCapture              = true;
    protected $_canAuthorize            = true;
    protected $_isInitializeNeeded      = true;
    protected $_canRefund               = true;
    protected $_canRefundInvoicePartial = true;
    
    protected $_infoBlockType = \Pagaleve\Payment\Block\Info\Pagaleve::class;

    /** 
     * @var string $_redirectUrl 
     */
    protected $_redirectUrl;

    /** 
     * @var CheckoutRequest $checkoutRequest 
    */
    protected $checkoutRequest;

    /**
     * Pagaleve constructor.
     * @param Context $context
     * @param Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param Data $paymentData
     * @param ScopeConfigInterface $scopeConfig
     * @param Logger $logger
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     * @param DirectoryHelper $directory
     * @param CheckoutRequest $checkoutRequest
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        Data $paymentData,
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = [],
        DirectoryHelper $directory = null,
        CheckoutRequest $checkoutRequest
    ) {
        $this->checkoutRequest = $checkoutRequest;
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data,
            $directory
        );
    }

    public function initialize($paymentAction, $stateObject) {
        parent::initialize($paymentAction, $stateObject);
        
        $payment = $this->getInfoInstance();
        $order = $payment->getOrder();
        $this->checkoutRequest->setOrder($order);
        $checkoutResquestData = $this->checkoutRequest->create();
        if (isset($checkoutResquestData['id']) && $checkoutResquestData['id']) {
            $this->_redirectUrl = $checkoutResquestData['checkout_url'];
            return $this;
        }
        throw new \Magento\Framework\Exception\LocalizedException(__('Houve um erro ao gerar o pedido, por favor, tente novamente.'));
    }
}