<?php

/**
 * PayCore.io Extension for Magento 2
 *
 * @author     PayCore.io https://paycore.io
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 */

use \Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'PaycorePayments_Paycore',
    __DIR__
);