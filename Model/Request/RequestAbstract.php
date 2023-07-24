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
use Magento\Framework\HTTP\LaminasClient;
use Magento\Framework\HTTP\LaminasClientFactory;
use Magento\Framework\Math\Random;
use Pagaleve\Payment\Helper\Config as HelperConfig;
use Pagaleve\Payment\Helper\Data as HelperData;
use Magento\Framework\Serialize\Serializer\Json;
use Pagaleve\Payment\Logger\Logger;

class RequestAbstract
{
    /** @var json $json */
    protected json $json;

    /** @var LaminasClientFactory $httpClientFactory */
    protected LaminasClientFactory $httpClientFactory;

    /** @var HelperConfig $helperConfig */
    protected HelperConfig $helperConfig;

    /** @var HelperData $helperData */
    protected HelperData $helperData;

    /** @var Random $mathRandom */
    protected Random $mathRandom;

    /** @var Logger $logger */
    protected Logger $logger;

    /**
     * @param LaminasClientFactory $httpClientFactory
     * @param Json $json
     * @param HelperConfig $helperConfig
     * @param Random $mathRandom
     * @param HelperData $helperData
     * @param Logger $logger
     */
    public function __construct(
        LaminasClientFactory $httpClientFactory,
        json $json,
        HelperConfig $helperConfig,
        Random $mathRandom,
        HelperData $helperData,
        Logger $logger
    ) {
        $this->httpClientFactory = $httpClientFactory;
        $this->json = $json;
        $this->helperConfig = $helperConfig;
        $this->mathRandom = $mathRandom;
        $this->helperData = $helperData;
        $this->logger = $logger;
    }

    /**
     * @return string
     * @throws \Laminas\Http\Client\Exception\RuntimeException|LocalizedException
     */
    protected function getToken(): string
    {
        $client = $this->httpClientFactory->create();
        $client->setUri($this->helperConfig->getTokenUrl());
        $client->setOptions(['strict'=> false, 'timeout' => 4]);

        $client->setHeaders(['content-type' => 'application/x-www-form-urlencoded']);
        $postParams = [
            'username' => $this->helperConfig->getTokenUserName(),
            'password' => $this->helperConfig->getTokenPassword()
        ];
        $client->setParameterPost($postParams);
        $client->setMethod(\Laminas\Http\Request::METHOD_POST);

        $response = $client->send();

        if ($response->getStatusCode() == 200) {
            $requestBody = $response->getBody();
            $result = $this->json->unserialize($requestBody);
            return $result['token'] ?? '';
        } else {
            $this->logger->info(
                'Status Code: ' . $response->getStatusCode()
            );
            $this->logger->info(
                'Body: ' . $response->getBody()
            );
        }
        return '';
    }

    /**
     * @param $uri
     * @return LaminasClient
     * @throws \Laminas\Http\Client\Exception\RuntimeException|LocalizedException
     */
    protected function getClient($uri): LaminasClient
    {
        $client = $this->httpClientFactory->create();
        $client->setUri($uri);
        $options = [
            'strict'=> false, 
            'timeout' => 10,
            'adapter' => 'Laminas\Http\Client\Adapter\Curl',
        ];
        $client->setOptions($options);
        $client->setHeaders(
            [
                'content-type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->getToken(),
                'Idempotency-Key' => $this->generateUniqueToken()
            ]
        );

        return $client;
    }

    /**
     * @param $uri
     * @param $method
     * @param $data
     * @return mixed
     * @throws \Laminas\Http\Client\Exception\RuntimeException|LocalizedException
     */
    public function makeRequest($uri, $method, $data = null) {
        $client = $this->getClient($uri);
        
        if($data) {
            $client->setRawBody($data);
        }
        $client->setMethod($method);

        $response = $client->send();

        if ($response->getStatusCode() == 200 || $response->getStatusCode() == 201) {
            $requestBody = $response->getBody();
            $result = $this->json->unserialize($requestBody);
            return $result;
        } else {
            $this->logger->info(
                'URI: ' . $uri
            );
            $this->logger->info(
                'Status Code: ' . $response->getStatusCode()
            );
            $this->logger->info(
                'Body: ' . $response->getBody()
            );
        }
        return [];
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
