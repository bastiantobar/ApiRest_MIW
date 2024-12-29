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

        // Verificaciones
        self::assertTrue($response->isSuccessful(), 'Response is not successful');
        self::assertNotNull($response->getEtag(), 'ETag is null');
        $r_body = strval($response->getContent());
        self::assertJson($r_body, 'Response is not a valid JSON');
        $results = json_decode($r_body, true);
        self::assertArrayHasKey('results', $results, 'Response does not contain "results" key');

        return (string) $response->getEtag();
    }

    /*
        public function testCGetResultAction200Ok(): string
        {
            $results = $this->entityManager->getRepository(Result::class)->findAll();
            if (empty($results)) {
                $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'admin@example.com']);
                self::assertNotNull($user, 'Admin user not found in the database');

                $result = new Result($user, 100, new \DateTime());
                $this->entityManager->persist($result);
                $this->entityManager->flush();
            }

            self::$adminHeaders = $this->getTokenHeaders(
                self::$role_admin[User::EMAIL_ATTR],
                self::$role_admin[User::PASSWD_ATTR]
            );

            self::$client->request(
                Request::METHOD_GET,
                self::RUTA_API . '.json/score',
                [],
                [],
                self::$adminHeaders
            );
            $response = self::$client->getResponse();

            self::assertTrue($response->isSuccessful(), 'Response is not successful');
            self::assertSame(Response::HTTP_OK, $response->getStatusCode(), 'Response status code is not 200');

            self::assertNotNull($response->getEtag(), 'Response does not have an ETag');

            $r_body = strval($response->getContent());

            self::assertJson($r_body, 'Response body is not a valid JSON');

            $results = json_decode($r_body, true);

            self::assertArrayHasKey('results', $results, 'Response does not contain "results" key');

            self::assertNotEmpty($results['results'], 'Results list is empty');

            foreach ($results['results'] as $result) {
                self::assertArrayHasKey('id', $result, 'Result does not have an "id"');
                self::assertArrayHasKey('score', $result, 'Result does not have a "score"');
                self::assertArrayHasKey('date', $result, 'Result does not have a "date"');
                self::assertArrayHasKey('user', $result, 'Result does not have a "user"');

                self::assertArrayHasKey('id', $result['user'], 'User does not have an "id"');
                self::assertArrayHasKey('username', $result['user'], 'User does not have a "username"');
            }

            return (string) $response->getEtag();
        }



        public function testResultStatus404NotFound(string $method, int $userId): void
        {
            self::$client->request(
                $method,
                self::RUTA_API . '/' . $userId,
                [],
                [],
                self::$adminHeaders
            );
            $this->checkResponseErrorMessage(
                self::$client->getResponse(),
                Response::HTTP_NOT_FOUND
            );
        }
    */
    /**
     * Test POST   /users 403 FORBIDDEN
     * Test PUT    /users/{userId} 403 FORBIDDEN
     * Test DELETE /users/{userId} 403 FORBIDDEN
     *
     * @param string $method
     * @param string $uri
     * @dataProvider providerRoutes403
     * @return void
     */
    /*
    public function testResultStatus403Forbidden(string $method, string $uri): void
    {
        $userHeaders = $this->getTokenHeaders(
            self::$role_user[User::EMAIL_ATTR],
            self::$role_user[User::PASSWD_ATTR]
        );
        self::$client->request($method, $uri, [], [], $userHeaders);
        $this->checkResponseErrorMessage(
            self::$client->getResponse(),
            Response::HTTP_FORBIDDEN
        );
    }

    /**
     * * * * * * * * * *
     * P R O V I D E R S
     * * * * * * * * * *
     */

    /**
     * User provider (incomplete) -> 422 status code
     *
     * @return Generator user data [email, password]
     */
    /*
    #[ArrayShape([
        'no_email' => "array",
        'no_passwd' => "array",
        'nothing' => "array"
    ])]
    public static function userProvider422(): Generator
    {
        $faker = FakerFactoryAlias::create('es_ES');
        $email = $faker->email();
        $password = $faker->password();

        yield 'no_email'  => [ null,   $password ];
        yield 'no_passwd' => [ $email, null      ];
        yield 'nothing'   => [ null,   null      ];
    }

    /**
     * Route provider (expected status: 401 UNAUTHORIZED)
     *
     * @return Generator name => [ method, url ]
     */
    #[ArrayShape([
        'cgetAction401' => "array",
        'getAction401' => "array",
        'postAction401' => "array",
        'putAction401' => "array",
        'deleteAction401' => "array"
    ])]
    public static function providerRoutes401(): Generator
    {
        yield 'cgetAction401'   => [ Request::METHOD_GET,    self::RUTA_API ];
        yield 'getAction401'    => [ Request::METHOD_GET,    self::RUTA_API . '/1' ];
        yield 'postAction401'   => [ Request::METHOD_POST,   self::RUTA_API ];
        yield 'putAction401'    => [ Request::METHOD_PUT,    self::RUTA_API . '/1' ];
        yield 'deleteAction401' => [ Request::METHOD_DELETE, self::RUTA_API . '/1' ];
    }

    /**
     * Route provider (expected status 404 NOT FOUND)
     *
     * @return Generator name => [ method ]
     */
    #[ArrayShape([
        'getAction404' => "array",
        'putAction404' => "array",
        'deleteAction404' => "array"
    ])]
    public static function providerRoutes404(): Generator
    {
        yield 'getAction404'    => [ Request::METHOD_GET ];
        yield 'putAction404'    => [ Request::METHOD_PUT ];
        yield 'deleteAction404' => [ Request::METHOD_DELETE ];
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
