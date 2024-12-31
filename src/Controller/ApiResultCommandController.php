<?php

namespace App\Controller;

use App\Entity\Result;
use App\Entity\User;
use App\Utility\Utils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Class ApiResultCommandController
 *
 * @package App\Controller
 */
#[Route(
    path: ApiResultQueryInterface::RUTA_API,
    name: 'api_results_'
)]
class ApiResultCommandController extends AbstractController implements ApiResultCommandInterface
{

    private const ROLE_ADMIN = 'ROLE_ADMIN';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    #[Route(
        path: "/{resultId}.{_format}",
        name: 'delete',
        requirements: [
            'resulId' => "\d+",
            '_format' => "json|xml"
        ],
        defaults: [ '_format' => null ],
        methods: [Request::METHOD_DELETE],
    )]
    public function deleteAction(Request $request, int $resultId): Response
    {
        $format = Utils::getFormat($request);
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return Utils::errorMessage( // 401
                Response::HTTP_UNAUTHORIZED,
                '`Unauthorized`: Invalid credentials.',
                $format
            );
        }
        if (!$this->isGranted(self::ROLE_ADMIN)) {
            return Utils::errorMessage( // 403
                Response::HTTP_FORBIDDEN,
                '`Forbidden`: you don\'t have permission to access',
                $format
            );
        }

        /** @var Result $result */
        $result = $this->entityManager
            ->getRepository(Result::class)
            ->find($resultId);

        if (!$result instanceof Result) {   // 404 - Not Found
            return Utils::errorMessage(Response::HTTP_NOT_FOUND, null, $format);
        }

        $this->entityManager->remove($result);
        $this->entityManager->flush();

        return Utils::apiResponse(Response::HTTP_NO_CONTENT);
    }

    #[Route(
        path: ".{_format}",
        name: 'post',
        requirements: [
            '_format' => "json|xml"
        ],
        defaults: [ '_format' => null ],
        methods: [Request::METHOD_POST],
    )]
    public function postAction(Request $request): Response
    {
        $format = Utils::getFormat($request);
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return Utils::errorMessage(
                Response::HTTP_UNAUTHORIZED,
                '`Unauthorized`: Invalid credentials.',
                $format
            );
        }
        if (!$this->isGranted(self::ROLE_ADMIN)) {
            return Utils::errorMessage(
                Response::HTTP_FORBIDDEN,
                '`Forbidden`: You don\'t have permission to access.',
                $format
            );
        }

        $body = $request->getContent();
        $postData = json_decode((string) $body, true, 512, JSON_THROW_ON_ERROR);

        if (!isset($postData[Result::SCORE_ATTR], $postData[Result::USER_ID_ATTR])) {
            return Utils::errorMessage(Response::HTTP_UNPROCESSABLE_ENTITY, 'Missing required data.', $format);
        }

        $user = $this->entityManager
            ->getRepository(User::class)
            ->find($postData[Result::USER_ID_ATTR]);

        if (!$user) {
            return Utils::errorMessage(Response::HTTP_BAD_REQUEST, 'User not found.', $format);
        }

        $result = new Result(
            $user,
            intval($postData[Result::SCORE_ATTR]),
            new \DateTime()
        );

        $this->entityManager->persist($result);
        $this->entityManager->flush();
        $responseContent = [
            'id' => $result->getId(),
            'user' => [
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'roles' => $user->getRoles(),
                ],
                '_links' => [
                    'parent' => [
                        'href' => '/api/v1/users',
                    ],
                    'self' => [
                        'href' => '/api/v1/users/' . $user->getId(),
                    ],
                ],
            ],
            'score' => $result->getScore(),
            'timestamp' => $result->getDate()->format('Y-m-d\TH:i:s.u\Z'),
        ];
        return Utils::apiResponse(
            Response::HTTP_CREATED,
            $responseContent,
            $format,
            [
                'Location' => $request->getScheme() . '://' . $request->getHttpHost() .
                    '/api/results/' . $result->getId(),
            ]
        );
    }

    #[Route(
        path: "/{resultId}.{_format}",
        name: 'put',
        requirements: [
            'resultId' => "\d+",
            '_format' => "json|xml"
        ],
        defaults: [ '_format' => null ],
        methods: [Request::METHOD_PUT],
    )]
    public function putAction(Request $request, int $resultId): Response
    {
        $format = Utils::getFormat($request);

        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return Utils::errorMessage(
                Response::HTTP_UNAUTHORIZED,
                '`Unauthorized`: Invalid credentials.',
                $format
            );
        }

        /** @var User $currentUser */
        $currentUser = $this->getUser();
        $result = $this->entityManager
            ->getRepository(Result::class)
            ->find($resultId);

        if (!$result instanceof Result) { // 404 - Not Found
            return Utils::errorMessage(Response::HTTP_NOT_FOUND, 'Result not found.', $format);
        }

        if (
            ($result->getUser()->getId() !== $currentUser->getId())
            && !$this->isGranted(self::ROLE_ADMIN)
        ) {
            return Utils::errorMessage(
                Response::HTTP_FORBIDDEN,
                '`Forbidden`: you don\'t have permission to modify this result.',
                $format
            );
        }

        $body = (string) $request->getContent();
        try {
            $postData = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return Utils::errorMessage(
                Response::HTTP_BAD_REQUEST,
                '`Bad Request`: Invalid JSON format.',
                $format
            );
        }

        $etag = md5(json_encode([
            'id' => $result->getId(),
            'score' => $result->getScore(),
            'date' => $result->getDate()->format('Y-m-d\TH:i:s.u\Z'),
            'userId' => $result->getUser()->getId(),
        ], JSON_THROW_ON_ERROR));

        // Verificar la cabecera If-Match
      /*  if (!$request->headers->has('If-Match') || $etag !== $request->headers->get('If-Match')) {
            return Utils::errorMessage(
                Response::HTTP_PRECONDITION_FAILED,
                'PRECONDITION FAILED: one or more conditions given evaluated to false',
                $format
            );
        }*/

        if (isset($postData[Result::SCORE_ATTR])) {
            $result->setScore((int) $postData[Result::SCORE_ATTR]);
        }

        if (isset($postData[Result::DATE_ATTR])) {
            try {
                $date = new \DateTime($postData[Result::DATE_ATTR]);
                $result->setDate($date);
            } catch (\Exception $e) {
                return Utils::errorMessage(
                    Response::HTTP_BAD_REQUEST,
                    '`Bad Request`: Invalid date format.',
                    $format
                );
            }
        }

        $this->entityManager->flush();

        return Utils::apiResponse(
            209, // 209 - Content Returned
            [
                'id' => $result->getId(),
                'user' => [
                    'id' => $result->getUser()->getId(),
                    'email' => $result->getUser()->getEmail(),
                    'roles' => $result->getUser()->getRoles(),
                ],
                'score' => $result->getScore(),
                'timestamp' => $result->getDate()->format('Y-m-d\TH:i:s.u\Z'),
            ],
            $format,
            [
                'ETag' => md5(json_encode([
                    'id' => $result->getId(),
                    'score' => $result->getScore(),
                    'date' => $result->getDate()->format('Y-m-d\TH:i:s.u\Z'),
                    'userId' => $result->getUser()->getId(),
                ], JSON_THROW_ON_ERROR)),
            ]
        );
    }


}
