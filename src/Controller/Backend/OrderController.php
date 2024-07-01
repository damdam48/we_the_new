<?php

namespace App\Controller\Backend;

use App\Repository\Order\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/orders', name: 'admin.orders')]

class OrderController extends AbstractController
{

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly orderRepository $orderRepository,
    ) {
    }
//index
    #[Route('', name: '.index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        return $this->render('Backend/Order/index.html.twig', [
            'orders' => $this->orderRepository->findAll(),

        ]);
    }



}


