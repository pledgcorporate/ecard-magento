<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 *
 * @author Gildas Rossignon <gildas@ginidev.com>
 * @package Pledg_PledgPaymentGateway
 */
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
