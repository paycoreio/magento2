<?php

/**
 * PayCore.io Extension for Magento 2
 *
 * @author     PayCore.io https://paycore.io
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 */

namespace PaycorePayments\Paycore\Model;


class Payment extends \Magento\Payment\Model\Method\AbstractMethod
{
    const METHOD_CODE = 'paycorepayments_paycore';

    protected $_code = self::METHOD_CODE;

    protected $_paycore;

    protected $_canCapture = true;
    protected $_canVoid = true;
    protected $_canUseForMultishipping = false;
    protected $_canUseInternal = false;
    protected $_isInitializeNeeded = true;
    protected $_isGateway = true;
    protected $_canAuthorize = false;
    protected $_canCapturePartial = false;
    protected $_canRefund = false;
    protected $_canRefundInvoicePartial = false;
    protected $_canUseCheckout = true;

    protected $_minOrderTotal = 0;
    protected $_supportedCurrencyCodes;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\UrlInterface $urlBuider,
        \PaycorePayments\Paycore\Sdk\Paycore $paycore,
        array $data = array()
    )
    {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            null,
            null,
            $data
        );

        $this->_paycore = $paycore;
        $this->_supportedCurrencyCodes = $paycore->getSupportedCurrencies();
        $this->_minOrderTotal = $this->getConfigData('min_order_total');
        $this->_urlBuilder = $urlBuider;
    }

    public function canUseForCurrency($currencyCode)
    {
        if (!in_array($currencyCode, $this->_supportedCurrencyCodes)) {
            return false;
        }
        return true;
    }

    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();
        $billing = $order->getBillingAddress();
        try {
            $payment->setTransactionId('paycore-' . $order->getId())->setIsTransactionClosed(0);
            return $this;
        } catch (\Exception $e) {
            $this->debugData(['exception' => $e->getMessage()]);
            throw new \Magento\Framework\Validator\Exception(__('Payment capturing error.'));
        }
    }

    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if (!$this->_paycore->getHelper()->isEnabled()) {
            return false;
        }
        $this->_minOrderTotal = $this->getConfigData('min_order_total');
        if ($quote && $quote->getBaseGrandTotal() < $this->_minOrderTotal) {
            return false;
        }
        return parent::isAvailable($quote);
    }
}