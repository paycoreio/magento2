<?php

/**
 * PayCore.io Extension for Magento 2
 *
 * @author     PayCore.io https://paycore.io
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 */

namespace PaycorePayments\Paycore\Api;


interface PaycoreCallbackInterface
{
    /**
     * @api
     * @return null
     */
    public function callback();
}