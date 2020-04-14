/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 *
 * @author Gildas Rossignon <gildas@ginidev.com>
 * @package Pledg_PledgPaymentGateway
 */
/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'pledg_gateway_10',
                component: 'Pledg_PledgPaymentGateway/js/view/payment/method-renderer/pledg_gateway_10'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);
