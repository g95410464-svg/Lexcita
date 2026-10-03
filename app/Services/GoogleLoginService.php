<?php

namespace App\Services;

use Google\Client;
use GuzzleHttp\Client as HttpClient;
use RuntimeException;

class GoogleLoginService
{
    public function __construct(private Client $client) {}

    public function configured(): bool
    {
        return filled(config('google.client_id'))
            && filled(config('google.client_secret'))
            && filled(config('google.redirect_uri'));
    }

    private function configure(): void
    {
        if (!$this->configured()) {
            throw new RuntimeException('Google login is not configured.');
        }

        $this->client->setClientId(config('google.client_id'));
        $this->client->setClientSecret(config('google.client_secret'));
        $this->client->setRedirectUri(config('google.redirect_uri'));
        $this->client->setScopes(['openid', 'email', 'profile']);
        $this->client->setHttpClient(new HttpClient(['connect_timeout' => 5, 'timeout' => 15]));
    }

    public function authorizationUrl(string $state, string $nonce): string
    {
        $this->configure();
        $this->client->setState($state);
        return $this->client->createAuthUrl(null, ['nonce' => $nonce]);
    }

    public function identity(string $code, string $nonce): array
    {
        $this->configure();
        $tokens = $this->client->fetchAccessTokenWithAuthCode($code);
        if (isset($tokens['error']) || empty($tokens['id_token'])) {
            throw new RuntimeException('Google did not return an identity token.');
        }

        // Google's SDK verifies signature, issuer, audience and expiration.
        $claims = $this->client->verifyIdToken($tokens['id_token']);
        if (!is_array($claims)
            || ($claims['aud'] ?? null) !== config('google.client_id')
            || !is_numeric($claims['exp'] ?? null) || $claims['exp'] <= time()
            || !is_string($claims['nonce'] ?? null)
            || !hash_equals($nonce, $claims['nonce'])
            || ($claims['email_verified'] ?? false) !== true
            || !is_string($claims['sub'] ?? null)
            || $claims['sub'] === ''
            || !is_string($claims['email'] ?? null)
            || strlen($claims['email']) > 255
            || !filter_var($claims['email'], FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Google identity could not be verified.');
        }

        $email = mb_strtolower($claims['email']);
        // A verified third-party email can change owners independently of Google.
        // Without an explicit account-linking flow, require Gmail or Workspace.
        if (!str_ends_with($email, '@gmail.com') && !filled($claims['hd'] ?? null)) {
            throw new RuntimeException('Use password login for a third-party email account.');
        }

        return [
            'email' => $email,
            'nombre' => mb_substr(is_string($claims['name'] ?? null) && filled($claims['name']) ? $claims['name'] : $email, 0, 120),
        ];
    }
}
