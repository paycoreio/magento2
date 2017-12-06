<?php

/**
 * PayCore.io Extension for Magento 2
 *
 * @author     PayCore.io https://paycore.io
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 */

namespace PaycorePayments\Paycore\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use PaycorePayments\Paycore\Model\Payment as PaycorePayment;
use Magento\Payment\Helper\Data as PaymentHelper;


class Data extends AbstractHelper
{
    const XML_PATH_IS_ENABLED        = 'payment/paycorepayments_paycore/active';
    const XML_PATH_PUBLIC_KEY        = 'payment/paycorepayments_paycore/public_key';
    const XML_PATH_SECRET_KEY        = 'payment/paycorepayments_paycore/secret_key';
    const XML_PATH_TEST_PUBLIC_KEY   = 'payment/paycorepayments_paycore/test_public_key';
    const XML_PATH_TEST_SECRET_KEY   = 'payment/paycorepayments_paycore/test_secret_key';
    const XML_PATH_TEST_MODE         = 'payment/paycorepayments_paycore/test_mode';
    const XML_PATH_DESCRIPTION       = 'payment/paycorepayments_paycore/description';

    /**
     * @var PaymentHelper
     */
    protected $_paymentHelper;

    /**
     * Data constructor.
     * @param Context $context
     * @param PaymentHelper $paymentHelper
     */
    public function __construct(Context $context,
                                PaymentHelper $paymentHelper)
    {
        parent::__construct($context);
        $this->_paymentHelper = $paymentHelper;
    }


    /**
     * @return bool
     */
    public function isEnabled()
    {
        if ($this->scopeConfig->getValue(
            static::XML_PATH_IS_ENABLED,
            ScopeInterface::SCOPE_STORE
        )
        ) {
            if ($this->getPublicKey() && $this->getSecretKey()) {
                return true;
            } else {
                $this->_logger->error(__('The PaycorePayments\Paycore module is turned off, because public or secret key is not set'));
            }
        }
        return false;
    }

    /**
     * @return mixed
     */
    public function isTestMode()
    {
        return $this->scopeConfig->getValue(
            static::XML_PATH_TEST_MODE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return string
     */
    public function getPublicKey()
    {
        $path = $this->isTestMode() ? static::XML_PATH_TEST_PUBLIC_KEY : static::XML_PATH_PUBLIC_KEY;

        return trim($this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE
        ));
    }

    /**
     * @return string
     */
    public function getSecretKey()
    {
        $path = $this->isTestMode() ? static::XML_PATH_TEST_SECRET_KEY : static::XML_PATH_SECRET_KEY;

        return trim($this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE
        ));
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface|null $order
     * @return string
     */
    public function getPaycoreDescription(\Magento\Sales\Api\Data\OrderInterface $order = null)
    {
        $description = trim($this->scopeConfig->getValue(
            static::XML_PATH_DESCRIPTION,
            ScopeInterface::SCOPE_STORE
        ));
        $params = [
            '{order_id}' => $order->getIncrementId(),
        ];
        return strtr($description, $params);
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function checkOrderIsPaycorePayment(\Magento\Sales\Api\Data\OrderInterface $order)
    {
        $method = $order->getPayment()->getMethod();
        $methodInstance = $this->_paymentHelper->getMethodInstance($method);
        return $methodInstance instanceof PaycorePayment;
    }

    /**
     * @param $data
     * @param $receivedPublicKey
     * @param $receivedSignature
     * @return bool
     */
    public function securityOrderCheck($data, $receivedSignature)
    {
        $privateKey = $this->getSecretKey();
        $generatedSignature = base64_encode(sha1($privateKey . $data . $privateKey, 1));

        return $receivedSignature === $generatedSignature;
    }

    /**
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger()
    {
        return $this->_logger;
    }
}