<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface ApiResultCommandInterface
{
    public function postAction(Request $request): Response;

    public function putAction(Request $request, int $resultId): Response;

    public function deleteAction(Request $request, int $resultId): Response;
}
