<?php

namespace App\Controller\Frontend;

use App\Entity\User;
use App\Entity\Address;
use App\Form\AddressType;
use App\Manager\CartManager;
use App\Entity\Delivery\Shipping;
use App\Form\ShippingCheckoutType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/checkout', name: 'app.checkout')]
class CheckoutController extends AbstractController
{
    public function __construct(
        private CartManager $cartManager,
        private EntityManagerInterface $em,
    ) {
    }

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
}
