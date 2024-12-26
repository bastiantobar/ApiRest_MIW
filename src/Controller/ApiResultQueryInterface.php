<?php

namespace App\Controller;
use Symfony\Component\HttpFoundation\{Request, Response};
interface ApiResultQueryInterface
{
    public final const RUTA_API = '/api/v1/results';
    public function getResults(): Response;

}