<?php

namespace Pledg\PledgPaymentGateway\Controller\Checkout;

use Magento\Framework\App\Action\Action;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Sales\Model\OrderFactory;
use Pledg\PledgPaymentGateway\Helper\Crypto;
use Pledg\PledgPaymentGateway\Helper\Data;
use Pledg\PledgPaymentGateway\Helper\Checkout;
use Psr\Log\LoggerInterface;

/**
 * @package Pledg\PledgPaymentGateway\Controller\Checkout
 */
abstract class AbstractAction extends Action {

    const LOG_FILE = 'pledg.log';
    const PLEDG_DEFAULT_CURRENCY_CODE = 'EUR';
    const PLEDG_DEFAULT_COUNTRY_CODE = 'FR';

    private $_context;

    private $_checkoutSession;

    private $_orderFactory;

    private $_cryptoHelper;

    private $_dataHelper;

    private $_checkoutHelper;

    private $_messageManager;

    private $_logger;

    private $_scopeConfig;

    private $_quoteIdMaskFactory;

    private $_quoteManagement;

    protected $_code;

    public function __construct(
        Session $checkoutSession,
        Context $context,
        OrderFactory $orderFactory,
        Crypto $cryptoHelper,
        Data $dataHelper,
        Checkout $checkoutHelper,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        CartManagementInterface $quoteManagement
        ) {
        parent::__construct($context);
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;
        $this->_cryptoHelper = $cryptoHelper;
        $this->_dataHelper = $dataHelper;
        $this->_checkoutHelper = $checkoutHelper;
        $this->_messageManager = $context->getMessageManager();
        $this->_scopeConfig = $scopeConfig;
        $this->_logger = $logger;
        $this->_quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->_quoteManagement = $quoteManagement;
    }

    protected function getContext() {
        return $this->_context;
    }

    protected function getCheckoutSession() {
        return $this->_checkoutSession;
    }

    protected function getOrderFactory() {
        return $this->_orderFactory;
    }

    protected function getCryptoHelper() {
        return $this->_cryptoHelper;
    }

    protected function getDataHelper() {
        return $this->_dataHelper;
    }

    protected function getCheckoutHelper() {
        return $this->_checkoutHelper;
    }

    protected function getMessageManager() {
        return $this->_messageManager;
    }

    protected function getLogger() {
        return $this->_logger;
    }

    protected function getQuoteIdMaskFactory() {
        return $this->_quoteIdMaskFactory;
    }

    protected function getQuoteManagement() {
        return $this->_quoteManagement;
    }

    protected function getOrder()
    {
        $orderId = $this->_checkoutSession->getLastRealOrderId();

        if (!isset($orderId)) {
            return null;
        }

        return $this->getOrderById($orderId);
    }

    protected function getOrderById($orderId)
    {
        $order = $this->_orderFactory->create()->loadByIncrementId($orderId);

        if (!$order->getId()) {
            return null;
        }

        return $order;
    }

    protected function getObjectManager()
    {
        return \Magento\Framework\App\ObjectManager::getInstance();
    }

    protected function isStaging()
    {
        return $this->_scopeConfig->getValue('pledg_gateway/payment/staging');
    }

    protected function getUrlPayment() {
        if ($this->isStaging()) {
            $url = $this->_scopeConfig->getValue('pledg_gateway/payment/staging_url');
        } else {
            $url = $this->_scopeConfig->getValue('pledg_gateway/payment/gateway_url');
        }

        $url .= '?merchantUid=' . $this->getMerchantUid();

        return $url;
    }

    protected function setCode($code) {
        $this->_code = $code;
    }

    protected function getMerchantUid() {
        return $this->_scopeConfig->getValue('payment/'.$this->_code.'/api_key');
    }

    public function getStoreName()
    {
        return $this->_scopeConfig->getValue(
            'general/store_information/name',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}
