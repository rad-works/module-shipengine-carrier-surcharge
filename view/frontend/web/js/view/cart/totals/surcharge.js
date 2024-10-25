define(
    [
        'Magento_Checkout/js/view/summary/abstract-total',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/totals',
        'mage/translate'
    ],
    function (Component, quote, totals, $t) {
        "use strict";
        return Component.extend({
            defaults: {
                template: 'RadWorks_ShipStationCarrierSurcharge/checkout/cart/totals/surcharge'
            },

            totals: quote.getTotals(),

            isDisplayed: function () {
                return !!this.getSurchargeValue();
            },

            getValue: function () {
                return this.getFormattedPrice(this.getSurchargeValue());
            },

            getSurchargeValue: function () {
                if (this.getSegment()) {
                    return parseFloat(this.getSegment().value);
                }

                return 0;
            },

            getTitle: function () {
                return this.getSegment().label || $t('Surcharge');
            },

            getSegment: function () {
                if (!this.totals()) {
                    return false;
                }

                return totals.getSegment('shipstation_surcharge');
            }
        });
    }
);