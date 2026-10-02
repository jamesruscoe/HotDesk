// Domain types mirroring the Laravel API resources in app/Http/Resources.

export type OrganizationRole = 'owner' | 'admin' | 'member' | 'guest';
export type DeskType = 'desk' | 'meeting_room';
export type AddOnAvailability = 'included' | 'optional';
export type AddOnPricingUnit = 'per_booking' | 'per_day' | 'per_hour';

export interface Organization {
    id: number;
    name: string;
    slug: string;
    timezone: string;
    role?: OrganizationRole;
    locations_count?: number;
}

export interface Location {
    id: number;
    name: string;
    address: string | null;
    timezone: string;
    floors_count?: number;
}

export interface UserMinimal {
    id: number;
    name: string;
    email: string;
}

export interface LocationAddOnOffer {
    id: number;
    add_on_id: number;
    name: string;
    icon: string | null;
    availability: AddOnAvailability;
    price_cents: number | null;
    pricing_unit: AddOnPricingUnit;
}

export interface DeskAddOnAttachment {
    location_add_on_id: number;
    /** Null falls back to the office's offer. */
    availability: AddOnAvailability | null;
    /** Null falls back to the office's offer. */
    price_cents: number | null;
}

export interface Desk {
    id: number;
    label: string;
    type: DeskType;
    x: number;
    y: number;
    width: number;
    height: number;
    rotation: number;
    is_bookable: boolean;
    assigned_user_id: number | null;
    add_ons: DeskAddOnAttachment[];
}

export interface WallElement {
    id: string;
    type: 'wall';
    /** Flat [x1, y1, x2, y2, ...] in plan units. */
    points: number[];
    thickness: number;
}

export interface RoomElement {
    id: string;
    type: 'room';
    x: number;
    y: number;
    width: number;
    height: number;
    rotation: number;
    label: string;
}

export interface LabelElement {
    id: string;
    type: 'label';
    x: number;
    y: number;
    rotation: number;
    text: string;
    font_size: number;
}

export type FloorElement = WallElement | RoomElement | LabelElement;

export interface Floor {
    id: number;
    name: string;
    level: number;
    width: number;
    height: number;
    layout: { elements: FloorElement[] };
    desks_count?: number;
    desks?: Desk[];
}
