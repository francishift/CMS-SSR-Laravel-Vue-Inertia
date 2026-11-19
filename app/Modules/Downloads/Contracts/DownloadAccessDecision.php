<?php

declare(strict_types=1);

namespace App\Modules\Downloads\Contracts;

/**
 * Objeto de valor inmutable que encapsula el resultado de evaluar el permiso de descarga.
 */
final readonly class DownloadAccessDecision
{
    public function __construct(
        public bool $allowed,
        public string $reasonCode,
        public string $message = '',
        public int $httpStatus = 200,
    ) {}

    public static function allow(string $reasonCode = 'granted'): self
    {
        return new self(true, $reasonCode, '', 200);
    }

    public static function deny(string $reasonCode, string $message, int $httpStatus = 403): self
    {
        return new self(false, $reasonCode, $message, $httpStatus);
    }
}
