/**
 * PayCore.io Extension for Magento 2
 *
 * @author     PayCore.io https://paycore.io
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 */
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
                type: 'paycorepayments_paycore',
                component: 'PaycorePayments_Paycore/js/view/payment/method-renderer/paycore'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);