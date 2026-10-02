<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\BookingType;

/**
 * Effective booking settings for a location: defaults, overridden by the
 * organization's settings, overridden by the location's own settings.
 */
final readonly class LocationSettingsDto
{
    public const DEFAULTS = [
        'allowed_booking_types' => ['hourly', 'full_day'],
        'allow_multi_day' => true,
        'max_days_ahead' => 30,
        'qr_checkin_enabled' => false,
        'checkin_window_minutes' => 15,
        'auto_release_minutes' => 30,
        'allow_walk_ins' => true,
    ];

    /**
     * @param list<BookingType> $allowedBookingTypes
     */
    private function __construct(
        public array $allowedBookingTypes,
        public bool $allowMultiDay,
        public int $maxDaysAhead,
        public bool $qrCheckinEnabled,
        public int $checkinWindowMinutes,
        public int $autoReleaseMinutes,
        public bool $allowWalkIns,
    ) {}

    /**
     * @param array<string, mixed>|null $organizationSettings
     * @param array<string, mixed>|null $locationSettings
     */
    public static function fromLayers(?array $organizationSettings, ?array $locationSettings): self
    {
        return self::fromArray(array_merge(self::DEFAULTS, $organizationSettings ?? [], $locationSettings ?? []));
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            allowedBookingTypes: array_values(array_map(
                fn (string $type): BookingType => BookingType::from($type),
                $data['allowed_booking_types'],
            )),
            allowMultiDay: (bool) $data['allow_multi_day'],
            maxDaysAhead: (int) $data['max_days_ahead'],
            qrCheckinEnabled: (bool) $data['qr_checkin_enabled'],
            checkinWindowMinutes: (int) $data['checkin_window_minutes'],
            autoReleaseMinutes: (int) $data['auto_release_minutes'],
            allowWalkIns: (bool) $data['allow_walk_ins'],
        );
    }

    public function allows(BookingType $type): bool
    {
        return in_array($type, $this->allowedBookingTypes, true);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'allowed_booking_types' => array_map(fn (BookingType $type): string => $type->value, $this->allowedBookingTypes),
            'allow_multi_day' => $this->allowMultiDay,
            'max_days_ahead' => $this->maxDaysAhead,
            'qr_checkin_enabled' => $this->qrCheckinEnabled,
            'checkin_window_minutes' => $this->checkinWindowMinutes,
            'auto_release_minutes' => $this->autoReleaseMinutes,
            'allow_walk_ins' => $this->allowWalkIns,
        ];
    }
}
