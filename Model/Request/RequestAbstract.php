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

namespace Pagaleve\Payment\Model\Request;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\ZendClient;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Framework\Math\Random;
use Pagaleve\Payment\Helper\Config as HelperConfig;
use Pagaleve\Payment\Helper\Data as HelperData;
use Zend_Http_Client;
use Magento\Framework\Serialize\Serializer\Json;

class RequestAbstract
{
    /** @var json $json */
    protected $json;

    /** @var ZendClientFactory $httpClientFactory */
    protected $httpClientFactory;

    /** @var HelperConfig $helperConfig */
    protected $helperConfig;

    /** @var HelperData $helperData */
    protected $helperData;

    /** @var Random $mathRandom */
    protected $mathRandom;

    /**
     * @param ZendClientFactory $httpClientFactory
     * @param Json $json
     * @param HelperConfig $helperConfig
     * @param Random $mathRandom
     * @param HelperData $helperData
     */
    public function __construct(
        ZendClientFactory $httpClientFactory,
        json $json,
        HelperConfig $helperConfig,
        Random $mathRandom,
        HelperData $helperData
    ) {
        $this->httpClientFactory = $httpClientFactory;
        $this->json = $json;
        $this->helperConfig = $helperConfig;
        $this->mathRandom = $mathRandom;
        $this->helperData = $helperData;
    }

    /**
     * @return string
     * @throws \Zend_Http_Client_Exception
     */
    protected function getToken(): string
    {
        $client = $this->httpClientFactory->create();
        $client->setUri($this->helperConfig->getTokenUrl());
        $client->setConfig(['strict'=> false, 'timeout' => 4]);

        $client->setHeaders(['content-type' => 'application/x-www-form-urlencoded']);
        $client->setParameterPost('username', $this->helperConfig->getTokenUserName());
        $client->setParameterPost('password', $this->helperConfig->getTokenPassword());
        $client->setMethod(Zend_Http_Client::POST);

        $request = $client->request();
        
        if ($request->getStatus() == 200 || $request->getStatus() == 201) {
            $requestBody = $request->getbody();
            $result = $this->json->unserialize($requestBody);
            return $result['token'] ?? '';
        }
        return '';
    }

    /**
     * @param $uri
     * @return ZendClient
     * @throws \Zend_Http_Client_Exception|LocalizedException
     */
    public function getClient($uri): ZendClient
    {
        $client = $this->httpClientFactory->create();
        $client->seturi($uri);
        $client->setconfig(['strict'=> false, 'timeout' => 10]);

        $client->setheaders(
            [
                'Authorization' => 'Bearer ' . $this->getToken(),
                'Idempotency-Key' => $this->generateUniqueToken()
            ]
        );

        return $client;
    }

    /**
     * @return string
     * @throws LocalizedException
     */
    public function generateUniqueToken(): string
    {
        return $this->mathRandom->getUniqueHash();
    }

    /**
     * @param $phone
     * @return mixed|string
     */
    protected function formatPhone($phone)
    {
        $formattedPhone = preg_replace('/[^0-9]/', '', $phone);
        return $formattedPhone;
        /*$matches = [];
        preg_match('/^([0-9]{2})([0-9]{4,5})([0-9]{4})$/', $formattedPhone, $matches);
        if ($matches) {
            return '('.$matches[1].')'.$matches[2].'-'.$matches[3];
        }
        return $phone;*/
    }
}
