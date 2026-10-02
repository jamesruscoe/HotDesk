<?php

declare(strict_types=1);

namespace App\DTOs;

use Carbon\CarbonImmutable;

/**
 * A location's open period on one local date, as UTC instants.
 */
final readonly class OpeningWindowDto
{
    private function __construct(
        public string $localDate,
        public CarbonImmutable $startsAt,
        public CarbonImmutable $endsAt,
    ) {}

    public static function fromInstants(string $localDate, CarbonImmutable $startsAt, CarbonImmutable $endsAt): self
    {
        return new self(
            localDate: $localDate,
            startsAt: $startsAt->utc(),
            endsAt: $endsAt->utc(),
        );
    }

    public function contains(CarbonImmutable $start, CarbonImmutable $end): bool
    {
        return $start->greaterThanOrEqualTo($this->startsAt) && $end->lessThanOrEqualTo($this->endsAt);
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'local_date' => $this->localDate,
            'starts_at' => $this->startsAt->toIso8601ZuluString(),
            'ends_at' => $this->endsAt->toIso8601ZuluString(),
        ];
    }
}
