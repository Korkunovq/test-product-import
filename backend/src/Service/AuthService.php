<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly string $jwtSecret,
        private readonly int $jwtTtl,
    ) {
    }

    public function authenticate(string $email, string $password): ?string
    {
        $user = $this->userRepository->findByEmail($email);
        if ($user === null || !password_verify($password, $user->getPasswordHash())) {
            return null;
        }

        return $this->issueToken($user);
    }

    public function issueToken(User $user): string
    {
        $now = time();

        return JWT::encode([
            'sub' => $user->getId(),
            'email' => $user->getEmail(),
            'iat' => $now,
            'exp' => $now + $this->jwtTtl,
        ], $this->jwtSecret, 'HS256');
    }

    /** @return array{sub: int, email: string}|null */
    public function validateToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));

            return [
                'sub' => (int) $decoded->sub,
                'email' => (string) $decoded->email,
            ];
        } catch (\Throwable) {
            return null;
        }
    }
}
