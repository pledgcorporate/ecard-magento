<?php

namespace Ginidev\PledgPaymentGateway\Observer;

use Magento\Framework\Event\ObserverInterface;


class PaymentMethodAvailable implements ObserverInterface
{

    public $_scopeConfig;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->_scopeConfig = $scopeConfig;
    }

    /**
     * payment_method_is_active event handler.
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $pledgCodes = [
            'pledg_gateway_1',
            'pledg_gateway_2',
            'pledg_gateway_3',
            'pledg_gateway_4',
            'pledg_gateway_5',
            'pledg_gateway_6',
            'pledg_gateway_7',
            'pledg_gateway_8',
            'pledg_gateway_9',
            'pledg_gateway_10',
        ];

        // you can replace "checkmo" with your required payment method code
        if(in_array($observer->getEvent()->getMethodInstance()->getCode(), $pledgCodes)){
            $checkResult = $observer->getEvent()->getResult();
            $checkResult->setData(
                'is_available',
                $this
                    ->_scopeConfig
                    ->getValue('payment/'.$observer->getEvent()->getMethodInstance()->getCode().'/active')
            ); //this is disabling the payment method at checkout page
        }
    }
}

