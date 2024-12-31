<?php

namespace App\Controller;

use App\Entity\Result;
use App\Utility\Utils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Attribute\Route;

class ApiResultQueryController extends AbstractController implements ApiResultQueryInterface
{
    private const HEADER_CACHE_CONTROL = 'Cache-Control';
    private const HEADER_ETAG = 'ETag';
    private const HEADER_ALLOW = 'Allow';
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }
    #[Route(
        path: "/api/v1/results.{_format}/{sort}",
        name: 'api_results_cget',
        requirements: [
            'sort' => "score|date",
            '_format' => "json|xml"
        ],
        defaults: [ '_format' => 'json', 'sort' => 'date' ],
        methods: [ Request::METHOD_GET ],
    )]
    public function cgetActionResult(Request $request): Response
    {
        $format = Utils::getFormat($request);
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return Utils::errorMessage(
                Response::HTTP_UNAUTHORIZED,
                '`Unauthorized`: Invalid credentials.',
                $format
            );
        }

        $order = strval($request->get('sort', 'date'));
        $allowedFields = ['score', 'date'];
        if (!in_array($order, $allowedFields)) {
            return Utils::errorMessage(
                Response::HTTP_BAD_REQUEST,
                '`Invalid sort field`: Allowed fields are `score`, `date`.',
                $format
            );
        }

        $results = $this->entityManager
            ->getRepository(Result::class)
            ->findBy([], [$order => 'ASC']);

        if (empty($results)) {
            return Utils::errorMessage(Response::HTTP_NOT_FOUND, 'No results found.', $format); // 404
        }

        $etag = md5((string) json_encode($results, JSON_THROW_ON_ERROR));
        if (($etags = $request->getETags()) && (in_array($etag, $etags) || in_array('*', $etags))) {
            return new Response(null, Response::HTTP_NOT_MODIFIED); // 304
        }

        return Utils::apiResponse(
            Response::HTTP_OK,
            ['results' => array_map(
                fn($result) => [
                    'id' => $result->getId(),
                    'score' => $result->getScore(),
                    'timestamp' => $result->getDate()->format('Y-m-d H:i:s'),
                    'user' => [
                        $result->getUser(),
                    ],
                    '_links' => [
                        'parent' => [
                            'href' => '/api/v1/results',
                        ],
                        'self' => [
                            'href' => '/api/v1/results/' . $result->getId(),
                        ],
                    ],
                ],
                $results
            )],
            $format,
            [
                self::HEADER_CACHE_CONTROL => 'private',
                self::HEADER_ETAG => $etag,
            ]
        );
    }

    #[Route(
        path: "/api/v1/results/{resultId}.{_format}",
        name: 'api_result_get',
        requirements: [
            "resultId" => "\d+",
            '_format' => "json|xml"
        ],
        defaults: [ '_format' => 'json' ],
        methods: [ Request::METHOD_GET ],
    )]
    public function getAction(Request $request, int $resultId): Response
    {
        $format = Utils::getFormat($request);
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return Utils::errorMessage( // 401
                Response::HTTP_UNAUTHORIZED,
                '`Unauthorized`: Invalid credentials.',
                $format
            );
        }

        /** @var $result $result */
        $result = $this->entityManager
            ->getRepository(Result::class)
            ->find($resultId);

        if (!$result instanceof Result) {
            return Utils::errorMessage(Response::HTTP_NOT_FOUND, null, $format);    // 404
        }

        // Caching with ETag (password included)
        $etag = md5(json_encode($result, JSON_THROW_ON_ERROR));
        if (($etags = $request->getETags()) && (in_array($etag, $etags) || in_array('*', $etags))) {
            return new Response(null, Response::HTTP_NOT_MODIFIED); // 304
        }

        return Utils::apiResponse(
            Response::HTTP_OK,
            [ Result::RESULT_ATTR => $result ],
            $format,
            [
                self::HEADER_CACHE_CONTROL => 'private',
                self::HEADER_ETAG => $etag,
            ]
        );
    }

    #[Route(
        path: "/api/v1/results/{resultId}.{_format}",
        name: 'optionsResult',
        requirements: [
            'resultId' => "\d+",
            '_format' => "json|xml"
        ],
        defaults: [ 'resultId' => 0, '_format' => 'json' ],
        methods: [ Request::METHOD_OPTIONS ],
    )]
    public function optionsActionResult(int|null $resultId): Response
    {
        $methods = $resultId !== 0
            ? [ Request::METHOD_GET, Request::METHOD_PUT, Request::METHOD_DELETE ]
            : [ Request::METHOD_GET, Request::METHOD_POST ];
        $methods[] = Request::METHOD_OPTIONS;

        return new Response(
            '',
            Response::HTTP_NO_CONTENT,
            [
                'Allow' => implode(', ', $methods), // Configuración correcta del encabezado Allow
                'Cache-Control' => 'public, immutable',
            ]
        );
    }

}
