<?php

namespace App\Controller;
use Symfony\Component\HttpFoundation\{Request, Response};
interface ApiResultQueryInterface
{
    public final const RUTA_API = '/api/v1/results';
    public function cgetActionResult(Request $request): Response;
    public function getAction(Request $request, int $resultId): Response;
    public function optionsActionResult(?int $resultId): Response;
}