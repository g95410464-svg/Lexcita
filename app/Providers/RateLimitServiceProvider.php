<?php

namespace App\Providers;

use App\Support\ClientAddress;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class RateLimitServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $email = $request->input('email');
            $email = is_string($email) ? Str::lower(trim($email)) : '';
            $ip = ClientAddress::key($request);

            return [
                $this->minute('login_ip', 'ip:'.$ip),
                $this->minute('login_identity', 'identity:'.hash('sha256', $ip.'|'.$email)),
            ];
        });

        RateLimiter::for('register', fn (Request $request) => [
            $this->minute('register_minute', 'minute:'.ClientAddress::key($request)),
            $this->response(Limit::perHour($this->maximum('register_hour'))
                ->by('hour:'.ClientAddress::key($request))),
        ]);

        RateLimiter::for('oauth', fn (Request $request) =>
            $this->minute('oauth', 'ip:'.ClientAddress::key($request)));

        // Independent budgets prevent ICE candidates from exhausting booking or login limits.
        foreach (['portal', 'slots', 'booking', 'payments', 'writes', 'video', 'broadcast'] as $name) {
            RateLimiter::for($name, fn (Request $request) => $this->minute($name,
                $request->user()
                    ? 'user:'.$request->user()->getAuthIdentifier()
                    : 'ip:'.ClientAddress::key($request)));
        }
    }

    private function maximum(string $name): int
    {
        return max(1, (int) config('traffic.limits.'.$name));
    }

    private function minute(string $name, string $key): Limit
    {
        return $this->response(Limit::perMinute($this->maximum($name))->by($key));
    }

    private function response(Limit $limit): Limit
    {
        return $limit->response(function (Request $request, array $headers) {
            $seconds = max(1, (int) ($headers['Retry-After'] ?? 60));
            $headers['Cache-Control'] = 'private, no-store';
            $message = "Demasiadas solicitudes. Espera {$seconds} segundos e intenta de nuevo.";

            return $request->expectsJson()
                ? response()->json(['message' => $message, 'retry_after' => $seconds], 429, $headers)
                : response()->view('errors.429', compact('message'), 429, $headers);
        });
    }
}
