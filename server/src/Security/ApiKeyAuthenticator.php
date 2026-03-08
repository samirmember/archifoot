<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class ApiKeyAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $appEnv
    ) {
    }

    public function supports(Request $request): ?bool
    {
        if ($request->isMethod(Request::METHOD_OPTIONS)) {
            return false;
        }

        return str_starts_with($request->getPathInfo(), '/api');
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $path = $request->getPathInfo();

        if ($this->appEnv === 'dev' || str_starts_with($path, '/api/person-photo/')) {
            return new SelfValidatingPassport(
                new UserBadge('api-client', static fn () => new ApiKeyUser())
            );
        }

        $providedKey = $request->headers->get('X-API-KEY');

        if ($providedKey === null || $providedKey === '') {
            throw new CustomUserMessageAuthenticationException('Missing API key.');
        }

        if (!hash_equals($this->apiKey, $providedKey)) {
            throw new CustomUserMessageAuthenticationException('Invalid API key.');
        }

        return new SelfValidatingPassport(
            new UserBadge('api-client', static fn () => new ApiKeyUser())
        );
    }

    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName
    ): ?Response {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(
            ['message' => $exception->getMessageKey()],
            Response::HTTP_UNAUTHORIZED
        );
    }
}
