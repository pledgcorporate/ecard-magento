<?php

namespace Ginidev\PledgPaymentGateway\Controller\Checkout;

use Magento\Sales\Model\Order;

/**
 * @package Ginidev\PledgPaymentGateway\Controller\Checkout
 */
class Success extends AbstractAction {

    public function execute() {
        $pledgResult = $this->getRequest()->get("pledg_result");

        $pledg = json_decode($pledgResult);

        $transactionId = $pledg->transaction->id;

        $order = $this->getOrder();

        //@Todo : Check signature


        /*$isValid = $this->getCryptoHelper()->isValidSignature($this->getRequest()->getParams(), $this->getGatewayConfig()->getApiKey());
        $result = $this->getRequest()->get("x_result");
        $orderId = $this->getRequest()->get("x_reference");
        $transactionId = $this->getRequest()->get("x_gateway_reference");

        if(!$isValid) {
            $this->getLogger()->debug('Possible site forgery detected: invalid response signature.');
            $this->_redirect('checkout/onepage/error', array('_secure'=> false));
            return;
        }*/

        /*if(!$orderId) {
            $this->getLogger()->debug("Pledg returned a null order id. This may indicate an issue with the Pledg payment gateway.");
            $this->_redirect('checkout/onepage/error', array('_secure'=> false));
            return;
        }

        $order = $this->getOrderById($orderId);*/
        if(!$order) {
            $this->getLogger()->debug("Pledg returned an id for an order that could not be retrieved: $orderId");
            $this->_redirect('checkout/onepage/error', array('_secure'=> false));
            return;
        }

        if($order->getState() === Order::STATE_PROCESSING) {
            $this->_redirect('checkout/onepage/success', array('_secure'=> false));
            return;
        }

        if($order->getState() === Order::STATE_CANCELED) {
            $this->_redirect('checkout/onepage/failure', array('_secure'=> false));
            return;
        }

        if (true/*$pledg->amount_cents == (int)($order->getTotalDue() * 100)*/) {
            $orderState = Order::STATE_PROCESSING;

            $orderStatus = $this->getGatewayConfig()->getPledgApprovedOrderStatus();
            if (!$this->statusExists($orderStatus)) {
                $orderStatus = $order->getConfig()->getStateDefaultStatus($orderState);
            }

            $emailCustomer = $this->getGatewayConfig()->isEmailCustomer();

            $order->setState($orderState)
                ->setStatus($orderStatus)
                ->addStatusHistoryComment("Pledg authorisation success. Transaction #$transactionId")
                ->setIsCustomerNotified($emailCustomer);

	        $payment = $order->getPayment();
	        $payment->setTransactionId($transactionId);
	        $payment->addTransaction(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE, null, true);
            $order->save();

            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $emailSender = $objectManager->create('\Magento\Sales\Model\Order\Email\Sender\OrderSender');
            $emailSender->send($order);

            $invoiceAutomatically = $this->getGatewayConfig()->isAutomaticInvoice();
            if ($invoiceAutomatically) {
                $this->invoiceOrder($order, $transactionId);
            }

            $this->getMessageManager()->addSuccessMessage(__("Le paiement Pledg a été validé"));
            $this->_redirect('checkout/onepage/success', array('_secure'=> false));
        } else {
            $this->getCheckoutHelper()->cancelCurrentOrder("Order #".($order->getId())." was rejected by pledg. Transaction #$transactionId.");
            $this->getCheckoutHelper()->restoreQuote(); //restore cart
            $this->getMessageManager()->addErrorMessage(__("Il y eu une erreur lors du paiement Pledg"));
            $this->_redirect('checkout/cart', array('_secure'=> false));
        }
    }

    private function statusExists($orderStatus)
    {
        $statuses = $this->getObjectManager()
            ->get('Magento\Sales\Model\Order\Status')
            ->getResourceCollection()
            ->getData();
        foreach ($statuses as $status) {
            if ($orderStatus === $status["status"]) return true;
        }
        return false;
    }

    private function invoiceOrder($order, $transactionId)
    {
        if(!$order->canInvoice()){
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

}
