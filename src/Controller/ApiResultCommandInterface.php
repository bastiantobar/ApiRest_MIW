<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface ApiResultCommandInterface
{
    public function handleRequest(Request $request): Response;
}
