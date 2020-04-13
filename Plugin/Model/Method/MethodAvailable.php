<?php
/**
 * MethodAvailable class
 *
 * @author  Gildas Rossignon <gildas@ginidev.com>
 * @package Ginidev_PledgPaymentGateway
 */

namespace Ginidev\PledgPaymentGateway\Plugin\Model\Method;

class MethodAvailable
{
    public $_scopeConfig;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->_scopeConfig = $scopeConfig;
    }

    /**
     * @param Magento\Payment\Model\MethodList $subject
     * @param $result
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetAvailableMethods(\Magento\Payment\Model\MethodList $subject, $result)
    {
        $t = [];
        foreach ($result as $key=>$_result) {

            $sortOrder = $this->_scopeConfig->getValue('payment/'.$_result->getCode().'/sort_order');
            $sortOrder = is_null($sortOrder) ? 999 : (int)$sortOrder;

            $t[$_result->getCode()]['res'] = $_result;
            $t[$_result->getCode()]['order'] = $sortOrder;
        }

        usort($t, function($a, $b) {
            if ($a['order'] == $b['order']) {
                return 0;
            }
            return $a['order'] > $b['order'] ? 1 : -1;
        });

        $newResult = [];
        foreach ($t as $val) {
            $newResult[] = $val['res'];
        }

        return $newResult;
    }
}
