# PayCore.io payment module for Magento 2 #

- Tags: payments, paycore, magento 2, cashless payments
- Requires at least: 2.1
- Tested up to: 2.2.1
- License: GPLv3
- License URI: http://www.gnu.org/licenses/gpl-3.0.html

Take cashless payments on your magento store using PayCore.io.

## Description ##
To accept payment you need create an account on [PayCore.io](https://dashboard.paycore.io).
Module accepts all payment methods available for your PayCore.io account directly on your Checkout page with.

## Why choose PayCore.io? ##

PayCore.io supports a lot of payment service providers like Interkassa, Liqpay, WalletOne, Platon, PayMaster and others! You can plugin this PSPs to your PayCore.io payment page and adjust payments settings to your needs, check the [PayCore.io docs](https://docs.paycore.io) to get more information.

## Installation ##
1. Copy downloded module in your store root directory.
2. In terminal execute these commands: 
```sh
cd /path/to/your/magento/root/directory
php bin/magento module:enable PaycorePayments_Paycore 
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
php bin/magento cache:clean
```
3. Check for appearing module in app/etc/config.php.

## Setting Module ##

1. Go to ad admin -> stores -> configuration -> sales -> payment methods -> PayCore.io/
2. For testing enable test mode (CAUTION money does not charged in test mode)
3. Enter you secret and public keys from you PayCore.io checkout [settings page](https://dashboard.paycore.io/checkout/payment-pages).
4. Enable module and save configurations.
5. Clean cache by executing command in terminal (in your store root directory):
```sh
php bin/magento cache:clean
```


