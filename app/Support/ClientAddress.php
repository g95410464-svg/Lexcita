<?php

namespace App\Support;

use Illuminate\Http\Request;

class ClientAddress
{
    public static function key(Request $request): string
    {
        // Do not trust client-supplied Cloudflare or X-Forwarded-For headers.
        // Railway supplies X-Real-IP, including the verified visitor behind Cloudflare.
        $ip = config('traffic.railway_ingress')
            ? $request->header('X-Real-IP')
            : $request->ip();

        if (!is_string($ip) || !filter_var($ip, FILTER_VALIDATE_IP)) {
            $ip = $request->server('REMOTE_ADDR');
        }

        // Canonicalize equivalent IPv6 spellings before deriving a counter key.
        return is_string($ip) && filter_var($ip, FILTER_VALIDATE_IP)
            ? bin2hex(inet_pton($ip))
            : 'unknown';
    }
}
