<?php

declare(strict_types=1);

namespace App\Adapters\Inbound\Http\Controllers;

use App\Application\Services\LoginService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AuthController
{
    public function __construct(private readonly LoginService $login)
    {
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        return response()->json($this->login->login($data['username'], $data['password']));
    }
}
