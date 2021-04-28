<?php

namespace Pledg\PledgPaymentGateway\Gateway\Request;

use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Sales\Model\Order;
use Magento\Payment\Gateway\Data\Order\OrderAdapter;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Checkout\Model\Session;
use Psr\Log\LoggerInterface;

class InitializationRequest implements BuilderInterface
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
     * Checks the quote for validity
     * @param OrderAdapter $order
     * @return bool;
     */
    private function validateQuote(OrderAdapterInterface $order) {
        $total = $order->getGrandTotalAmount();
        /*if($total < 20) {
            $this->_session->setPledgErrorMessage(__("Pledg doesn't support purchases less than $20."));
            return false;
        }

        if ($this->_gatewayConfig->isAus()) {
            if ($total > 2100) {
                $this->_session->setPledgErrorMessage(__("Pledg doesn't support purchases over $2100."));
                return false;
            }
        } else {
            if ($total > 1500) {
                $this->_session->setPledgErrorMessage(__("Pledg doesn't support purchases over $1500."));
                return false;
            }
        }*/

        return true;

        /*$this->_logger->debug('[InitializationRequest][validateQuote]$this->_gatewayConfig->getSpecificCountry():'.($this->_gatewayConfig->getSpecificCountry()));
        $allowedCountriesArray = explode(',', $this->_gatewayConfig->getSpecificCountry());

        $this->_logger->debug('[InitializationRequest][validateQuote]$order->getBillingAddress()->getCountryId():'.($order->getBillingAddress()->getCountryId()));
        if (!in_array($order->getBillingAddress()->getCountryId(), $allowedCountriesArray)) {
            $this->_logger->debug('[InitializationRequest][validateQuote]Country is not in array');
            $this->_session->setPledgErrorMessage(__('Orders from this country are not supported by Pledg. Please select a different payment option.'));
            return false;
        }

        $this->_logger->debug('[InitializationRequest][validateQuote]$order->getShippingAddress()->getCountryId():'.($order->getShippingAddress()->getCountryId()));
        if (!in_array($order->getShippingAddress()->getCountryId(), $allowedCountriesArray)) {
            $this->_session->setPledgErrorMessage(__('Orders shipped to this country are not supported by Pledg. Please select a different payment option.'));
            return false;
        }*/

        //return true;
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

        $payment = $buildSubject['payment'];
        $stateObject = $buildSubject['stateObject'];

        $order = $payment->getOrder();

        if($this->validateQuote($order)) {
            $stateObject->setState(Order::STATE_PENDING_PAYMENT);
            $stateObject->setStatus(Order::STATE_PENDING_PAYMENT);
            $stateObject->setIsNotified(false);
        } else {
            $stateObject->setState(Order::STATE_CANCELED);
            $stateObject->setStatus(Order::STATE_CANCELED);
            $stateObject->setIsNotified(false);
        }

        return [ 'IGNORED' => [ 'IGNORED' ] ];
    }
}
