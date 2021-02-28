<?php

namespace RicardoMartins\PagSeguro\Observer;

use Magento\Backend\Model\Auth\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order\Status\HistoryFactory;
use Magento\Sales\Api\OrderRepositoryInterface;

class CreditMemoObserver implements ObserverInterface
{
    protected $_order;
    protected $orderHistoryFactory;
    protected $orderRepository;
    protected $authSession;

    public function __construct(
        OrderRepositoryInterface $order,
        HistoryFactory $orderHistoryFactory,
        OrderRepositoryInterface $orderRepository,
        Session $authSession
    ) {
        $this->_order = $order;
        $this->orderHistoryFactory = $orderHistoryFactory;
        $this->orderRepository = $orderRepository;
        $this->authSession = $authSession;
    }

    public function execute(Observer $observer): CreditMemoObserver
    {
        $refund = $observer->getEvent()->getCreditmemo();
        $_order = $refund->getOrder();
        $payment = $_order->getPayment();
        $method = $payment->getMethod();

        if (stristr($method, "rm_pagseguro")) {
            $orderId = $_order->getId();
            $order = $this->orderRepository->get($orderId);
            $value = $refund->getGrandTotal();
            $user = $this->authSession->getUser();
            $user->getName();
            $user->getId();
            $comment = "Reembolso de R$" . number_format($value, 2, ",", ".")
                . " feito por " . $user->getName() . " (" . $user->getUserName() . ")";
            $this->orderHistoryFactory->create();
            $history = $this->orderHistoryFactory->create()
                ->setStatus($order->getStatus())
                ->setEntityName(\Magento\Sales\Model\Order::ENTITY)
                ->setComment($comment);

            $history->setIsCustomerNotified(false)
            ->setIsVisibleOnFront(false);

            $order->addStatusHistory($history);
            $this->orderRepository->save($order);
        }
        return $this;
    }
}
