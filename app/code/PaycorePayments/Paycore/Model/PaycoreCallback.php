<?php

/**
 * PayCore.io Extension for Magento 2
 *
 * @author     PayCore.io https://paycore.io
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 */

namespace PaycorePayments\Paycore\Model;

use Magento\Sales\Model\Order;
use PaycorePayments\Paycore\Sdk\Paycore;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\Transaction;
use PaycorePayments\Paycore\Helper\Data as Helper;
use Magento\Framework\App\RequestInterface;
use PaycorePayments\Paycore\Api\PaycoreCallbackInterface;


class PaycoreCallback implements PaycoreCallbackInterface
{
    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $_order;

    /**
     * @var Paycore
     */
    protected $_paycore;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $_orderRepository;

    /**
     * @var \Magento\Sales\Model\Service\InvoiceService
     */
    protected $_invoiceService;

    /**
     * @var \Magento\Framework\DB\Transaction
     */
    protected $_transaction;

    /**
     * @var Helper
     */
    protected $_helper;

    /**
     * @var RequestInterface
     */
    protected $_request;

    public function __construct(
        Order $order,
        OrderRepositoryInterface $orderRepository,
        InvoiceService $invoiceService,
        Transaction $transaction,
        Helper $helper,
        Paycore $paycore,
        RequestInterface $request
    )
    {
        $this->_order = $order;
        $this->_paycore = $paycore;
        $this->_orderRepository = $orderRepository;
        $this->_invoiceService = $invoiceService;
        $this->_transaction = $transaction;
        $this->_helper = $helper;
        $this->_request = $request;
    }

    public function callback()
    {
        $body = json_decode(file_get_contents('php://input', 'rb'), true);

        if (!(isset($body['data']) && isset($body['signature']))) {
            $this->_helper->getLogger()->error(__('In the response from PayCore.io server there are no POST parameters "data" and "signature"'));
            return null;
        }

        $data = $body['data'];
        $receivedSignature = $body['signature'];

        $decodedData = $this->_paycore->getDecodedData($data);
        $status = $decodedData['state'];
        $orderId = $decodedData['reference'];

        try {
            $order = $this->_order->loadByIncrementId($orderId);;
            if (!($order && $order->getId() && $this->_helper->checkOrderIsPaycorePayment($order))) {
                return null;
            }

            // DON'T delete this block, be careful of fraud!!!
            if (!$this->_helper->securityOrderCheck($data, $receivedSignature)) {
                $order->addStatusHistoryComment(__('PayCore security check failed!'));
                $this->_orderRepository->save($order);

                return null;
            }

            // Ignore IPN for successfully paid orders
            if ($status !== Paycore::STATUS_SUCCESS && $order->getState() === Order::STATE_PROCESSING) {
                return null;
            }

            $historyMessage = [];
            $state = null;
            switch ($status) {
                case Paycore::STATUS_CREATED:
                case Paycore::STATUS_PENDING:
                    $state = Order::STATE_PENDING_PAYMENT;
                    $historyMessage[] = __('PayCore.io payment pending.');
                    break;
                case Paycore::STATUS_SUCCESS:
                    if ($order->canInvoice()) {
                        $invoice = $this->_invoiceService->prepareInvoice($order);
                        $invoice->register()->pay();
                        $transactionSave = $this->_transaction->addObject(
                            $invoice
                        )->addObject(
                            $invoice->getOrder()
                        );
                        $transactionSave->save();
                        $historyMessage[] = __('Invoice #%1 created.', $invoice->getIncrementId());
                        $state = Order::STATE_PROCESSING;
                    } else {
                        $historyMessage[] = __('Error during creation of invoice.');
                    }
                    break;
                case Paycore::STATUS_EXPIRED:
                    $state = Order::STATE_CANCELED;
                    $historyMessage[] = __('PayCore.io: payment expired.');
                    break;
                case Paycore::STATUS_FAILURE:
                    $state = Order::STATE_CANCELED;
                    $historyMessage[] = __('PayCore.io: payment failure.');
                    break;
                case Paycore::STATUS_CANCELED:
                    $state = Order::STATE_CANCELED;
                    $historyMessage[] = __('PayCore.io: payment canceled.');
                    break;
                default:
                    $historyMessage[] = __('Unexpected status from PayCore.io server: %1', $status);
                    break;
            }
            if (count($historyMessage)) {
                $order->addStatusHistoryComment(implode(' ', $historyMessage))
                    ->setIsCustomerNotified(true);
            }
            if ($state) {
                $order->setState($state);
                $order->setStatus($state);
                $order->save();
            }
            $this->_orderRepository->save($order);
        } catch (\Exception $e) {
            $this->_helper->getLogger()->critical($e);
        }
        return null;
    }
}