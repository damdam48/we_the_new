<?php

namespace App\Factory;

use Stripe\Event;
use Stripe\Stripe;
use Stripe\Webhook;
use App\Event\StripeEvent;
use App\Entity\Order\Order;
use Stripe\Checkout\Session;
use Webmozart\Assert\Assert;
use App\Entity\Order\OrderItem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Stripe\Exception\SignatureVerificationException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class StripeFactory
{
    public function __construct(
        private string $stripeSecretKey,
        private string $webhookSecret,
        private EventDispatcherInterface $eventDispatcher,
    ) {
        Stripe::setApiKey($stripeSecretKey);
        Stripe::setApiVersion('2024-04-10');
    }

    /**
     * Stripe pour la page de paiement
     * @return  Session
     */
    public function createSession(Order $order, string $successUrl, string $cancelUrl): Session
    {
        Assert::notEmpty($order->getPayments(), 'You must have at least one payment to create a Stripe session.');

        return Session::create([
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'customer_email' => $order->getUser()->getEmail(),
            'line_items' => array_map(function (OrderItem $orderItem): array {
                return [
                    'quantity' => $orderItem->getQuantity(),
                    'price_data' => [
                        'currency' => 'EUR',
                        'product_data' => [
                            'name' => $orderItem->getQuantity() . ' x ' . $orderItem->getProductVariant()->getProduct()->getName(),
                        ],

                        'unit_amount' => bcmul($orderItem->getPriceTTC() / $orderItem->getQuantity(), 100),
                    ],
                ];
            }, $order->getOrderItems()->toArray()),
            'shipping_options' => [
                [
                    'shipping_rate_data' => [
                        'type' => 'fixed_amount',
                        'fixed_amount' => [
                            'currency' => 'EUR',
                            'amount' => $order->getShippings()->Last()->getDelivery()->getPrice() * 100,
                        ],
                        'display_name' => $order->getShippings()->Last()->getDelivery()->getName(),
                    ],
                ],
            ],

            'payment_intent_data' => [
                'metadata' => [
                    'order_id' => $order->getId(),
                    'payment_id' => $order->getPayments()->last()->getId(),
                ],
            ],

        ]);
    }
    /**
     * permet d'analyser la requete stripe et de retourner l'évenement correspondant
     *
     * @param string $signature;
     * @param mixed $body;
     * @return JsonResponse;
     */
    public function handleStripeRequest(string $signature, mixed $body): JsonResponse
    {
        if (!$body) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Missing body content',
            ], 404);
        }

        $event = $this->getEvent($signature, $body);

        if($event instanceof JsonResponse){
            return $event;
        }

        $event = new StripeEvent($event);

        $this->eventDispatcher->dispatch($event, $event->getName());

        //TODO gestion des événements srtipe et persistence en bdd

        return new JsonResponse([
            'status' =>'success',
            'message' => 'Event received and processed successfully'
        ]);
    }

    /**
     * permet de decoder la requete stripe et de retourner d'évenement correspondant
     *
     * @param string $signature;
     * @param mixed $body;
     * @return Event|JsonResponse;
     */
    private function getEvent(string $signature, mixed $body): Event|JsonResponse
    {
        try {
            $event = Webhook::constructEvent($body, $signature, $this->webhookSecret);
        } catch (\UnexpectedValueException $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $e->getCode());
        } catch (SignatureVerificationException $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $e->getCode());
        }
        return $event;
    }
}
