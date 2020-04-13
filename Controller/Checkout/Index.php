<?php

namespace Ginidev\PledgPaymentGateway\Controller\Checkout;

use Magento\Sales\Model\Order;

/**
 * @package Ginidev\PledgPaymentGateway\Controller\Checkout
 */
class Index extends AbstractAction {

    private function getPayload($order) {
        if($order == null) {
            $this->getLogger()->debug('Unable to get order from last lodged order id. Possibly related to a failed database call');
            $this->_redirect('checkout/onepage/error', array('_secure'=> false));
        }

        $url = $this->getURLPayment();
        $url .= '&amountCents=' . (int)($order->getTotalDue() * 100);
        $url .= '&title=' . $this->getDataHelper()->getStoreCode();
        $url .= '&email=' . $order->getData('customer_email');
        $url .= '&reference=' . $order->getRealOrderId();
        $url .= '&currency=' . $order->getOrderCurrencyCode();
        $url .= '&redirectUrl=' . $this->getDataHelper()->getCompleteUrl();
        $url .= '&cancelUrl=' . $this->getDataHelper()->getCancelledUrl($order->getRealOrderId());
        $url .= '&firstname=' . $order->getCustomerFirstname();
        $url .= '&lastname=' . $order->getCustomerLastname();
        $url .= '&phoneNumber=' . $order->getBillingAddress()->getData('telephone');

        // Address
        $street = preg_replace('/\s+/', ' ', trim($order->getBillingAddress()->getData('street')));https://staging.front.ecard.pledg.co/purchase?merchantUid=mer_35c64fe7-aecc-469b-bbde-05c0d2572931&amountCents=100500&title=default&email=g_ildas@yahoo.fr&reference=000000031&currency=EUR&paymentNotificationUrl=&redirectUrl=http://pledg.loc:8888/pledg/checkout/success&cancelUrl=http://pledg.loc:8888/pledg/checkout/cancel?orderId=000000031&firstname=Gildas&lastname=Rossignon&phoneNumber=0782787242&address=%7B%22street%22%3A%222%2C%20rue%20Fr%C3%A9zier%22%2C%22city%22%3A%22Brest%22%2C%22zipcode%22%3A%2229200%22%2C%22stateProvince%22%3A%22Bretagne%22%2C%22country%22%3A%22FR%22%7D&shippingAddress=%7B%22street%22%3A%222%20rue%20Fr%C3%A9zier%22%2C%22city%22%3A%22Brest%22%2C%22zipcode%22%3A%2229200%22%2C%22stateProvince%22%3A%22Bretagne%22%2C%22country%22%3A%22FR%22%7D
        $street = str_replace(',', '', $street);

        //https://staging.front.ecard.pledg.co/purchase?merchantUid=mer_35c64fe7-aecc-469b-bbde-05c0d2572931&title=S%C3%89JOUR%20%C3%80%20LONDRES&subtitle=Vol%20%2B%20H%C3%B4tel%202%20nuits%20(3%20chambres)&reference=PLEDG_9009559392261&amountCents=30550&currency=EUR&&firstName=Ga%C3%ABlle&lastName=Gu%C3%A9guen&email=sales%40pledg.co&phoneNumber=%2B33663558607&birthDate=1981-07-24&birthCity=Nantes&birthStateProvince=Bretagne&birthCountry=FR&redirectUrl=https%3A%2F%2Fstaging.merchant.ecard.pledg.co%2Fsuccess-installment-url-call&cancelUrl=https%3A%2F%2Fstaging.merchant.ecard.pledg.co%2Finstallment-url-call.html&address
        //https://staging.front.ecard.pledg.co/purchase?merchantUid=mer_35c64fe7-aecc-469b-bbde-05c0d2572931&amountCents=100500&title=default&email=g_ildas@yahoo.fr&reference=000000031&currency=EUR&paymentNotificationUrl=&redirectUrl=http://pledg.loc:8888/pledg/checkout/success&cancelUrl=http://pledg.loc:8888/pledg/checkout/cancel?orderId=000000031&firstname=Gildas&lastname=Rossignon&phoneNumber=0782787242&address=%7Bstreet%3A+55+rue+de+Besonfosse%2C+city%3A+EPINAL%2C+stateProvince%3A+Vosges%2C+zipcode%3A+88000%2C+country%3A+FR%7D&showCloseButton=true&r9RUK
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

        var_dump($url);exit;

        $shippingAddress = $order->getShippingAddress();
        $billingAddress = $order->getBillingAddress();

        $billingAddressParts = preg_split('/\r\n|\r|\n/', $billingAddress->getData('street'));
        $shippingAddressParts = preg_split('/\r\n|\r|\n/', $shippingAddress->getData('street'));

        $orderId = $order->getRealOrderId();
        $data = array(
            'x_currency' => $order->getOrderCurrencyCode(),
            'x_url_callback' => $this->getDataHelper()->getCompleteUrl(),
            'x_url_complete' => $this->getDataHelper()->getCompleteUrl(),
            'x_url_cancel' => $this->getDataHelper()->getCancelledUrl($orderId),
            'x_shop_name' => $this->getDataHelper()->getStoreCode(),
            'x_account_id' => $this->getGatewayConfig()->getMerchantNumber(),
            'x_reference' => $orderId,
            'x_invoice' => $orderId,
            'x_amount' => $order->getTotalDue(),
            'x_customer_first_name' => $order->getCustomerFirstname(),
            'x_customer_last_name' => $order->getCustomerLastname(),
            'x_customer_email' => $order->getData('customer_email'),
            'x_customer_phone' => $billingAddress->getData('telephone'),
            'x_customer_billing_address1' => $billingAddressParts[0],
            'x_customer_billing_address2' => count($billingAddressParts) > 1 ? $billingAddressParts[1] : '',
            'x_customer_billing_city' => $billingAddress->getData('city'),
            'x_customer_billing_state' => $billingAddress->getData('region'),
            'x_customer_billing_zip' => $billingAddress->getData('postcode'),
            'x_customer_shipping_address1' => $shippingAddressParts[0],
            'x_customer_shipping_address2' => count($shippingAddressParts) > 1 ? $shippingAddressParts[1] : '',
            'x_customer_shipping_city' => $shippingAddress->getData('city'),
            'x_customer_shipping_state' => $shippingAddress->getData('region'),
            'x_customer_shipping_zip' => $shippingAddress->getData('postcode'),
            'x_test' => 'false'
        );

        foreach ($data as $key => $value) {
            $data[$key] = preg_replace('/\r\n|\r|\n/', ' ', $value);
        }

        $apiKey = $this->getGatewayConfig()->getApiKey();
        $signature = $this->getCryptoHelper()->generateSignature($data, $apiKey);
        $data['x_signature'] = $signature;

        return $data;
    }

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
     *
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
