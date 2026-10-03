define([
    'Magento_Checkout/js/view/summary/abstract-total',
    'Magento_Checkout/js/model/totals'
], function (Component, totals) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Doit_HandlingFee/summary/handling-fee'
        },

        getFee: function () {
            var segment = totals.getSegment('doit_handling_fee');

            return segment ? Number(segment.value) : 0;
        },

        isDisplayed: function () {
            return this.getFee() > 0;
        },

        getValue: function () {
            return this.getFormattedPrice(this.getFee());
        }
    });
});
