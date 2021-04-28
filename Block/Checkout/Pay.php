<?php

namespace Pledg\PledgPaymentGateway\Block\Checkout;

use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Model\Order;

class Pay extends Template
{
    /**
     * @var Registry
     */
    private $registry;

    /**
     * @param Template\Context $context
     * @param Registry         $registry
     * @param array            $data
     */
    public function __construct(Template\Context $context, Registry $registry, array $data = [])
    {
        parent::__construct($context, $data);

        $this->registry = $registry;
    }

    /**
     * @return array
     */
    public function getPledgData(): array
    {
        /** @var Order $order */
        $order = $this->registry->registry('pledg_order');
        $orderIncrementId = $order->getIncrementId();
        $orderAddress = $order->getBillingAddress();
        if (!$order->getIsVirtual()) {
            $orderAddress = $order->getShippingAddress();
        }

        return [
            'merchantUid' => $this->registry->registry('pledg_merchant_id'),
            'amountCents' => round($order->getGrandTotal() * 100),
            'email' => $order->getCustomerEmail(),
            'title' => 'Order ' . $orderIncrementId,
            'subtitle' => 'Order ' . $orderIncrementId,
            'reference' => 'order_' . $orderIncrementId,
            'firstName' => $orderAddress->getFirstname(),
            'lastName' => $orderAddress->getLastname(),
            'currency' => $order->getOrderCurrencyCode(),
            'address' => [
                'street' => is_array($orderAddress->getStreet()) ? implode(' ', $orderAddress->getStreet()) : '',
                'city' => $orderAddress->getCity(),
                'zipcode' => $orderAddress->getPostcode(),
                'stateProvince' => $orderAddress->getRegion(),
                'country' => $orderAddress->getCountryId(),
            ],
        ];
    }
}
