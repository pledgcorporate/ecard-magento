<?php

namespace Pledg\PledgPaymentGateway\Block;

use Magento\Framework\Phrase;
use Magento\Payment\Block\Info as BaseInfo;

/**
 * Class Info
 */
class Info extends BaseInfo
{
    /**
     * Returns label
     *
     * @param string $field
     * @return Phrase
     */
    protected function getLabel($field)
    {
        return __($field);
    }
}
