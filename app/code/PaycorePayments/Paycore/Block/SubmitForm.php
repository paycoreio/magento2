<?php

/**
 * PayCore.io Extension for Magento 2
 *
 * @author     PayCore.io https://paycore.io
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 */

namespace PaycorePayments\Paycore\Block;

use Magento\Framework\View\Element\Template;
use Magento\Sales\Model\Order;
use PaycorePayments\Paycore\Sdk\Paycore;
use PaycorePayments\Paycore\Helper\Data as Helper;


class SubmitForm extends Template
{
    protected $_order = null;

    /* @var $_paycore Paycore */
    protected $_paycore;

    /* @var $_helper Helper */
    protected $_helper;

    public function __construct(
        Template\Context $context,
        Paycore $paycore,
        Helper $helper,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->_paycore = $paycore;
        $this->_helper = $helper;
    }

    /**
     * @return Order
     */
    public function getOrder()
    {
        if ($this->_order === null) {
            throw new \Exception('Order is not set');
        }
        return $this->_order;
    }

    public function setOrder(Order $order)
    {
        $this->_order = $order;
    }

    protected function _loadCache()
    {
        return false;
    }

    protected function _toHtml()
    {
        $order = $this->getOrder();
        $html = $this->_paycore->cnb_form(array(
            'amount' => $order->getGrandTotal() * 100,
            'currency' => $order->getOrderCurrencyCode(),
            'description' => $this->_helper->getPaycoreDescription($order),
            'reference' => $order->getIncrementId(),
            //'ipn_url' => $this->_urlBuilder->getUrl('rest/V1/paycore/callback'),
            'return_url' => $this->_urlBuilder->getUrl('checkout/onepage/success'),
        ));
        return $html;
    }
}