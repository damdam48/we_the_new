<?php

namespace App\Controller\Api;


use App\Form\GenderType;
use App\Entity\Product\Gender;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\Product\GenderRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Controller\Api\Traits\ApiControllerTraits;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api/gender', name: 'api.gender')]
class GenderApiController extends AbstractController
{
    use ApiControllerTraits;

    public function __construct(
        private GenderRepository $genderRepository,
        private EntityManagerInterface $em,
    ) {
    }
//index
    #[Route('', name: '.index', methods: ['GET'])]
    public function index(): JsonResponse
    {

        return $this->json(
            $this->genderRepository->findAll(),
            200,
            [],
            [
                'groups' => ['gender:read', 'read']
            ]
        );
    }
//show
    #[Route('/{id}', name: '.show', methods: ['GET'])]
    public function show(?Gender $gender): JsonResponse
    {
        if (!$gender) {
            return $this->json([
                'status' => 'Error',
                'message' => 'Gender not found',
            ], 404);
        }

        return $this->json(
            $gender,
            200,
            [],
            [
                'groups' => ['gender:read', 'read', 'gender:show']
            ]
        );
    }
//create
    #[Route('/create', name: '.create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $gender = new Gender();

        $form = $this->createForm(GenderType::class, $gender, [
            'csrf_protection' => false,
        ]);

        $form->submit(json_decode($request->getContent(), true), false);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($gender);
            $this->em->flush();

            return $this->json(
                $gender,
                201,
                [],
                [
                    'groups' => ['gender:read', 'gender:show', 'show']
                ]
            );
        }

        return $this->getFormErrors($form);
    }
//put patch
    #[Route('/{id}', name: '.update', methods: ['PUT', 'PATCH'])]
    public function update(?Gender $gender, Request $request): JsonResponse
    {
        if (!$gender) {
            return $this->json([
                'status' => 'Error',
                'message' => 'Gender not found',
            ], 404);
        }

        $form = $this->createForm(GenderType::class, $gender, [
            'csrf_protection' => false,
        ]);

        $form->submit(json_decode($request->getContent(), true), false);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($gender);
            $this->em->flush();

            return $this->json(
                $gender,
                200,
                [],
                [
                    'groups' => ['gender:read', 'gender:show', 'show']
                ]
            );
        }

        return $this->getFormErrors($form);
    }
//delete
    #[Route('/{id}', name: '.delete', methods: ['DELETE'])]
    public function delete(?Gender $gender): JsonResponse
    {
        if (!$gender) {
            return $this->json([
                'status' => 'Error',
                'message' => 'Gender not found',
            ], 404);
        }

        $this->em->remove($gender);
        $this->em->flush();

        return $this->json([
            'status' => 'Success',
            'message' => 'Gender deleted',
        ], 204);
    }
}
