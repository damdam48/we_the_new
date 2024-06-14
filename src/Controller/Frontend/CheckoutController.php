<?php

namespace App\Controller\Frontend;

use App\Entity\User;
use App\Entity\Address;
use App\Form\AddressType;
use App\Form\PaymentType;
use App\Manager\CartManager;
use App\Entity\Order\Payment;
use App\Factory\StripeFactory;
use App\Entity\Delivery\Shipping;
use App\Form\ShippingCheckoutType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/checkout', name: 'app.checkout')]
class CheckoutController extends AbstractController
{
    public function __construct(
        private CartManager $cartManager,
        private EntityManagerInterface $em,
    ) {
    }

    //address
    #[Route('/address', name: '.address', methods: ['GET', 'POST'])]
    public function address(Request $request): Response
    {
        $cart = $this->cartManager->getCurrentCart();

        if ($cart->getOrderItems()->isEmpty()) {
            $this->addFlash('danger', 'Votre panier est vide, veuillez ajouter des produits avant de continuer.');

            return $this->redirectToRoute('app.cart.show');
        }

        /** @var User $user */
        $user = $this->getUser();

        if ($user->getDefaultAddress()) {
            $address = clone $user->getDefaultAddress();
        } elseif (!$user->getAddresses()->isEmpty()) {
            $address = clone $user->getAddresses()->first();
        } else {
            $address = new Address();
        }

        $form = $this->createForm(AddressType::class, $address);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$user->hasAddress($address)) {
                $user->addAddress($address);

                $this->em->persist($address);
                $this->em->flush();
            }

            return $this->redirectToRoute('app.checkout.shipping');
        }

        return $this->render('Frontend/Checkout/address.html.twig', [
            'cart' => $cart,
            'form' => $form,
            'addresses' => $user->getAddresses(),
        ]);
    }

    //shipping
    #[Route('/shipping', name: '.shipping', methods: ['GET', 'POST'])]
    public function shipping(Request $request): Response
    {
        $cart = $this->cartManager->getCurrentCart();

        if ($cart->getOrderItems()->isEmpty()) {

            $this->addFlash('error', 'Vous n\'avez pas de commande en cours');

            return $this->redirectToRoute('app.cart.show');
        }
        if (!$cart->getShippings()->isEmpty()) {
            $shipping = $cart->getShippings()->last();
        } else {
            $shipping = (new Shipping)
                ->setStatus(Shipping::STATUS_NEW);
        }

        $form = $this->createForm(ShippingCheckoutType::class, $shipping);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $shipping
                ->setOrderRef($cart)
                ->setStatus(Shipping::STATUS_NEW);

            $this->em->persist($shipping);
            $this->em->flush();

            return $this->redirectToRoute('app.checkout.recap');
        }

        return $this->render('Frontend/Checkout/shipping.html.twig', [
            'cart' => $cart,
            'form' => $form,
        ]);
    }

    //recap
    #[Route('/recap', name: '.recap', methods: ['GET', 'POST'])]
    public function recap(Request $request, StripeFactory $stripeFactory): Response|RedirectResponse
    {
        $cart = $this->cartManager->getCurrentCart();

        if ($cart->getOrderItems()->isEmpty()) {
            $this->addFlash('danger', 'Votre panier est vide, veuillez ajouter des produits avant de continuer.');

            return $this->redirectToRoute('app.cart.show');
        }

        $payment = (new Payment)
            ->setStatus(Payment::STATUS_NEW)
            ->setUser($this->getUser())
            ->setOrderRef($cart);

        $form = $this->createForm(PaymentType::class, $payment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $payment->setStatus(Payment::STATUS_NEW);

            $this->em->persist($payment);
            $this->em->flush();

            $session = $stripeFactory->createSession
            ($cart,
            $this->generateUrl('app.checkout.success', [], UrlGeneratorInterface::ABSOLUTE_URL),
            $this->generateUrl('app.checkout.cancel', [], UrlGeneratorInterface::ABSOLUTE_URL),
        );


            return $this->redirect($session->url);
        }


        return $this->render('Frontend/Checkout/recap.html.twig', [
            'cart' => $cart,
            'form' => $form,
        ]);
    }

    //success
    #[Route('/success', name: '.success', methods: ['GET', 'POST'])]
    public function success(): RedirectResponse
    {
        $this->addFlash('success', 'Vote commande a bien été enregistrée.');
        return $this->redirectToRoute('app.home');
    }

    //cancel
    #[Route('/cancel', name: '.cancel', methods: ['GET'])]
    public function cancel(): RedirectResponse
    {
        $this->addFlash('danger', 'Vote paiment a été annulé.');
        return $this->redirectToRoute('app.home');
    }
}
