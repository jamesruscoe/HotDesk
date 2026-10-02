<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class PriceQuoteDto
{
    private function __construct(
        public int $amountCents,
        public ?string $currency,
    ) {}

    public static function free(): self
    {
        return new self(amountCents: 0, currency: null);
    }

    public static function fromAmount(int $amountCents, ?string $currency): self
    {
        return new self(amountCents: $amountCents, currency: $currency);
    }

    public function isFree(): bool
    {
        return $this->amountCents === 0;
    }

    /**
     * @return array<string, int|string|null>
     */
    public function toArray(): array
    {
        return [
            'amount_cents' => $this->amountCents,
            'currency' => $this->currency,
        ];
    }
}
