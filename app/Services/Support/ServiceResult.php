<?php

namespace App\Services\Support;

final readonly class ServiceResult
{
    private function __construct(
        public bool $successful,
        public array $data = [],
        public int $status = 200,
        public ?string $message = null,
        public array|string|null $errors = null,
    ) {}

    public static function success(array $data = [], int $status = 200, ?string $message = null): self
    {
        return new self(true, $data, $status, $message);
    }

    public static function failure(array|string $errors, int $status = 400, ?string $message = null): self
    {
        return new self(false, [], $status, $message, $errors);
    }
}
