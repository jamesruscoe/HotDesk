<?php

declare(strict_types=1);

namespace Tests\Feature\Manage;

use App\Enums\AddOnAvailability;
use App\Enums\AddOnPricingUnit;
use App\Enums\DayOfWeek;
use App\Enums\OrganizationRole;
use App\Models\AddOn;
use App\Models\Booking;
use App\Models\Desk;
use App\Models\Floor;
use App\Models\Location;
use App\Models\LocationAddOn;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class FloorPlanTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private Location $location;

    private Floor $floor;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->create();
        $this->location = Location::factory()->for($this->organization)->create();
        $this->floor = Floor::factory()->for($this->location)->create();
        $this->admin = $this->userWithRole(OrganizationRole::ADMIN);
    }

    public function test_creating_an_organization_makes_the_creator_its_owner(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('manage.organizations.store'), [
            'name' => 'Acme Workspace',
            'timezone' => 'Europe/London',
        ]);

        $organization = Organization::query()->where('name', 'Acme Workspace')->sole();
        $response->assertRedirect(route('manage.organizations.show', $organization));
        $this->assertSame(OrganizationRole::OWNER, $user->roleIn($organization));
    }

    public function test_new_office_opens_weekdays_nine_to_five(): void
    {
        $this->actingAs($this->admin)->post(route('manage.organizations.locations.store', $this->organization), [
            'name' => 'Office B',
            'timezone' => 'Europe/London',
        ])->assertSessionHasNoErrors();

        $location = Location::query()->where('name', 'Office B')->sole();
        $this->assertSame(7, $location->hours()->count());
        $this->assertTrue($location->hours()->where('day_of_week', DayOfWeek::SATURDAY)->sole()->is_closed);
        $this->assertSame('09:00', $location->hours()->where('day_of_week', DayOfWeek::MONDAY)->sole()->opens_at);
    }

    public function test_members_cannot_open_the_builder(): void
    {
        $member = $this->userWithRole(OrganizationRole::MEMBER);

        $this->actingAs($member)->get($this->editUrl())->assertForbidden();
    }

    public function test_outsiders_cannot_open_the_builder(): void
    {
        $this->actingAs(User::factory()->create())->get($this->editUrl())->assertForbidden();
    }

    public function test_floor_from_another_office_is_not_found(): void
    {
        $otherFloor = Floor::factory()->create();

        $this->actingAs($this->admin)
            ->get(route('manage.organizations.floors.edit', [$this->organization, $this->location, $otherFloor]))
            ->assertNotFound();
    }

    public function test_admin_sees_the_builder_with_desks_and_add_on_offers(): void
    {
        Desk::factory()->for($this->floor)->create(['label' => 'D-1']);
        $this->offer('Unlimited tea');

        $this->actingAs($this->admin)->get($this->editUrl())
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Manage/Floors/Edit')
                ->where('floor.desks.0.label', 'D-1')
                ->has('addOnOffers', 1)
                ->where('addOnOffers.0.name', 'Unlimited tea')
                ->has('members'));
    }

    public function test_saving_creates_updates_and_removes_desks(): void
    {
        $kept = Desk::factory()->for($this->floor)->create(['label' => 'D-1']);
        $removed = Desk::factory()->for($this->floor)->create(['label' => 'D-2']);
        $tea = $this->offer('Unlimited tea');

        $this->actingAs($this->admin)->put($this->updateUrl(), $this->payload([
            $this->desk(['id' => $kept->id, 'label' => 'Window 1', 'x' => 300, 'add_ons' => [
                ['location_add_on_id' => $tea->id, 'availability' => 'optional', 'price_cents' => 150],
            ]]),
            $this->desk(['label' => 'D-3', 'type' => 'meeting_room', 'assigned_user_id' => $this->admin->id]),
        ], [
            ['id' => 'wall-1', 'type' => 'wall', 'points' => [0, 0, 500, 0, 500, 300], 'thickness' => 8],
            ['id' => 'room-1', 'type' => 'room', 'x' => 10, 'y' => 10, 'width' => 200, 'height' => 100, 'rotation' => 0, 'label' => 'Kitchen'],
        ]))->assertSessionHasNoErrors()->assertRedirect();

        $kept->refresh();
        $this->assertSame('Window 1', $kept->label);
        $this->assertSame(300.0, $kept->x);
        $this->assertSame(150, $kept->addOns()->sole()->price_cents);
        $this->assertSoftDeleted($removed);

        $new = $this->floor->desks()->where('label', 'D-3')->sole();
        $this->assertSame($this->admin->id, $new->assigned_user_id);
        $this->assertNotEmpty($new->qr_token);

        $elements = $this->floor->fresh()->layout['elements'];
        $this->assertCount(2, $elements);
        $this->assertSame('Kitchen', $elements[1]['label']);
    }

    public function test_desk_with_upcoming_bookings_cannot_be_removed(): void
    {
        $desk = Desk::factory()->for($this->floor)->create(['label' => 'D-1']);
        Booking::factory()->for($desk)->create(['starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour()]);

        $this->actingAs($this->admin)
            ->put($this->updateUrl(), $this->payload([]))
            ->assertSessionHasErrors('desks');

        $this->assertNotSoftDeleted($desk);
    }

    public function test_duplicate_desk_labels_are_rejected(): void
    {
        $this->actingAs($this->admin)
            ->put($this->updateUrl(), $this->payload([$this->desk(['label' => 'D-1']), $this->desk(['label' => 'd-1'])]))
            ->assertSessionHasErrors('desks.1.label');
    }

    public function test_desk_ids_from_another_floor_are_rejected(): void
    {
        $foreign = Desk::factory()->create();

        $this->actingAs($this->admin)
            ->put($this->updateUrl(), $this->payload([$this->desk(['id' => $foreign->id])]))
            ->assertSessionHasErrors('desks.0.id');
    }

    public function test_assigning_a_desk_to_a_non_member_is_rejected(): void
    {
        $outsider = User::factory()->create();

        $this->actingAs($this->admin)
            ->put($this->updateUrl(), $this->payload([$this->desk(['assigned_user_id' => $outsider->id])]))
            ->assertSessionHasErrors('desks.0.assigned_user_id');
    }

    public function test_add_ons_from_another_office_are_rejected(): void
    {
        $otherLocation = Location::factory()->for($this->organization)->create();
        $foreignOffer = $this->offer('Parking', $otherLocation);

        $this->actingAs($this->admin)
            ->put($this->updateUrl(), $this->payload([$this->desk(['add_ons' => [['location_add_on_id' => $foreignOffer->id]]])]))
            ->assertSessionHasErrors('desks.0.add_ons.0.location_add_on_id');
    }

    private function userWithRole(OrganizationRole $role): User
    {
        $user = User::factory()->create();
        $this->organization->members()->attach($user, ['role' => $role->value]);

        return $user;
    }

    private function offer(string $name, ?Location $location = null): LocationAddOn
    {
        $location ??= $this->location;
        $addOn = AddOn::query()->create(['organization_id' => $this->organization->id, 'name' => $name]);

        return $location->addOns()->create([
            'add_on_id' => $addOn->id,
            'availability' => AddOnAvailability::OPTIONAL,
            'price_cents' => 200,
            'pricing_unit' => AddOnPricingUnit::PER_DAY,
        ]);
    }

    private function editUrl(): string
    {
        return route('manage.organizations.floors.edit', [$this->organization, $this->location, $this->floor]);
    }

    private function updateUrl(): string
    {
        return route('manage.organizations.floors.update', [$this->organization, $this->location, $this->floor]);
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function desk(array $overrides = []): array
    {
        return [
            'id' => null,
            'label' => 'D-9',
            'type' => 'desk',
            'x' => 100,
            'y' => 100,
            'width' => 60,
            'height' => 40,
            'rotation' => 0,
            'is_bookable' => true,
            'assigned_user_id' => null,
            'add_ons' => [],
            ...$overrides,
        ];
    }

    /**
     * @param list<array<string, mixed>> $desks
     * @param list<array<string, mixed>> $elements
     * @return array<string, mixed>
     */
    private function payload(array $desks, array $elements = []): array
    {
        return [
            'width' => 1200,
            'height' => 800,
            'layout' => ['elements' => $elements],
            'desks' => $desks,
        ];
    }
}
