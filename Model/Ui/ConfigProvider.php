<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 *
 * @author Gildas Rossignon <gildas@ginidev.com>
 * @package Pledg_PledgPaymentGateway
 */
namespace Pledg\PledgPaymentGateway\Model\Ui;

use Pledg\PledgPaymentGateway\Gateway\Config\Config1;
use Pledg\PledgPaymentGateway\Gateway\Config\Config2;
use Pledg\PledgPaymentGateway\Gateway\Config\Config3;
use Pledg\PledgPaymentGateway\Gateway\Config\Config4;
use Pledg\PledgPaymentGateway\Gateway\Config\Config5;
use Pledg\PledgPaymentGateway\Gateway\Config\Config6;
use Pledg\PledgPaymentGateway\Gateway\Config\Config7;
use Pledg\PledgPaymentGateway\Gateway\Config\Config8;
use Pledg\PledgPaymentGateway\Gateway\Config\Config9;
use Pledg\PledgPaymentGateway\Gateway\Config\Config10;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Customer\Model\Session;
use Magento\Backend\Model\Session\Quote;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\View\Asset\Repository;
use Pledg\PledgPaymentGateway\Gateway\Config\Config;

/**
 * Class ConfigProvider
 */
final class ConfigProvider implements ConfigProviderInterface
{
    protected $_gatewayConfig;
    protected $_scopeConfigInterface;
    protected $customerSession;
    protected $_urlBuilder;
    protected $request;
    protected $_assetRepo;

    public function __construct(
    Config $gatewayConfig,
    Session $customerSession,
    Quote $sessionQuote,
    Context $context,
    Repository $assetRepo
    )
    {
        $this->_gatewayConfig = $gatewayConfig;
        $this->_scopeConfigInterface = $context->getScopeConfig();
        $this->customerSession = $customerSession;
        $this->sessionQuote = $sessionQuote;
        $this->_urlBuilder = $context->getUrlBuilder();
        $this->_assetRepo = $assetRepo;
    }

    public function getConfig()
    {

        // Logo defaut

        /** @var $om \Magento\Framework\ObjectManagerInterface */
        $om = \Magento\Framework\App\ObjectManager::getInstance();
        /** @var $request \Magento\Framework\App\RequestInterface */
        $request = $om->get('Magento\Framework\App\RequestInterface');
        $params = array();
        $params = array_merge(['_secure' => $request->isSecure()], $params);

        $logoDef = $this->_assetRepo->getUrlWithParams('Pledg_PledgPaymentGateway::images/pledg_logo.png', $params);


        $logoFile = $this->_gatewayConfig->getLogo();
        if(strlen($logoFile) > 0){
            $logo = '../pub/media/sales/store/logo/' . $logoFile;
        }
        else{
            $logo = $logoDef;
        }

        $config = [
            'payment' => [
                Config::CODE => [
                    'title' => $this->_gatewayConfig->getTitle(),
                    'description' => $this->_gatewayConfig->getDescription(),
                    'logo' => $logo,
                    'staging' => $this->_scopeConfigInterface->getValue('payment/' . Config::CODE . '/staging'),
                    //'allowed_countries' => $this->_gatewayConfig->getSpecificCountry(),
                ],
                Config1::CODE => [
                    'title' => $this->_scopeConfigInterface->getValue('payment/' . Config1::CODE . '/title'),
                    'description' => $this->_scopeConfigInterface->getValue('payment/' . Config1::CODE . '/description'),
                    'logo' => strlen($this->_scopeConfigInterface->getValue('payment/' . Config1::CODE . '/gateway_logo')) > 0 ? '../pub/media/sales/store/logo/' . $this->_scopeConfigInterface->getValue('payment/' . Config1::CODE . '/gateway_logo') : $logoDef,
                    'merchant_uid' => $this->_scopeConfigInterface->getValue('payment/' . Config1::CODE . '/api_key'),
                    //'allowed_countries' => $this->_gatewayConfig->getSpecificCountry(),
                ],
                Config2::CODE => [
                    'title' => $this->_scopeConfigInterface->getValue('payment/' . Config2::CODE . '/title'),
                    'description' => $this->_scopeConfigInterface->getValue('payment/' . Config2::CODE . '/description'),
                    'logo' => strlen($this->_scopeConfigInterface->getValue('payment/' . Config2::CODE . '/gateway_logo')) > 0 ? '../pub/media/sales/store/logo/' . $this->_scopeConfigInterface->getValue('payment/' . Config2::CODE . '/gateway_logo') : $logoDef,
                    'merchant_uid' => $this->_scopeConfigInterface->getValue('payment/' . Config2::CODE . '/api_key'),
                    //'allowed_countries' => $this->_gatewayConfig->getSpecificCountry(),
                ],
                Config3::CODE => [
                    'title' => $this->_scopeConfigInterface->getValue('payment/' . Config3::CODE . '/title'),
                    'description' => $this->_scopeConfigInterface->getValue('payment/' . Config3::CODE . '/description'),
                    'logo' => strlen($this->_scopeConfigInterface->getValue('payment/' . Config3::CODE . '/gateway_logo')) > 0 ? '../pub/media/sales/store/logo/' . $this->_scopeConfigInterface->getValue('payment/' . Config3::CODE . '/gateway_logo') : $logoDef,
                    'merchant_uid' => $this->_scopeConfigInterface->getValue('payment/' . Config3::CODE . '/api_key'),
                    //'allowed_countries' => $this->_gatewayConfig->getSpecificCountry(),
                ],
                Config4::CODE => [
                    'title' => $this->_scopeConfigInterface->getValue('payment/' . Config4::CODE . '/title'),
                    'description' => $this->_scopeConfigInterface->getValue('payment/' . Config4::CODE . '/description'),
                    'logo' => strlen($this->_scopeConfigInterface->getValue('payment/' . Config4::CODE . '/gateway_logo')) > 0 ? '../pub/media/sales/store/logo/' . $this->_scopeConfigInterface->getValue('payment/' . Config4::CODE . '/gateway_logo') : $logoDef,
                    'merchant_uid' => $this->_scopeConfigInterface->getValue('payment/' . Config4::CODE . '/api_key'),
                    //'allowed_countries' => $this->_gatewayConfig->getSpecificCountry(),
                ],
                Config5::CODE => [
                    'title' => $this->_scopeConfigInterface->getValue('payment/' . Config5::CODE . '/title'),
                    'description' => $this->_scopeConfigInterface->getValue('payment/' . Config5::CODE . '/description'),
                    'logo' => strlen($this->_scopeConfigInterface->getValue('payment/' . Config5::CODE . '/gateway_logo')) > 0 ? '../pub/media/sales/store/logo/' . $this->_scopeConfigInterface->getValue('payment/' . Config5::CODE . '/gateway_logo') : $logoDef,
                    'merchant_uid' => $this->_scopeConfigInterface->getValue('payment/' . Config5::CODE . '/api_key'),
                    //'allowed_countries' => $this->_gatewayConfig->getSpecificCountry(),
                ],
                Config6::CODE => [
                    'title' => $this->_scopeConfigInterface->getValue('payment/' . Config6::CODE . '/title'),
                    'description' => $this->_scopeConfigInterface->getValue('payment/' . Config6::CODE . '/description'),
                    'logo' => strlen($this->_scopeConfigInterface->getValue('payment/' . Config6::CODE . '/gateway_logo')) > 0 ? '../pub/media/sales/store/logo/' . $this->_scopeConfigInterface->getValue('payment/' . Config6::CODE . '/gateway_logo') : $logoDef,
                    'merchant_uid' => $this->_scopeConfigInterface->getValue('payment/' . Config6::CODE . '/api_key'),
                    //'allowed_countries' => $this->_gatewayConfig->getSpecificCountry(),
                ],
                Config7::CODE => [
                    'title' => $this->_scopeConfigInterface->getValue('payment/' . Config7::CODE . '/title'),
                    'description' => $this->_scopeConfigInterface->getValue('payment/' . Config7::CODE . '/description'),
                    'logo' => strlen($this->_scopeConfigInterface->getValue('payment/' . Config7::CODE . '/gateway_logo')) > 0 ? '../pub/media/sales/store/logo/' . $this->_scopeConfigInterface->getValue('payment/' . Config7::CODE . '/gateway_logo') : $logoDef,
                    'merchant_uid' => $this->_scopeConfigInterface->getValue('payment/' . Config7::CODE . '/api_key'),
                    //'allowed_countries' => $this->_gatewayConfig->getSpecificCountry(),
                ],
                Config8::CODE => [
                    'title' => $this->_scopeConfigInterface->getValue('payment/' . Config8::CODE . '/title'),
                    'description' => $this->_scopeConfigInterface->getValue('payment/' . Config8::CODE . '/description'),
                    'logo' => strlen($this->_scopeConfigInterface->getValue('payment/' . Config8::CODE . '/gateway_logo')) > 0 ? '../pub/media/sales/store/logo/' . $this->_scopeConfigInterface->getValue('payment/' . Config8::CODE . '/gateway_logo') : $logoDef,
                    'merchant_uid' => $this->_scopeConfigInterface->getValue('payment/' . Config8::CODE . '/api_key'),
                    //'allowed_countries' => $this->_gatewayConfig->getSpecificCountry(),
                ],
                Config9::CODE => [
                    'title' => $this->_scopeConfigInterface->getValue('payment/' . Config9::CODE . '/title'),
                    'description' => $this->_scopeConfigInterface->getValue('payment/' . Config9::CODE . '/description'),
                    'logo' => strlen($this->_scopeConfigInterface->getValue('payment/' . Config9::CODE . '/gateway_logo')) > 0 ? '../pub/media/sales/store/logo/' . $this->_scopeConfigInterface->getValue('payment/' . Config9::CODE . '/gateway_logo') : $logoDef,
                    'merchant_uid' => $this->_scopeConfigInterface->getValue('payment/' . Config9::CODE . '/api_key'),
                    //'allowed_countries' => $this->_gatewayConfig->getSpecificCountry(),
                ],
                Config10::CODE => [
                    'title' => $this->_scopeConfigInterface->getValue('payment/' . Config10::CODE . '/title'),
                    'description' => $this->_scopeConfigInterface->getValue('payment/' . Config10::CODE . '/description'),
                    'logo' => strlen($this->_scopeConfigInterface->getValue('payment/' . Config10::CODE . '/gateway_logo')) > 0 ? '../pub/media/sales/store/logo/' . $this->_scopeConfigInterface->getValue('payment/' . Config3::CODE . '/gateway_logo') : $logoDef,
                    'merchant_uid' => $this->_scopeConfigInterface->getValue('payment/' . Config10::CODE . '/api_key'),
                    //'allowed_countries' => $this->_gatewayConfig->getSpecificCountry(),
                ]
            ]
        ];

        return $config;
    }
}
