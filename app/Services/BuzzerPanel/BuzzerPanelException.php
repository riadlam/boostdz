<?php

namespace App\Services\BuzzerPanel;

use RuntimeException;

class BuzzerPanelException extends RuntimeException
{
    /**
     * @param  array<string, mixed>|null  $response
     */
    public function __construct(string $message, protected ?array $response = null, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function response(): ?array
    {
        return $this->response;
    }
}
