<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: "LOGISTIK UNIKOM API",
    version: "1.0.0"
)]

#[OA\Server(
    url: "http://perlengkapan.codelabspace.or.id/"
)]

#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    scheme: "bearer"
)]

class OpenApi {}
