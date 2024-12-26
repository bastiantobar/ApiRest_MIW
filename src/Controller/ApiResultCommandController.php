<?php

namespace App\Controller;

use App\Entity\Result;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Attribute\Route;

/**
 * Class ApiResultCommandController
 *
 * @package App\Controller
 */
#[Route(
    path: "/api/results",
    name: "api_results_"
)]
class ApiResultCommandController extends AbstractController implements ApiResultCommandInterface
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function handleRequest(Request $request): Response
    {
        // Manejar la lógica del controlador aquí
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['user_id'], $data['score'])) {
            return new Response('Invalid input', Response::HTTP_BAD_REQUEST);
        }

        $user = $this->entityManager->getRepository(User::class)->find($data['user_id']);

        if (!$user) {
            return new Response('User not found', Response::HTTP_NOT_FOUND);
        }

        $result = new Result($user, $data['score'], new \DateTime());
        $this->entityManager->persist($result);
        $this->entityManager->flush();

        return new Response('Result created', Response::HTTP_CREATED);
    }
}
