<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import type { SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowRight } from 'lucide-vue-next';

const page = usePage<SharedData>();

const features = [
    { label: 'Plan', title: 'Draw your floors', text: 'Walls, rooms, desks and meeting rooms, laid out on a grid in minutes.' },
    { label: 'Book', title: 'Book the way you work', text: 'By the hour, for the full working day or across several days, in the office’s own timezone.' },
    { label: 'Check in', title: 'Scan at the desk', text: 'Optional QR check-in frees up desks when people do not turn up.' },
];

// Desks for the hero drawing: [x, y, state].
const heroDesks: [number, number, 'free' | 'taken' | 'mine'][] = [
    [70, 70, 'free'],
    [150, 70, 'taken'],
    [260, 70, 'free'],
    [340, 70, 'mine'],
    [70, 180, 'taken'],
    [150, 180, 'free'],
    [260, 180, 'free'],
    [340, 180, 'free'],
];

const deskFill = { free: 'rgba(76,195,230,0.14)', taken: 'rgba(255,255,255,0.06)', mine: '#FFC24B' };
const deskStroke = { free: '#4CC3E6', taken: 'rgba(233,241,251,0.25)', mine: '#FFC24B' };
</script>

<template>
    <Head title="Desk booking for flexible offices" />

    <div class="min-h-screen bg-background text-foreground">
        <section class="bp-board text-[#E9F1FB]">
            <header class="mx-auto flex max-w-6xl items-center justify-between px-6 py-5">
                <Link :href="route('home')" class="text-white"><AppLogo /></Link>
                <nav class="flex items-center gap-2 text-sm font-medium">
                    <Link v-if="page.props.auth.user" :href="route('dashboard')" class="rounded bg-bp-cyan px-4 py-2 font-semibold text-bp-ink hover:bg-bp-cyan/90">
                        Open dashboard
                    </Link>
                    <template v-else>
                        <Link :href="route('login')" class="rounded px-4 py-2 text-[#E9F1FB]/80 hover:text-white">Log in</Link>
                        <Link :href="route('register')" class="rounded bg-bp-cyan px-4 py-2 font-semibold text-bp-ink hover:bg-bp-cyan/90">Get started</Link>
                    </template>
                </nav>
            </header>

            <div class="mx-auto grid max-w-6xl items-center gap-12 px-6 pb-20 pt-10 lg:grid-cols-[1fr_1.1fr] lg:pb-28 lg:pt-16">
                <div>
                    <p class="font-mono text-xs font-medium uppercase tracking-[0.12em] text-bp-cyan">Desk booking · Free to use</p>
                    <h1 class="mt-5 text-[44px] font-bold leading-[1.05] tracking-tight text-white sm:text-[56px]">Book the desk, not the guesswork.</h1>
                    <p class="mt-5 max-w-lg text-lg text-[#E9F1FB]/75">
                        Draw your office floor plans, let your team book desks and meeting rooms, and rent spare space to others.
                    </p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <Link
                            :href="page.props.auth.user ? route('dashboard') : route('register')"
                            class="inline-flex items-center gap-2 rounded bg-bp-amber px-5 py-3 text-sm font-bold text-bp-ink hover:bg-bp-amber/90"
                        >
                            {{ page.props.auth.user ? 'Go to dashboard' : 'Create your workspace' }} <ArrowRight class="size-4" />
                        </Link>
                        <Link v-if="!page.props.auth.user" :href="route('login')" class="inline-flex items-center rounded border border-white/20 px-5 py-3 text-sm font-semibold text-white hover:bg-white/5">
                            Log in
                        </Link>
                    </div>
                </div>

                <!-- The plan drawing -->
                <figure class="relative">
                    <svg viewBox="0 0 520 320" class="w-full" role="img" aria-label="A floor plan with one desk booked">
                        <rect x="10" y="10" width="500" height="300" rx="6" fill="rgba(18,48,90,0.75)" stroke="rgba(233,241,251,0.18)" />
                        <polyline points="30,30 490,30 490,290 30,290 30,30" fill="none" stroke="#E9F1FB" stroke-width="6" stroke-linejoin="round" />
                        <polyline points="420,30 420,160 490,160" fill="none" stroke="#E9F1FB" stroke-width="4" stroke-linejoin="round" />
                        <rect x="432" y="52" width="46" height="88" rx="5" fill="rgba(255,194,75,0.08)" stroke="rgba(255,194,75,0.75)" stroke-width="1.5" />
                        <text x="455" y="100" text-anchor="middle" fill="#FFD98A" font-size="10" font-weight="600">Room 1</text>
                        <g v-for="([x, y, state], index) in heroDesks" :key="index">
                            <rect :x="x + 14" :y="y + 36" width="24" height="7" rx="3" fill="rgba(76,195,230,0.35)" />
                            <rect :x="x" :y="y" width="52" height="30" rx="4" :fill="deskFill[state]" :stroke="deskStroke[state]" stroke-width="1.5" />
                        </g>
                        <line x1="340" y1="60" x2="392" y2="60" stroke="rgba(201,239,255,0.85)" />
                        <line x1="340" y1="56" x2="340" y2="64" stroke="rgba(201,239,255,0.85)" />
                        <line x1="392" y1="56" x2="392" y2="64" stroke="rgba(201,239,255,0.85)" />
                        <text x="366" y="52" text-anchor="middle" fill="rgba(201,239,255,0.85)" font-size="9" font-family="IBM Plex Mono, monospace">60 × 40</text>
                        <rect x="60" y="232" width="130" height="40" rx="4" fill="rgba(255,255,255,0.035)" stroke="rgba(233,241,251,0.35)" stroke-dasharray="6 4" stroke-width="1.5" />
                        <text x="70" y="250" fill="rgba(233,241,251,0.6)" font-size="9" font-family="IBM Plex Mono, monospace" letter-spacing="1">KITCHEN</text>
                    </svg>
                    <figcaption class="absolute -bottom-6 right-4 flex items-center gap-3 rounded-md border border-border bg-card px-4 py-3 text-foreground shadow-2xl shadow-black/30">
                        <span class="size-3 rounded-sm bg-bp-amber" />
                        <span>
                            <span class="block text-sm font-bold">Desk D-4 booked</span>
                            <span class="block font-mono text-xs text-muted-foreground">Fri 3 Oct · 09:00–17:00</span>
                        </span>
                    </figcaption>
                </figure>
            </div>
        </section>

        <section class="mx-auto grid max-w-6xl gap-px overflow-hidden px-6 py-20 md:grid-cols-3 md:gap-10">
            <div v-for="feature in features" :key="feature.title" class="border-t-2 border-bp-ink pt-5 dark:border-bp-cyan">
                <p class="bp-label">{{ feature.label }}</p>
                <h2 class="mt-2 text-lg font-bold">{{ feature.title }}</h2>
                <p class="mt-1.5 text-sm text-muted-foreground">{{ feature.text }}</p>
            </div>
        </section>

        <footer class="border-t border-border py-8 text-center font-mono text-xs text-muted-foreground">© {{ new Date().getFullYear() }} HotDesk</footer>
    </div>
</template>
