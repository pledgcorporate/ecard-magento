<?php

namespace Pledg\PledgPaymentGateway\Controller\Checkout;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Pledg\PledgPaymentGateway\Model\Ui\ConfigProvider;
use Psr\Log\LoggerInterface;

class Pay extends Action
{
    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var PageFactory
     */
    private $pageFactory;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Context         $context
     * @param Session         $checkoutSession
     * @param OrderFactory    $orderFactory
     * @param PageFactory     $pageFactory
     * @param Registry        $registry
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        OrderFactory $orderFactory,
        PageFactory $pageFactory,
        Registry $registry,
        LoggerInterface $logger
    ) {
        parent::__construct($context);

        $this->checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
        $this->pageFactory = $pageFactory;
        $this->registry = $registry;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        try {
            $order = $this->getLastOrder();
            $paymentMethod = $order->getPayment()->getMethod();

            if (!in_array($paymentMethod, ConfigProvider::getPaymentMethodCodes())) {
                throw new \Exception(sprintf('Order with method %s wrongfully accessed Pledg payment page', $paymentMethod));
            }

            if ($order->getState() !== Order::STATE_PENDING_PAYMENT) {
                throw new \Exception(sprintf('Order with state %s wrongfully accessed Pledg payment page', $order->getState()));
            }

            $apiKeyMapping = $order->getPayment()->getMethodInstance()->getConfigData('api_key_mapping', $order->getStoreId());
            $apiKeyMapping = json_decode($apiKeyMapping, true);
            $merchantApiKey = null;
            foreach ($apiKeyMapping as $mapping) {
                if ($mapping['country'] === $order->getBillingAddress()->getCountryId()) {
                    $merchantApiKey = $mapping['api_key'];
                }
            }

            if ($merchantApiKey === null) {
                throw new \Exception(sprintf(
                    'Could not retrieve api key for country %s on order %s',
                    $order->getBillingAddress()->getCountryId(),
                    $order->getIncrementId()
                ));
            }

            $this->registry->register('pledg_order', $order);
            $this->registry->register('pledg_merchant_id', $merchantApiKey);

            $title = __('Pay with Pledg');
            $page = $this->pageFactory->create();
            $page->getConfig()->getTitle()->set($title);

            $pageMainTitle = $page->getLayout()->getBlock('page.main.title');
            if ($pageMainTitle) {
                $pageMainTitle->setPageTitle($title);
            }

            return $page;
        } catch (\Exception $e) {
            $this->logger->error('An error occurred on pledg payment page', [
                'exception' => $e,
            ]);

            $this->messageManager->addErrorMessage(
                __('An error occurred while processing your payment. Please try again.')
            );

            return $this->resultRedirectFactory->create()->setPath('checkout/cart');
        }
    }

    /**
     * @return Order
     *
     * @throws \Exception
     */
    private function getLastOrder()
    {
        $lastIncrementId = $this->checkoutSession->getLastRealOrderId();

        if (!$lastIncrementId) {
            throw new \Exception('Could not retrieve last order id');
        }
        $order = $this->orderFactory->create();
        $order->loadByIncrementId($lastIncrementId);

        if (!$order->getId()) {
            throw new \Exception(sprintf('Could not retrieve order with id %s', $lastIncrementId));
        }

        return $order;
    }
}
