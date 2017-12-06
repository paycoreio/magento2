<?php

/**
 * PayCore.io Extension for Magento 2
 *
 * @author     PayCore.io https://paycore.io
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 */

namespace PaycorePayments\Paycore\Controller\Checkout;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\LayoutFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use PaycorePayments\Paycore\Helper\Data as Helper;


class Form extends Action
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var Helper
     */
    protected $_helper;

    /**
     * @var LayoutFactory
     */
    protected $_layoutFactory;

    public function __construct(
        Context $context,
        CheckoutSession $checkoutSession,
        Helper $helper,
        LayoutFactory $layoutFactory
    )
    {
        parent::__construct($context);
        $this->_checkoutSession = $checkoutSession;
        $this->_helper = $helper;
        $this->_layoutFactory = $layoutFactory;
    }

    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        try {
            if (!$this->_helper->isEnabled()) {
                throw new \Exception(__('Payment is not allow.'));
            }
            $order = $this->getCheckoutSession()->getLastRealOrder();
            if (!($order && $order->getId())) {
                throw new \Exception(__('Order not found'));
            }
            if ($this->_helper->checkOrderIsPaycorePayment($order)) {
                /* @var $formBlock \PaycorePayments\Paycore\Block\SubmitForm */
                $formBlock = $this->_layoutFactory->create()->createBlock('PaycorePayments\Paycore\Block\SubmitForm');
                $formBlock->setOrder($order);
                $data = [
                    'status' => 'success',
                    'content' => $formBlock->toHtml(),
                ];
            } else {
                throw new \Exception('Order payment method is not a PayCore.io payment method');
            }
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong, please try again later'));
            $this->_helper->getLogger()->critical($e);
            $this->getCheckoutSession()->restoreQuote();
            $data = [
                'status' => 'error',
                'redirect' => $this->_url->getUrl('checkout/cart'),
            ];
        }
        /** @var \Magento\Framework\Controller\Result\Json $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $result->setData($data);
        return $result;
    }


    /**
     * Return checkout session object
     *
     * @return \Magento\Checkout\Model\Session
     */
    protected function getCheckoutSession()
    {
        return $this->_checkoutSession;
    }
}