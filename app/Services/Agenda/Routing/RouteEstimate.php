<?php

namespace App\Services\Agenda\Routing;

final readonly class RouteEstimate
{
    public function __construct(public int $seconds, public ?int $meters, public string $provider, public string $quality) {}
    public function minutes(): int { return (int) ceil($this->seconds / 60); }
}
