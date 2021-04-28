<?php

namespace Pledg\PledgPaymentGateway\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Checkout\Model\Session;
use Psr\Log\LoggerInterface;

class RefundRequest implements BuilderInterface
{
    private $_logger;
    private $_session;

    /**
     * @param LoggerInterface $logger
     * @param Session $session
     */
    public function __construct(
        LoggerInterface $logger,
        Session $session
    ) {
        $this->_logger = $logger;
        $this->_session = $session;
    }

    /**
     * Builds ENV request
     * From: https://github.com/magento/magento2/blob/2.1.3/app/code/Magento/Payment/Model/Method/Adapter.php
     * The $buildSubject contains:
     * 'payment' => $this->getInfoInstance()
     * 'paymentAction' => $paymentAction
     * 'stateObject' => $stateObject
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject) {
        // TODO : configuration should be retrieved from order payment method
//    	$gateway_api_key = $this->_gatewayConfig->getApiKey();
//    	$gateway_merchant_id = $this->_gatewayConfig->getMerchantNumber();
//    	$gateway_refund_gateway_url = $this->_gatewayConfig->getRefundUrl();
        $gateway_merchant_id = '';
        $gateway_api_key = '';
        $gateway_refund_gateway_url = '';
    	return [ 'GATEWAY_MERCHANT_ID'=>$gateway_merchant_id, 'GATEWAY_API_KEY' => $gateway_api_key, 'GATEWAY_REFUND_GATEWAY_URL'=>$gateway_refund_gateway_url ];
    }
}
