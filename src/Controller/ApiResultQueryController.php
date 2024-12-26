<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class ApiResultQueryController extends AbstractController implements ApiResultQueryInterface
{
    #[Route('/api/v1/results', name: 'api_results', methods: ['GET'])]
    public function getResults(): JsonResponse
    {
        return $this->json(['message' => 'Results retrieved successfully']);
    }
}
