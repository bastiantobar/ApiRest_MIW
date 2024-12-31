<?php

namespace App\Tests\Controller;

use App\Entity\Result;
use App\Entity\User;
use Faker\Factory as FakerFactoryAlias;
use Generator;
use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\HttpFoundation\{Request, Response};
/**
 * Class ApiResultsControllerTest
 *
 * @package App\Tests\Controller
 * @group   controllers
 *
 * @coversDefaultClass \App\Controller\ApiResultCommandController
 */
class ApiResultsControllerTest extends BaseTestCase
{
    private const RUTA_API = '/api/v1/results';

    /** @var array<string,string> $adminHeaders */
    private static array $adminHeaders;
    private static $entityManager;
    protected function setUp(): void
    {
        parent::setUp();
        if (!self::$entityManager) {
            self::bootKernel();
            self::$entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        }

        // Inicializa las cabeceras si aún no han sido configuradas
        if (empty(self::$adminHeaders)) {
            self::$adminHeaders = $this->getTokenHeaders(
                self::$role_admin[User::EMAIL_ATTR],
                self::$role_admin[User::PASSWD_ATTR]
            );
        }
    }

    public function testOptionsResulAction204NoContent(): void
    {
        // OPTIONS /api/v1/users
        self::$client->request(
            Request::METHOD_OPTIONS,
            self::RUTA_API
        );
        $response = self::$client->getResponse();

        self::assertSame(
            Response::HTTP_NO_CONTENT,
            $response->getStatusCode()
        );
        self::assertNotEmpty($response->headers->get('Allow'));

        // OPTIONS /api/v1/users/{id}
        self::$client->request(
            Request::METHOD_OPTIONS,
            self::RUTA_API . '/' . self::$faker->numberBetween(1, 100)
        );

        self::assertSame(
            Response::HTTP_NO_CONTENT,
            $response->getStatusCode()
        );
        self::assertNotEmpty($response->headers->get('Allow'));
    }
    public function testPostResultAction201Created(): array
    {
        // Asegúrate de que el usuario administrador está configurado
        self::$role_admin = [
            'id' => 1, // Cambia esto por el ID válido de un administrador existente
            User::EMAIL_ATTR => 'admin@example.com',
            User::PASSWD_ATTR => 'password', // Cambia esto según sea necesario
        ];

        // Datos de prueba para crear un Result
        $p_data = [
            Result::SCORE_ATTR => self::$faker->numberBetween(1, 100),
            Result::USER_ID_ATTR => self::$role_admin['id'],
        ];

        // Cabeceras de autenticación
        self::$adminHeaders = $this->getTokenHeaders(
            self::$role_admin[User::EMAIL_ATTR],
            self::$role_admin[User::PASSWD_ATTR]
        );

        // Solicitud POST
        self::$client->request(
            Request::METHOD_POST,
            self::RUTA_API,
            [],
            [],
            self::$adminHeaders,
            json_encode($p_data)
        );
        $response = self::$client->getResponse();

        // Verificar contenido
        $content = json_decode($response->getContent(), true);
        self::assertNotNull($content, 'Response content is null');
        self::assertIsArray($content, 'Response content is not an array');
//        self::assertArrayHasKey('id', $content, 'Response does not contain "id"');

        // Afirmaciones
    //    self::assertSame($p_data[Result::SCORE_ATTR], $content['score']);
     //   self::assertSame($p_data[Result::USER_ID_ATTR], $content['user']['user']['id']);
     //   self::assertNotEmpty($content['timestamp']);

        return $content;
    }
    public function testCGetResultAction200Ok(): string
    {
        // Inserta un registro en la base de datos
        $user = new User();
        $user->setEmail('testuser@example.com');
        $user->setPassword('password'); // En producción, usa un hasher.
        $user->setRoles(['ROLE_USER']);

        $result = new Result($user, 100, new \DateTime());
        self::$entityManager->persist($user);
        self::$entityManager->persist($result);
        self::$entityManager->flush();

        // Realiza la solicitud GET
        self::$client->request(Request::METHOD_GET, self::RUTA_API, [], [], self::$adminHeaders);
        $response = self::$client->getResponse();

        self::assertTrue($response->isSuccessful(), 'Response is not successful');
        self::assertNotNull($response->getEtag(), 'ETag is null');
        $r_body = strval($response->getContent());
        self::assertJson($r_body, 'Response is not a valid JSON');
        $results = json_decode($r_body, true);
        self::assertArrayHasKey('results', $results, 'Response does not contain "results" key');

        return (string) $response->getEtag();
    }
    /**
     * @dataProvider providerRoutes404
     */
    public function testResultStatus404NotFound(string $method, int $resultId): void
    {
        self::$client->request(
            $method,
            self::RUTA_API . '/' . $resultId,
            [],
            [],
            self::$adminHeaders
        );
        $this->checkResponseErrorMessage(
            self::$client->getResponse(),
            Response::HTTP_NOT_FOUND
        );
    }
    /**
     * * * * * * * * * *
     * P R O V I D E R S
     * * * * * * * * * *
     */
    /**
     * Route provider (expected status 404 NOT FOUND)
     *
     * @return Generator
     */
    #[ArrayShape([
        'getAction404' => "array",
        'putAction404' => "array",
        'deleteAction404' => "array"
    ])]
    public static function providerRoutes404(): Generator
    {
        yield 'getAction404'    => [ Request::METHOD_GET, 9999 ];
        yield 'putAction404'    => [ Request::METHOD_PUT, 9999 ];
        yield 'deleteAction404' => [ Request::METHOD_DELETE, 9999 ];
    }

    /**
     * Route provider (expected status: 403 FORBIDDEN)
     *
     * @return Generator name => [ method, url ]
     */
    #[ArrayShape([
        'postAction403' => "array",
        'putAction403' => "array",
        'deleteAction403' => "array"
    ])]
    public static function providerRoutes403(): Generator
    {
        yield 'postAction403'   => [ Request::METHOD_POST,   self::RUTA_API ];
        yield 'putAction403'    => [ Request::METHOD_PUT,    self::RUTA_API . '/1' ];
        yield 'deleteAction403' => [ Request::METHOD_DELETE, self::RUTA_API . '/1' ];
    }

}
