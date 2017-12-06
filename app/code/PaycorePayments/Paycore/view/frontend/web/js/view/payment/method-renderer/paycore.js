/**
 * PayCore.io Extension for Magento 2
 *
 * @author     PayCore.io https://paycore.io
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 */
define(
    [
        'Magento_Payment/js/view/payment/cc-form',
        'jquery',
        'mage/url',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Payment/js/model/credit-card-validation/validator'
    ],
    function (Component, $, url) {
        'use strict';

        return Component.extend({
            redirectAfterPlaceOrder: false,
            defaults: {
                template: 'PaycorePayments_Paycore/payment/checkout'
            },
            getCode: function() {
                return 'paycorepayments_paycore';
            },
            isActive: function() {
                return true;
            },
            validate: function() {
                var $form = $('#' + this.getCode() + '-form');
                return $form.validation() && $form.validation('isValid');
            },
            afterPlaceOrder: function () {
                $.post(url.build('paycore/checkout/form'), {
                    'random_string': this._generateRandomString(30)
                }).done(function(data) {
                    if (!data.status) {
                        return
                    }
                    if (data.status == 'success') {
                        if (data.content) {
                            var html = '<div id="paycoreSubmitFrom" style="display: none;">' + data.content + '</div>';
                            $('body').append(html);
                            $('#paycoreSubmitFrom form:first').submit();
                        }
                    } else {
                        if (data.redirect) {
                            window.location = data.redirect;
                        }
                    }
                });
            },
            _generateRandomString: function(length) {
                if (!length) {
                    length = 10;
                }
                var text = '';
                var possible = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
                for (var i = 0; i < length; ++i) {
                    text += possible.charAt(Math.floor(Math.random() * possible.length));
                }
                return text;
            }
        });
    }
);