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
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/url-builder',
        'mage/url',
        'Magento_Checkout/js/model/quote',
        'Magento_Ui/js/modal/alert',
    ],
    function (
        $,
        Component,
        urlBuilder,
        url,
        quote,
        alert) {
        'use strict';

        var self;

        return Component.extend({
            redirectAfterPlaceOrder: false,

            defaults: {
                template: 'Pledg_PledgPaymentGateway/payment/form'
            },

            isPledgLoaded: false,

            isClick: false,

            pledgObject: null,

            pledgTransactionId: null,

            initialize: function() {
                this._super();
                self = this;
            },

            getCode: function() {
                return 'pledg_gateway_1';
            },

            afterPlaceOrder: function () {
                window.location.replace(
                    url.build(
                        'pledg/checkout/index?code=' + this.getCode() +
                        '&transaction_id=' + this.pledgTransactionId +
                        '&secret=' + btoa(
                            this.pledgTransactionId + '#' + this.getCode() + '#' +
                            quote.getQuoteId() + '#' + this.getCode() + '#' +
                            this.getMerchantUid()
                        )
                    )
                );
            },

            /*
             * This same validation is done server-side in InitializationRequest.validateQuote()
             */
            validate: function() {

                var billingAddress = quote.billingAddress();
                var shippingAddress = quote.shippingAddress();
                var allowedCountries = self.getAllowedCountries();
                var totals = quote.totals();
                var allowedCountriesArray = [];

                if(typeof(allowedCountries) == 'string' && allowedCountries.length > 0){
                    allowedCountriesArray = allowedCountries.split(',');
                }

                self.messageContainer.clear();

                if (!billingAddress) {
                    self.messageContainer.addErrorMessage({'message': 'Please enter your billing address'});
                    return false;
                }

                if (!billingAddress.firstname ||
                    !billingAddress.lastname ||
                    !billingAddress.street ||
                    !billingAddress.city ||
                    !billingAddress.postcode ||
                    billingAddress.firstname.length == 0 ||
                    billingAddress.lastname.length == 0 ||
                    billingAddress.street.length == 0 ||
                    billingAddress.city.length == 0 ||
                    billingAddress.postcode.length == 0) {
                    self.messageContainer.addErrorMessage({'message': 'Please enter your billing address details'});
                    return false;
                }

                /*if (allowedCountriesArray.indexOf(billingAddress.countryId) == -1 ||
                    allowedCountriesArray.indexOf(shippingAddress.countryId) == -1) {
                    self.messageContainer.addErrorMessage({'message': 'Orders from this country are not supported by Pledg. Please select a different payment option.'});
                    return false;
                }

                if (totals.grand_total < 20) {
                    self.messageContainer.addErrorMessage({'message': 'Pledg doesn\'t support purchases less than $20.'});
                    return false;
                }*/

                return true;
            },

            getTitle: function() {
                return window.checkoutConfig.payment.pledg_gateway_1.title;
            },

            getDescription: function() {
                return window.checkoutConfig.payment.pledg_gateway_1.description;
            },

            getPledgLogo:function(){
                var logo = window.checkoutConfig.payment.pledg_gateway_1.logo;

                return logo;
            },

            getMerchantUid:function(){
                var merchant_uid = window.checkoutConfig.payment.pledg_gateway_1.merchant_uid;

                return merchant_uid;
            },

            getAllowedCountries: function() {
                return window.checkoutConfig.payment.pledg_gateway_1.allowed_countries;
            },

            isPlaceOrderEnabled: function()
            {
                if (this.isBillingAddressSet() && document.querySelector("#" + this.getCode()).checked) {
                    this.initPledg();


                    if (!this.isClick) {
                        $("#trigger_pledg_form_" + this.getCode()).trigger('click');
                        this.isClick = true;
                    }
                    /*var existCondition = setInterval(function() {
                        if ($('.payment-method').length) {
                            clearInterval(existCondition);
                            console.log('click');
                            $("#" + this.getCode()).trigger('click');
                        }
                    }, 100);*/

                    return true;
                }

                if (document.querySelector("#" + this.getCode()).checked) {
                    this.isPledgLoaded = false;
                    this.pledgObject = null;
                    this.isClick = false;
                    //$("#trigger_pledg_form_" + this.getCode()).off('click');

                    document.querySelector('#container_pledg_form_' + this.getCode()).innerHTML = ""
                }

                return false;
            },

            isBillingAddressSet: function()
            {
                return quote.billingAddress() && quote.billingAddress().canUseForBilling();
            },

            onCheckoutFormRendered: function() {
            },

            initPledg: function() {
                if (this.isPledgLoaded || !document.querySelector("#" + this.getCode()).checked) {
                    return;
                }
                var button = document.querySelector("#trigger_pledg_form_" + this.getCode());

                var self = this;

                this.pledgObject = new Pledg(button, {
                    // the Pledg merchant id
                    merchantUid: this.getMerchantUid(),
                    //container: document.querySelector('#container_pledg_form_' + this.getCode()),
                    containerElement: document.querySelector('#container_pledg_form_' + this.getCode()),
                    // the amount **in cents** of the purchase
                    amountCents: window.checkoutConfig.quoteData.base_grand_total*100,
                    // the email of the customer (optional - here, it is retrieved from a control on the page)
                    email: this.getEmail(),
                    // the title of the purchase
                    title: "Quote " + quote.getQuoteId(),
                    // the subtitle of the purchase
                    subtitle: "Quote " + quote.getQuoteId(),
                    // the reference of the purchase
                    reference: "quote_"+quote.getQuoteId()+Math.random(),
                    // the name of the customer (optional, to improve anti-fraud)
                    firstName: quote.shippingAddress().firstName,
                    lastName: quote.shippingAddress().lastName,
                    currency: window.checkoutConfig.quoteData.base_currency_code,
                    // the shipping address (optional, to improve anti-fraud)
                    address: {
                        street: quote.shippingAddress().street.join(' '),
                        city: quote.shippingAddress().city,
                        zipcode: quote.shippingAddress().postcode,
                        stateProvince: quote.shippingAddress().region,
                        country: quote.shippingAddress().countryId
                    },
                    showCloseButton: false,
                    // the function which triggers the payment
                    onSuccess: function (result) {
                        self.pledgTransactionId = result.purchase.uid;
                        self.placeOrder();
                    },
                    onError: function (error) {
                        self.pledgTransactionId = null;
                        alert({
                            title: $.mage.__('Erreur de paiement'),
                            content: $.mage.__(error.message),
                            clickableOverlay: false,
                            actions: {
                                always: function(){}
                            },
                            buttons: [{
                                text: $.mage.__('Revenir au panier'),
                                class: 'action-danger action-accept',
                                click: function () {
                                    window.location.replace(
                                        url.build(
                                            '/checkout/cart'
                                        )
                                    );
                                }
                            }]
                        });
                    },
                });

                this.isPledgLoaded = true;
            },

            getEmail: function() {
                if(quote.guestEmail) return quote.guestEmail;
                else return window.checkoutConfig.customerData.email;
            }

        });
    }
);


