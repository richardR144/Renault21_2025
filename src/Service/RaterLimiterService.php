<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactory;

class RateLimiterService
{
    public function __construct(
        private RateLimiterFactory $loginAttemptsLimiter,
        private RateLimiterFactory $registrationAttemptsLimiter
    ) {}

    public function checkLoginAttempts(Request $request): bool
    {
        $limiter = $this->loginAttemptsLimiter->create($this->getClientIdentifier($request));
        return $limiter->consume(1)->isAccepted();
    }

    public function checkRegistrationAttempts(Request $request): bool
    {
        $limiter = $this->registrationAttemptsLimiter->create($this->getClientIdentifier($request));
        return $limiter->consume(1)->isAccepted();
    }

    private function getClientIdentifier(Request $request): string
    {
        return $request->getClientIp() . '_' . $request->headers->get('User-Agent', 'unknown');
    }
}