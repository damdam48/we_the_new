<?php

namespace App\Eventlistener;

use App\Event\StripeEvent;
use App\Entity\Order\Order;
use Psr\Log\LoggerInterface;
use App\Entity\Order\Payment;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\Order\OrderRepository;
use App\Repository\Order\PaymentRepository;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: 'payment_intent.succeeded', method: 'onPaymentSucceed')]
#[AsEventListener(event: 'payment_intent.payment_failed', method: 'onPaymentFailled')]
class StripeEventListener
{

    public function __construct(
        private EntityManagerInterface $em,
        private OrderRepository $orderRepository,
        private PaymentRepository $paymentRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function onPaymentSucceed(StripeEvent $event): void
    {
        $resource = $event->getResource();

        if (!$resource) {
            throw new \InvalidArgumentException('The event resource is missing.');
        }

        $payment = $this->paymentRepository->find($resource->metadata->payment_id);
        $order = $this->orderRepository->find($resource->metadata->order_id);

        if (!$payment || !$order) {
            throw new \InvalidArgumentException('the payment or order is missing.');
        }

        // on met a jour le payment et la commande
        $payment->setStatus(Payment::STATUS_PAID);
        $order->setStatus(Order::STATUS_SHIPPING);

        $this->em->persist($payment);
        $this->em->persist($order);
        $this->em->flush();
    }

    public function onPaymentFailled(StripeEvent $event): void
    {
        $resource = $event->getResource();

        if (!$resource) {
            throw new \InvalidArgumentException('The event resource is missing.');
        }

        $payment = $this->paymentRepository->find($resource->metadata->payment_id);
        $order = $this->orderRepository->find($resource->metadata->order_id);

        if (!$payment || !$order) {
            throw new \InvalidArgumentException('the payment or order is missing.');
        }

        $order->setStatus(Order::STATUS_AWAITING_PAYMENT);
        $payment->setStatus(Payment::STATUS_FAILED);

        $this->em->persist($payment);
        $this->em->persist($order);
        $this->em->flush();
    }
}
