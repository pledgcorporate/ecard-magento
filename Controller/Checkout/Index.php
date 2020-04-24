<?php

/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 *
 * @author Gildas Rossignon <gildas@ginidev.com>
 * @package Pledg_PledgPaymentGateway
 */

namespace Pledg\PledgPaymentGateway\Controller\Checkout;

use Magento\Sales\Model\Order;

/**
 * @package Pledg\PledgPaymentGateway\Controller\Checkout
 */
class Index extends AbstractAction {

    /**
     * Generate Payment URL
     *
     * @param $order
     * @return string
     */
    private function getPayload($order) {
        if($order == null) {
            $this->getLogger()->debug('Unable to get order from last lodged order id. Possibly related to a failed database call');
            $this->_redirect('checkout/onepage/error', array('_secure'=> false));
        }

        $url = $this->getURLPayment();
        $url .= '&amountCents=' . (int)($order->getTotalDue() * 100);
        $url .= '&title=' . $order->getRealOrderId();
        $url .= '&email=' . $order->getData('customer_email');
        $url .= '&reference=' . $order->getRealOrderId();
        $url .= '&currency=' . $order->getOrderCurrencyCode();
        $url .= '&redirectUrl=' . $this->getDataHelper()->getCompleteUrl();
        $url .= '&cancelUrl=' . $this->getDataHelper()->getCancelledUrl($order->getRealOrderId());
        $url .= '&firstname=' . $order->getCustomerFirstname();
        $url .= '&lastname=' . $order->getCustomerLastname();
        $url .= '&phoneNumber=' . $order->getBillingAddress()->getData('telephone');

        // Address
        $street = preg_replace('/\s+/', ' ', trim($order->getBillingAddress()->getData('street')));
        $street = str_replace(',', '', $street);

        $adress = '{';
        $adress .= '"street": "'. $street;
        $adress .= '", "city": "' . $order->getBillingAddress()->getData('city');
        $adress .= '", "stateProvince": "' . $order->getBillingAddress()->getData('region');
        $adress .= '", "zipcode": "' . $order->getBillingAddress()->getData('postcode');
        $adress .= '", "country": "' . $order->getBillingAddress()->getData('country_id');
        $adress .= '"}';


        $url .= '&address=' . urlencode($adress);
        $url .= '&showCloseButton=true&r9RUK';

        return $url;
    }

    /**
     * Generate Payment Form
     *
     * @param $payload
     */
    private function postToCheckout($payload)
    {
        $rand = rand();
        echo
        "<html>
            <body>
            <form id='form' action='$payload' method='get'>
            <input type='hidden' value='$rand'>
            </form>
            </body>";
        echo
            "<script>
                window.location.replace('$payload');
            </script>
            <!-- $rand -->
        </html>";
    }

    /**
     * Execute Payment Form
     *
     * @return void
     */
    public function execute() {
        $this->setCode($this->getRequest()->getParam('code'));
        $code = $this->getRequest()->getParam('code');

        try {
            $order = $this->getOrder();
            if ($order->getState() === Order::STATE_PENDING_PAYMENT) {
                $payload = $this->getPayload($order);
                $this->postToCheckout($payload);
            } else if ($order->getState() === Order::STATE_CANCELED) {
                $errorMessage = $this->getCheckoutSession()->getPledgErrorMessage(); //set in InitializationRequest
                if ($errorMessage) {
                    $this->getMessageManager()->addWarningMessage($errorMessage);
                    $errorMessage = $this->getCheckoutSession()->unsPledgErrorMessage();
                }
                $this->getCheckoutHelper()->restoreQuote(); //restore cart
                $this->_redirect('checkout/cart');
            } else {
                $this->getLogger()->debug('Order in unrecognized state: ' . $order->getState());
                $this->_redirect('checkout/cart');
            }
        } catch (Exception $ex) {
            $this->getLogger()->debug('An exception was encountered in pledg/checkout/index: ' . $ex->getMessage());
            $this->getLogger()->debug($ex->getTraceAsString());
            $this->getMessageManager()->addErrorMessage(__('Impossible de procéder au paiement Pledg.'));
        }
    }
}