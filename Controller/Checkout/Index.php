<?php

namespace Pledg\PledgPaymentGateway\Controller\Checkout;

use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Sales\Model\Order;

/**
 * @package Pledg\PledgPaymentGateway\Controller\Checkout
 */
class Index extends AbstractAction
{

    /**
     * Generate Payment URL
     *
     * @param $order
     * @return string
     */
    private function getPayload($order)
    {
        if ($order == null) {
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
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $this->setCode($this->getRequest()->getParam('code'));
        $order = $this->getOrder();

        // Check order status
        if ($order->getState() !== Order::STATE_PENDING_PAYMENT) {
            return $this->errorOrder('Order in unrecognized state: ' . $order->getState());
        }

        // Decode secret
        $dataPledg = explode(
            '#'.$this->getRequest()->getParam('code').'#',
            base64_decode($this->getRequest()->getParam('secret'))
        );

        if (count($dataPledg) != 3) {
            return $this->errorOrder('Secret Pledg invalid count');
        }

        // Check merchant uid
        if ($this->getMerchantUid() != $dataPledg[2]) {
            return $this->errorOrder('Secret Pledg invalid uid');
        }

        // Check transaction id
        $transactionId = $dataPledg[0];
        if ($transactionId != $this->getRequest()->getParam('transaction_id')) {
            return $this->errorOrder('Pledg Transaction ID invalid');
        }

        // Check quote id
        $quoteMaskId = $dataPledg[1];
        $quoteMask = $this->getQuoteIdMaskFactory()->create()->load($quoteMaskId, 'masked_id');
        if ($dataPledg[1] != $this->getOrder()->getQuoteId() && $quoteMask->getQuoteId() != $this->getOrder()->getQuoteId()) {
            return $this->errorOrder('Secret Pledg invalid quote');
        }

        $orderState = Order::STATE_PROCESSING;

        // TODO default order status should be retrived on order payment method
//        $orderStatus = $this->getGatewayConfig()->getPledgApprovedOrderStatus();
        $orderStatus = 'processing';
        if (!$this->statusExists($orderStatus)) {
            $orderStatus = $order->getConfig()->getStateDefaultStatus($orderState);
        }

        $order->setState($orderState)
            ->setStatus($orderStatus)
            ->addStatusHistoryComment("Pledg authorisation success. Transaction #$transactionId");

        $payment = $order->getPayment();
        $payment->setTransactionId($transactionId);
        $payment->addTransaction(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE, null, true);
        $order->save();

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $emailSender = $objectManager->create('\Magento\Sales\Model\Order\Email\Sender\OrderSender');
        $emailSender->send($order);

        // TODO : confirm that invoice should be created automatically without configuration parameter
//        $invoiceAutomatically = $this->getGatewayConfig()->isAutomaticInvoice();
        $invoiceAutomatically = true;
        if ($invoiceAutomatically) {
            $this->invoiceOrder($order, $transactionId);
        }

        $this->getMessageManager()->addSuccessMessage(__("Le paiement Pledg a été validé"));
        return $this->_redirect('checkout/onepage/success', array('_secure'=> false));
    }

    /**
     * Check if Status exists
     *
     * @param $orderStatus
     * @return bool
     */
    private function statusExists($orderStatus)
    {
        $statuses = $this->getObjectManager()
            ->get('Magento\Sales\Model\Order\Status')
            ->getResourceCollection()
            ->getData();
        foreach ($statuses as $status) {
            if ($orderStatus === $status["status"]) {
                return true;
            }
        }
        return false;
    }

    /**
     * Generate Invoice
     *
     * @param $order
     * @param $transactionId
     */
    private function invoiceOrder($order, $transactionId)
    {
        if (!$order->canInvoice()) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Cannot create an invoice.')
            );
        }

        $invoice = $this->getObjectManager()
            ->create('Magento\Sales\Model\Service\InvoiceService')
            ->prepareInvoice($order);

        if (!$invoice->getTotalQty()) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('You can\'t create an invoice without products.')
            );
        }

        /*
         * Look Magento/Sales/Model/Order/Invoice.register() for CAPTURE_OFFLINE explanation.
         * Basically, if !config/can_capture and config/is_gateway and CAPTURE_OFFLINE and
         * Payment.IsTransactionPending => pay (Invoice.STATE = STATE_PAID...)
         */
        $invoice->setTransactionId($transactionId);
        $invoice->setRequestedCaptureCase(Order\Invoice::CAPTURE_OFFLINE);
        $invoice->register();

        $transaction = $this->getObjectManager()->create('Magento\Framework\DB\Transaction')
            ->addObject($invoice)
            ->addObject($invoice->getOrder());
        $transaction->save();
    }

    /**
     * @param string|string $message
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function errorOrder(string $message)
    {
        $this
            ->getCheckoutHelper()
            ->cancelCurrentOrder(
                "Order #".($this->getOrder()->getId())." $message."
            );
        $this->getCheckoutHelper()->restoreQuote(); //restore cart
        $this->getMessageManager()->addErrorMessage(__($message));
        return $this->_redirect('checkout/cart', array('_secure'=> false));
    }
}
