<?php

namespace Pledg\PledgPaymentGateway\Controller\Checkout;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\OrderFactory;
use Magento\Store\Model\ScopeInterface;
use Pledg\PledgPaymentGateway\Helper\Config;
use Pledg\PledgPaymentGateway\Helper\Crypto;
use Pledg\PledgPaymentGateway\Model\Ui\ConfigProvider;
use Psr\Log\LoggerInterface;

class Ipn extends Action
{
    const MODE_TRANSFER = 'transfer';
    const MODE_BACK = 'back';

    const STATUS_PENDING = [
        "waiting",
        "pending",
        "authorized",
        "pending-capture",
        "in-review",
        "retrieval-request",
        "fraud-notification",
        "chargeback-initiated",
        "solved",
        "reversed"
    ];
    const STATUS_CANCELLED = [
        "failed",
        "voided",
        "refunded",
        "pending-capture",
        "blocked"
    ];
    const STATUS_COMPLETED = [
        "completed"
    ];

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var Crypto
     */
    private $cryptoHelper;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var OrderSender
     */
    private $orderSender;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Context              $context
     * @param FormKey              $formKey
     * @param OrderFactory         $orderFactory
     * @param Crypto               $cryptoHelper
     * @param ScopeConfigInterface $scopeConfig
     * @param OrderSender          $orderSender
     * @param LoggerInterface      $logger
     */
    public function __construct(
        Context $context,
        FormKey $formKey,
        OrderFactory $orderFactory,
        Crypto $cryptoHelper,
        ScopeConfigInterface $scopeConfig,
        OrderSender $orderSender,
        LoggerInterface $logger
    ) {
        parent::__construct($context);

        $this->orderFactory = $orderFactory;
        $this->cryptoHelper = $cryptoHelper;
        $this->scopeConfig = $scopeConfig;
        $this->orderSender = $orderSender;
        $this->logger = $logger;

        $this->getRequest()->setParam('form_key', $formKey->getFormKey());
    }

    public function execute()
    {
        $params = json_decode($this->getRequest()->getContent(), true);

        $secretKey = $this->scopeConfig->getValue(
            sprintf('payment/%s/secret_key', $this->getRequest()->getParam('pledg_method')),
            ScopeInterface::SCOPE_STORES,
            (int)$this->getRequest()->getParam('ipn_store_id')
        ) ?? '';

        $this->logger->info('Received IPN', ['params' => $params]);

        try {
            if (isset($params['signature'])) {
                if (count($params) === 1) {
                    $this->logger->info('Mode signed transfer');

                    $signature = $params['signature'];
                    $params = $this->cryptoHelper->decode($signature, $secretKey);
                    $this->logger->info('Decrypted message', ['params' => $params]);

                    $mode = self::MODE_TRANSFER;
                } else {
                    $this->logger->info('Mode signed back');

                    $paramsToValidate = [
                        'created_at',
                        'error',
                        'id',
                        'reference',
                        'sandbox',
                        'status',
                    ];

                    $stringToValidate = [];
                    foreach ($paramsToValidate as $param) {
                        $stringToValidate[] = $param . '=' . $params[$param] ?? '';
                    }
                    $stringToValidate = strtoupper(hash('sha256', implode($secretKey, $stringToValidate)));

                    if ($params['signature'] !== $stringToValidate) {
                        throw new \Exception('Invalid signature');
                    }
                    $mode = self::MODE_BACK;
                }
            } else {
                $this->logger->info('Mode unsigned transfer');
                $mode = self::MODE_TRANSFER;
            }

            $orderIncrementId = str_replace(Config::ORDER_REFERENCE_PREFIX, '', $params['reference']);
            $order = $this->orderFactory->create();
            $order->loadByIncrementId($orderIncrementId);

            if (!$order->getId()) {
                throw new \Exception(sprintf('Could not retrieve order with id %s', $orderIncrementId));
            }

            $paymentMethod = $order->getPayment()->getMethod();

            if (!in_array($paymentMethod, ConfigProvider::getPaymentMethodCodes())) {
                throw new \Exception(sprintf('Order with method %s should not be updated via Pledg notification', $paymentMethod));
            }

            $message = null;
            if ($mode === self::MODE_TRANSFER) {
                // In tranfer mode, notification is only sent when payment is validated
                $this->logger->info('Invoice order after receiving transfer notification');
                $this->invoiceOrder($order, $params['purchase_uid'] ?? '');
                $message = __('Received invoicing order from Pledg transfer notification');
            } else {
                $pledgStatus = $params['status'] ?? '';
                $this->logger->info('Payment status received with back mode : ' . $pledgStatus);
                if (in_array($pledgStatus, self::STATUS_COMPLETED)) {
                    $this->logger->info('Invoice order after receiving back notification');
                    $this->invoiceOrder($order, $params['id'] ?? '');
                    $message = __('Received invoicing order from Pledg back notification with status %1', $pledgStatus);
                } elseif (in_array($pledgStatus, self::STATUS_CANCELLED)) {
                    $this->logger->info('Cancel order after receiving back notification');
                    if (!$order->canCancel()) {
                        throw new \Exception(sprintf('Order %s cannot be canceled', $orderIncrementId));
                    }
                    $order->registerCancellation(__(
                        'Received cancellation order from Pledg back notification with status %1',
                        $pledgStatus
                    ))->save();
                } elseif (in_array($pledgStatus, self::STATUS_PENDING)) {
                    $this->logger->info('Received back notification with Pending status. Do nothing');
                    $message = __('Received Pledg back notification with status %1. Waiting for further instructions to update order.', $pledgStatus);
                } else {
                    $this->logger->error('Received unhandled status from Pledg back notification', ['status' => $pledgStatus]);
                }
            }

            if ($message !== null) {
                $order->addCommentToStatusHistory($message);
                $order->save();
            }

            /** @var Raw $response */
            $response = $this->resultFactory->create(ResultFactory::TYPE_RAW);
            $response->setContents('');

            return $response;
        } catch (\Exception $e) {
            $this->logger->error('An error occurred while processing IPN', [
                'exception' => $e,
            ]);

            /** @var Json $response */
            $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);
            $response->setHttpResponseCode(500);
            $response->setData(['exception' => $e->getMessage()]);

            return $response;
        }
    }

    /**
     * @param Order  $order
     * @param string $transactionId
     *
     * @throws LocalizedException
     * @throws \Exception
     */
    private function invoiceOrder(Order $order, string $transactionId): void
    {
        if (!$order->canInvoice() || $order->getState() !== Order::STATE_PENDING_PAYMENT) {
            throw new \Exception(sprintf('Order with state %s cannot be processed and invoiced', $order->getState()));
        }

        $invoice = $order->prepareInvoice();
        $invoice->register();
        $order->addRelatedObject($invoice);
        $invoice->setTransactionId($transactionId);

        $order->setState(Order::STATE_PROCESSING);
        $invoice->pay();
        $order->getPayment()->setBaseAmountPaidOnline($order->getBaseGrandTotal());
        $message = __('Registered update about approved payment.') . ' ' . __('Transaction ID: "%1"', $transactionId);
        $order->addStatusToHistory(
            $order->getConfig()->getStateDefaultStatus(Order::STATE_PROCESSING),
            $message
        );

        try {
            $this->orderSender->send($order);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
