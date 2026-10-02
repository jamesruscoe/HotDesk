<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import TimezoneSelect from '@/components/TimezoneSelect.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    timezones: string[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Organizations', href: route('manage.organizations.index') },
    { title: 'New organization', href: route('manage.organizations.create') },
];

const browserTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;

const form = useForm({
    name: '',
    timezone: props.timezones.includes(browserTimezone) ? browserTimezone : 'UTC',
});

const submit = (): void => {
    form.post(route('manage.organizations.store'));
};
</script>

<template>
    <Head title="New organization" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto w-full max-w-2xl space-y-8 px-4 py-10 md:px-8">
            <PageHeader title="New organization" eyebrow="Organizations" description="The company that owns or runs the offices. You will be its owner." />

            <form class="rounded-md border border-border bg-card" @submit.prevent="submit">
                <div class="space-y-6 p-6">
                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input id="name" v-model="form.name" required autofocus placeholder="Acme Ltd" />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="timezone">Default timezone</Label>
                        <TimezoneSelect id="timezone" v-model="form.timezone" :timezones="timezones" />
                        <p class="text-xs text-muted-foreground">New offices start in this timezone. Each office can set its own.</p>
                        <InputError :message="form.errors.timezone" />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-border bg-muted/40 px-6 py-3">
                    <Button variant="ghost" as-child>
                        <Link :href="route('manage.organizations.index')">Cancel</Link>
                    </Button>
                    <Button :disabled="form.processing">Create organization</Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
