<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import TimezoneSelect from '@/components/TimezoneSelect.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { Organization } from '@/types/hotdesk';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    organization: Organization;
    timezones: string[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Organizations', href: route('manage.organizations.index') },
    { title: props.organization.name, href: route('manage.organizations.show', { organization: props.organization.slug }) },
    { title: 'New office', href: route('manage.organizations.locations.create', { organization: props.organization.slug }) },
];

const form = useForm({
    name: '',
    address: '',
    timezone: props.organization.timezone,
});

const submit = (): void => {
    form.post(route('manage.organizations.locations.store', { organization: props.organization.slug }));
};
</script>

<template>
    <Head title="New office" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto w-full max-w-2xl space-y-8 px-4 py-10 md:px-8">
            <PageHeader
                title="New office"
                :eyebrow="organization.name"
                description="Opens Monday to Friday, 09:00 to 17:00 to start with. Booking times are shown in the office's timezone."
            />

            <form class="rounded-md border border-border bg-card" @submit.prevent="submit">
                <div class="space-y-6 p-6">
                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input id="name" v-model="form.name" required autofocus placeholder="Office A" />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="address">Address <span class="font-normal text-muted-foreground">(optional)</span></Label>
                        <Input id="address" v-model="form.address" placeholder="1 High Street, London" />
                        <InputError :message="form.errors.address" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="timezone">Timezone</Label>
                        <TimezoneSelect id="timezone" v-model="form.timezone" :timezones="timezones" />
                        <InputError :message="form.errors.timezone" />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-border bg-muted/40 px-6 py-3">
                    <Button variant="ghost" as-child>
                        <Link :href="route('manage.organizations.show', { organization: organization.slug })">Cancel</Link>
                    </Button>
                    <Button :disabled="form.processing">Create office</Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
