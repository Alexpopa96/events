<script setup>
import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import {
    ArrowRightIcon,
    CheckBadgeIcon,
    CheckIcon,
    ExclamationTriangleIcon,
    ChevronDownIcon,
    ComputerDesktopIcon,
    KeyIcon,
    ShieldCheckIcon,
    TrashIcon,
    UserCircleIcon,
} from '@heroicons/vue/24/outline';
import DeleteUserForm from '@/Pages/Profile/Partials/DeleteUserForm.vue';
import LogoutOtherBrowserSessionsForm from '@/Pages/Profile/Partials/LogoutOtherBrowserSessionsForm.vue';
import TwoFactorAuthenticationForm from '@/Pages/Profile/Partials/TwoFactorAuthenticationForm.vue';
import UpdatePasswordForm from '@/Pages/Profile/Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from '@/Pages/Profile/Partials/UpdateProfileInformationForm.vue';
import Layout from '@/Layouts/Layout.vue';
import ProviderLayout from '@/Layouts/ProviderLayout.vue';
import ClientAccountLayout from '@/Layouts/ClientAccountLayout.vue';

const props = defineProps({
    confirmsTwoFactorAuthentication: Boolean,
    sessions: Array,
});

const page = usePage();

// Same account page, wrapped in the shell each audience already knows:
// providers get the provider panel, staff the admin sidebar, clients the account area.
// Don't key off 'view dashboard': every role has it.
const shell = computed(() => {
    if (page.props.auth.isProvider) {
        return { component: ProviderLayout, props: { title: 'Setări cont' } };
    }

    if (page.props.auth.can.viewAdministration) {
        return { component: Layout, props: { title: 'Informații profil', breadcrumbs: ['Setări', 'Profil'] } };
    }

    return { component: ClientAccountLayout, props: { title: 'Setări cont', active: 'settings', topMenu: true } };
});

const user = computed(() => page.props.auth.user);
const jetstream = computed(() => page.props.jetstream);
const twoFactorOn = computed(() => !!page.props.emailTwoFactor?.enabled);
const sessionCount = computed(() => props.sessions?.length || 0);

const initials = computed(() => (user.value?.name || '')
    .split(' ')
    .filter(Boolean)
    .map((part) => part[0])
    .slice(0, 2)
    .join('')
    .toUpperCase());

const memberSince = computed(() => (user.value?.created_at
    ? new Date(user.value.created_at).toLocaleDateString('ro-RO', { month: 'long', year: 'numeric' })
    : null));

/* ---------- settings rows ---------- */
// Each row shows the current state at a glance and opens its form inline.
// Only the rows whose feature is enabled for this app are offered.
const groups = computed(() => [
    {
        title: 'Cont',
        rows: [
            {
                key: 'date',
                icon: UserCircleIcon,
                label: 'Nume și email',
                summary: `${user.value?.name ?? ''} · ${user.value?.email ?? ''}`,
                action: 'Editează',
                show: jetstream.value.canUpdateProfileInformation,
            },
        ],
    },
    {
        title: 'Securitate',
        rows: [
            {
                key: 'parola',
                icon: KeyIcon,
                label: 'Parolă',
                summary: 'Folosește o parolă lungă, pe care nu o mai ai în alt cont.',
                action: 'Schimbă',
                show: jetstream.value.canUpdatePassword,
            },
            {
                key: '2fa',
                icon: ShieldCheckIcon,
                label: 'Autentificare în 2 pași',
                summary: twoFactorOn.value ? 'Îți cerem un cod pe email la fiecare conectare.' : 'Adaugă un cod pe email peste parolă.',
                status: twoFactorOn.value ? 'on' : 'off',
                action: twoFactorOn.value ? 'Gestionează' : 'Activează',
                show: true,
            },
            {
                key: 'sesiuni',
                icon: ComputerDesktopIcon,
                label: 'Sesiuni active',
                summary: sessionCount.value === 1 ? 'Doar acest dispozitiv' : `${sessionCount.value} dispozitive conectate`,
                action: 'Vezi',
                show: true,
            },
        ].filter((row) => row.show),
    },
    {
        title: 'Zonă periculoasă',
        danger: true,
        rows: [
            {
                key: 'sterge',
                icon: TrashIcon,
                label: 'Șterge contul',
                summary: 'Șterge definitiv contul și toate datele lui.',
                action: 'Șterge',
                show: jetstream.value.hasAccountDeletionFeatures,
            },
        ],
    },
].map((group) => ({ ...group, rows: group.rows.filter((row) => row.show) })).filter((group) => group.rows.length));

// Security checklist shown in the side card.
const checks = computed(() => [
    { label: 'Email verificat', done: !!user.value?.email_verified_at },
    { label: 'Autentificare în 2 pași', done: twoFactorOn.value, key: '2fa' },
    { label: 'O singură sesiune activă', done: sessionCount.value <= 1, key: 'sesiuni' },
]);
const score = computed(() => Math.round((checks.value.filter((check) => check.done).length / checks.value.length) * 100));
const RING = 2 * Math.PI * 42;

const keys = computed(() => groups.value.flatMap((group) => group.rows.map((row) => row.key)));

// The open row lives in the URL (?tab=parola), so a refresh or shared link lands on the same one.
const fromUrl = new URLSearchParams(page.url.split('?')[1] ?? '').get('tab');
const open = ref(keys.value.includes(fromUrl) ? fromUrl : null);

const toggle = (key, force = false) => {
    open.value = open.value === key && !force ? null : key;
    // Client-side only: rewrites the URL without asking the server for anything.
    router.replace({ url: `${page.url.split('?')[0]}${open.value ? `?tab=${open.value}` : ''}`, preserveState: true, preserveScroll: true });
};

const jumpTo = (key) => {
    toggle(key, true);
    document.getElementById(`row-${key}`)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
};
</script>

<template>
    <component :is="shell.component" v-bind="shell.props">
        <div class="grid gap-8 lg:grid-cols-[340px_1fr] lg:items-start xl:gap-10">
            <!-- SIDE: identity (non-client shells) + security status + quick nav -->
            <aside class="space-y-5 lg:sticky lg:top-24">
                <div v-if="shell.component !== ClientAccountLayout" class="flex items-center gap-4 rounded-[22px] border-2 border-ivt-line bg-white p-5">
                    <span class="flex h-14 w-14 flex-none items-center justify-center rounded-2xl bg-ivt-ink text-lg font-semibold tracking-wide text-white">
                        {{ initials }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="flex items-center gap-1.5 font-semibold text-ivt-ink">
                            <span class="truncate">{{ user?.name }}</span>
                            <CheckBadgeIcon v-if="user?.email_verified_at" class="h-5 w-5 flex-none text-primary" title="Email verificat" />
                        </p>
                        <p class="truncate text-sm text-ivt-ink-soft">{{ user?.email }}</p>
                        <p v-if="memberSince" class="mt-0.5 text-xs text-ivt-ink-faint">Membru din {{ memberSince }}</p>
                    </div>
                </div>

                <!-- Security status -->
                <div class="relative overflow-hidden rounded-[24px] bg-gradient-to-br from-ivt-ink-2 to-ivt-ink p-6 text-ivt-on-dark">
                    <span class="pointer-events-none absolute -right-16 -top-20 h-48 w-48 rounded-full" style="background: radial-gradient(circle, rgba(124,58,237,0.28), transparent 70%);" />

                    <div class="relative flex items-center gap-5">
                        <div class="relative h-24 w-24 flex-none">
                            <svg viewBox="0 0 100 100" class="h-full w-full -rotate-90">
                                <circle cx="50" cy="50" r="42" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="8" />
                                <circle
                                    cx="50" cy="50" r="42" fill="none" stroke-width="8" stroke-linecap="round"
                                    class="stroke-ivt-accent-bright transition-[stroke-dashoffset] duration-700"
                                    :stroke-dasharray="RING"
                                    :stroke-dashoffset="RING * (1 - score / 100)"
                                />
                            </svg>
                            <span class="absolute inset-0 flex flex-col items-center justify-center">
                                <span class="font-display text-[24px] leading-none tabular-nums">{{ score }}%</span>
                                <span class="mt-0.5 text-[8.5px] font-bold uppercase tracking-[0.12em] text-ivt-on-dark-dim">securitate</span>
                            </span>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-ivt-accent-bright">Starea contului</p>
                            <p class="mt-1 font-display text-[19px] leading-tight">
                                {{ score === 100 ? 'Contul tău e bine protejat.' : 'Mai poți întări securitatea.' }}
                            </p>
                        </div>
                    </div>

                    <ul class="relative mt-6 space-y-2">
                        <li v-for="check in checks" :key="check.label">
                            <component
                                :is="check.key && !check.done ? 'button' : 'div'"
                                type="button"
                                class="flex w-full items-center gap-3 rounded-xl border border-white/10 bg-white/[0.04] px-3.5 py-2.5 text-left text-[13px]"
                                :class="check.key && !check.done ? 'transition-colors hover:border-ivt-accent-bright/40 hover:bg-white/10' : ''"
                                @click="check.key && !check.done && jumpTo(check.key)"
                            >
                                <span
                                    class="flex h-6 w-6 flex-none items-center justify-center rounded-full"
                                    :class="check.done ? 'bg-success-400/20 text-success-300' : 'bg-warning-400/20 text-warning-300'"
                                >
                                    <CheckIcon v-if="check.done" class="h-3.5 w-3.5" stroke-width="3" />
                                    <ExclamationTriangleIcon v-else class="h-3.5 w-3.5" stroke-width="2.5" />
                                </span>
                                <span class="flex-1" :class="check.done ? 'text-ivt-on-dark' : 'text-ivt-on-dark-dim'">{{ check.label }}</span>
                                <ArrowRightIcon v-if="check.key && !check.done" class="h-3.5 w-3.5 text-ivt-accent-bright" stroke-width="2.5" />
                            </component>
                        </li>
                    </ul>
                </div>

                <!-- Quick nav -->
                <nav class="hidden rounded-[22px] border-2 border-ivt-line bg-white p-2 lg:block">
                    <template v-for="group in groups" :key="group.title">
                        <button
                            v-for="row in group.rows"
                            :key="row.key"
                            type="button"
                            class="group flex w-full items-center gap-3 rounded-2xl px-3 py-2.5 text-left text-[13.5px] font-semibold transition-colors"
                            :class="open === row.key
                                ? 'bg-ivt-ink text-ivt-paper'
                                : group.danger ? 'text-danger-600 hover:bg-danger-50' : 'text-ivt-ink-soft hover:bg-ivt-paper-2 hover:text-ivt-ink'"
                            @click="jumpTo(row.key)"
                        >
                            <component :is="row.icon" class="h-[18px] w-[18px]" :class="open === row.key ? 'text-ivt-accent-bright' : ''" />
                            <span class="flex-1">{{ row.label }}</span>
                            <ArrowRightIcon class="h-3.5 w-3.5 opacity-0 transition-all group-hover:translate-x-0.5 group-hover:opacity-100" :class="open === row.key ? 'opacity-100' : ''" stroke-width="2.5" />
                        </button>
                    </template>
                </nav>
            </aside>

            <!-- MAIN: grouped settings -->
            <div class="min-w-0 space-y-10">
                <section v-for="group in groups" :key="group.title">
                    <div class="mb-4 flex items-baseline gap-4 border-b border-ivt-line pb-3">
                        <h2 class="font-display text-[22px] font-medium" :class="group.danger ? 'text-danger-600' : 'text-ivt-ink'">{{ group.title }}</h2>
                        <span class="text-[13px] text-ivt-ink-faint">{{ group.rows.length }} {{ group.rows.length === 1 ? 'setare' : 'setări' }}</span>
                    </div>

                    <div class="space-y-3.5">
                        <div
                            v-for="row in group.rows"
                            :id="`row-${row.key}`"
                            :key="row.key"
                            class="scroll-mt-28 overflow-hidden rounded-[22px] border-2 bg-white transition-all duration-300"
                            :class="[
                                group.danger
                                    ? (open === row.key ? 'border-danger-300 shadow-[0_20px_40px_-24px_rgba(220,38,38,0.45)]' : 'border-danger-100 hover:border-danger-200')
                                    : (open === row.key ? 'border-primary/30 shadow-glow-primary' : 'border-ivt-line hover:-translate-y-0.5 hover:border-primary/20 hover:shadow-ivt-soft'),
                            ]"
                        >
                            <button
                                type="button"
                                class="group flex w-full items-center gap-4 px-5 py-5 text-left sm:px-6"
                                :aria-expanded="open === row.key"
                                :aria-controls="`setting-${row.key}`"
                                @click="toggle(row.key)"
                            >
                                <span
                                    class="flex h-12 w-12 flex-none items-center justify-center rounded-2xl transition-all duration-300"
                                    :class="group.danger
                                        ? 'bg-danger-50 text-danger-600'
                                        : open === row.key ? 'rotate-[-6deg] bg-brand text-white' : 'bg-ivt-paper-2 text-primary group-hover:rotate-[-6deg] group-hover:scale-105'"
                                >
                                    <component :is="row.icon" class="h-6 w-6" stroke-width="1.5" />
                                </span>

                                <span class="min-w-0 flex-1">
                                    <span class="flex flex-wrap items-center gap-2 text-[15.5px] font-semibold" :class="group.danger ? 'text-danger-700' : 'text-ivt-ink'">
                                        {{ row.label }}
                                        <span
                                            v-if="row.status"
                                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[10.5px] font-bold uppercase tracking-[0.06em]"
                                            :class="row.status === 'on' ? 'bg-success-100 text-success-700' : 'bg-warning-100 text-warning-700'"
                                        >
                                            <span class="h-1.5 w-1.5 rounded-full" :class="row.status === 'on' ? 'bg-success-500' : 'bg-warning-500'" />
                                            {{ row.status === 'on' ? 'Activ' : 'Inactiv' }}
                                        </span>
                                    </span>
                                    <span class="mt-0.5 block truncate text-[13px] text-ivt-ink-soft">{{ row.summary }}</span>
                                </span>

                                <span
                                    class="hidden flex-none items-center gap-1.5 rounded-full py-2 pl-4 pr-3 text-[12.5px] font-semibold transition-all duration-300 sm:inline-flex"
                                    :class="group.danger
                                        ? 'bg-danger-50 text-danger-600 group-hover:bg-danger-600 group-hover:text-white'
                                        : open === row.key ? 'bg-ivt-ink text-ivt-paper' : 'bg-ivt-paper-2 text-ivt-ink group-hover:bg-ivt-ink group-hover:text-ivt-paper'"
                                >
                                    {{ open === row.key ? 'Închide' : row.action }}
                                    <ChevronDownIcon class="h-3.5 w-3.5 transition-transform duration-300" :class="{ 'rotate-180': open === row.key }" stroke-width="2.5" />
                                </span>
                                <ChevronDownIcon
                                    class="h-4 w-4 flex-none text-ivt-ink-faint transition-transform duration-300 sm:hidden"
                                    :class="{ 'rotate-180': open === row.key }"
                                />
                            </button>

                            <!-- Kept mounted while closed so each form keeps its state (e.g. a 2FA setup in progress) -->
                            <div
                                :id="`setting-${row.key}`"
                                class="grid transition-[grid-template-rows] duration-300 ease-out"
                                :class="open === row.key ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]'"
                                :inert="open !== row.key"
                            >
                                <div class="overflow-hidden">
                                    <div class="mx-5 mb-5 border-t-2 border-dashed pt-5 sm:mx-6 sm:mb-6 sm:ml-[5.5rem] sm:pt-6" :class="group.danger ? 'border-danger-100' : 'border-ivt-line'">
                                        <UpdateProfileInformationForm v-if="row.key === 'date'" :user="user" />
                                        <UpdatePasswordForm v-else-if="row.key === 'parola'" />
                                        <TwoFactorAuthenticationForm v-else-if="row.key === '2fa'" />
                                        <LogoutOtherBrowserSessionsForm v-else-if="row.key === 'sesiuni'" :sessions="sessions" />
                                        <DeleteUserForm v-else-if="row.key === 'sterge'" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </component>
</template>
