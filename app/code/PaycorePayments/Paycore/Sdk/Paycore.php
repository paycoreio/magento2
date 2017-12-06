<?php

/**
 * PayCore.io Extension for Magento 2
 *
 * @author     PayCore.io https://paycore.io
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 */

namespace PaycorePayments\Paycore\Sdk;

use PaycorePayments\Paycore\Helper\Data as Helper;
use InvalidArgumentException;

/**
 * Class Paycore
 *
 * @package PaycorePayments\Paycore\Sdk
 */
class Paycore
{
    const STATUS_CREATED = 'created';
    const STATUS_PENDING = 'pending';
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILURE = 'failure';
    const STATUS_EXPIRED = 'expired';
    const STATUS_CANCELED = 'canceled';
    const STATUS_REFUNDED = 'refunded';

    const CURRENCY_EUR = 'EUR';
    const CURRENCY_USD = 'USD';
    const CURRENCY_UAH = 'UAH';
    const CURRENCY_RUB = 'RUB';
    const CURRENCY_RUR = 'RUR';

    /**
     * @var string
     */
    private $_api_url = 'http://checkout.dev.paycore.io:81/';
    /**
     * @var string
     */
    private $_checkout_url = 'http://checkout.dev.paycore.io:81/';
    /**
     * @var array
     */
    protected $_supportedCurrencies = array(
        self::CURRENCY_EUR,
        self::CURRENCY_USD,
        self::CURRENCY_UAH,
        self::CURRENCY_RUB,
        self::CURRENCY_RUR,
    );
    /**
     * @var string
     */
    private $_public_key;
    /**
     * @var string
     */
    private $_private_key;
    /**
     * @var null
     */
    private $_server_response_code = null;

    /**
     * @var Helper
     */
    protected $_helper;

    /**
     * Paycore constructor.
     * @param Helper $helper
     */
    public function __construct(Helper $helper)
    {
        $this->_helper = $helper;
        if ($helper->isEnabled()) {
            $this->_public_key = $helper->getPublicKey();
            $this->_private_key = $helper->getSecretKey();
        }
    }

    /**
     * Return last api response http code
     *
     * @return string|null
     */
    public function get_response_code()
    {
        return $this->_server_response_code;
    }

    /**
     * cnb_form
     *
     * @param array $params
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    public function cnb_form($params)
    {
        $language = 'ru';
        if (isset($params['language']) && $params['language'] == 'en') {
            $language = 'en';
        }

        $params    = $this->cnb_params($params);
        $data      = $this->encode_params($params);
        $signature = $this->cnb_signature($params);

        return sprintf('
            <form method="POST" action="%s" accept-charset="utf-8">
                %s
                %s
            </form>
            ',
            $this->_checkout_url,
            sprintf('<input type="hidden" name="%s" value="%s" />', 'data', $data),
            sprintf('<input type="hidden" name="%s" value="%s" />', 'signature', $signature)
        );
    }

    /**
     * cnb_signature
     *
     * @param array $params
     *
     * @return string
     */
    public function cnb_signature($params)
    {
        $params      = $this->cnb_params($params);
        $private_key = $this->_private_key;

        $json      = $this->encode_params($params);
        $signature = $this->str_to_sign($private_key . $json . $private_key);

        return $signature;
    }

    /**
     * cnb_params
     *
     * @param array $params
     *
     * @return array $params
     */
    private function cnb_params($params)
    {
        $params['public_key'] = $this->_public_key;

        if (!isset($params['amount'])) {
            throw new InvalidArgumentException('amount is null');
        }
        if (!isset($params['currency'])) {
            throw new InvalidArgumentException('currency is null');
        }
        if (!isset($params['description'])) {
            throw new InvalidArgumentException('description is null');
        }

        return $params;
    }

    /**
     * encode_params
     *
     * @param array $params
     * @return string
     */
    private function encode_params($params)
    {
        return base64_encode(json_encode($params));
    }

    /**
     * decode_params
     *
     * @param string $params
     * @return array
     */
    public function decode_params($params)
    {
        return json_decode(base64_decode($params), true);
    }

    /**
     * str_to_sign
     *
     * @param string $str
     *
     * @return string
     */
    public function str_to_sign($str)
    {
        $signature = base64_encode(sha1($str, 1));

        return $signature;
    }

    /**
     * @return Helper
     */
    public function getHelper()
    {
        return $this->_helper;
    }

    /**
     * @return array
     */
    public function getSupportedCurrencies()
    {
        return $this->_supportedCurrencies;
    }

    /**
     * @param $data
     * @return mixed
     */
    public function getDecodedData($data)
    {
        return json_decode(base64_decode($data), true, 1024);
    }

    /**
     * @param $signature
     * @param $data
     * @return bool
     */
    public function checkSignature($signature, $data)
    {
        $privateKey = $this->_helper->getSecretKey();
        $generatedSignature = base64_encode(sha1($privateKey . $data . $privateKey, 1));

        return $signature === $generatedSignature;
    }
}
