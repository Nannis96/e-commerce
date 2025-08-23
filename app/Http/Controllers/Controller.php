<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="E-Commerce API",
 *     version="1.0.0",
 *     description="API para el sistema de E-Commerce",
 *     @OA\Contact(
 *         email="admin@ecommerce.com"
 *     )
 * )
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="Servidor API E-Commerce"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Ingrese el token de autenticación en el formato: Bearer {token}"
 * )
 */
abstract class Controller
{
    //
}
