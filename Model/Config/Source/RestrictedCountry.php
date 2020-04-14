<?php

/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 *
 * @author Gildas Rossignon <gildas@ginidev.com>
 * @package Pledg_PledgPaymentGateway
 */

/**
 * Country config field renderer
 */

namespace Pledg\PledgPaymentGateway\Model\Config\Source;

use Magento\Directory\Model\Config\Source\Country;

class RestrictedCountry extends Country
{
    /**
     * @param \Magento\Directory\Model\ResourceModel\Country\Collection $countryCollection
     */
    public function __construct(\Magento\Directory\Model\ResourceModel\Country\Collection $countryCollection)
    {
        $countryCollection->addCountryIdFilter(array('FR'));

        parent::__construct($countryCollection);
    }
}
