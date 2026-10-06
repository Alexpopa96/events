<script setup>
import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { ComputerDesktopIcon, KeyIcon, ShieldCheckIcon, UserCircleIcon } from '@heroicons/vue/24/outline';
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

    return { component: ClientAccountLayout, props: { title: 'Setări cont', active: 'settings' } };
});

/* ---------- tabs ---------- */
// Only offer the tabs whose feature is enabled for this app.
const tabs = computed(() => [
    { key: 'date', label: 'Date personale', icon: UserCircleIcon, show: page.props.jetstream.canUpdateProfileInformation || page.props.jetstream.hasAccountDeletionFeatures },
    { key: 'parola', label: 'Parolă', icon: KeyIcon, show: page.props.jetstream.canUpdatePassword },
    { key: '2fa', label: 'Autentificare în 2 pași', icon: ShieldCheckIcon, show: true, status: page.props.emailTwoFactor?.enabled ? 'on' : 'off' },
    { key: 'sesiuni', label: 'Sesiuni active', icon: ComputerDesktopIcon, show: true, count: props.sessions?.length || 0 },
].filter((tab) => tab.show));

// The active tab lives in the URL (?tab=parola), so a refresh or shared link lands on the same one.
const fromUrl = new URLSearchParams(page.url.split('?')[1] ?? '').get('tab');
const active = ref(tabs.value.some((tab) => tab.key === fromUrl) ? fromUrl : tabs.value[0].key);

const select = (key) => {
    active.value = key;
    // Client-side only: rewrites the URL without asking the server for anything.
    router.replace({ url: `${page.url.split('?')[0]}${key === tabs.value[0].key ? '' : `?tab=${key}`}`, preserveState: true, preserveScroll: true });
};
</script>

<template>
    <component :is="shell.component" v-bind="shell.props">
        <div class="max-w-4xl">
            <div class="mb-5 overflow-x-auto rounded-2xl border border-ivt-line bg-white p-2 shadow-sm shadow-ivt-ink/5">
                <div class="flex min-w-max items-center gap-1" role="tablist" aria-label="Setări cont">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        type="button"
                        role="tab"
                        :aria-selected="active === tab.key"
                        @click="select(tab.key)"
                        class="flex flex-1 items-center justify-center gap-2 whitespace-nowrap rounded-xl px-4 py-2.5 text-sm font-medium transition-all duration-150"
                        :class="active === tab.key ? 'bg-primary text-white shadow-sm shadow-primary/25' : 'text-ivt-ink-soft hover:bg-ivt-paper-2 hover:text-ivt-ink'"
                    >
                        <component :is="tab.icon" class="h-4 w-4" />
                        {{ tab.label }}
                        <!-- Two-step state at a glance, visible from every tab -->
                        <span
                            v-if="tab.status === 'on'"
                            class="inline-flex items-center gap-1 rounded-full bg-emerald-500 px-2 py-0.5 text-[11px] font-semibold text-white"
                        ><span class="h-1.5 w-1.5 rounded-full bg-white"></span> Activ</span>
                        <span
                            v-else-if="tab.status === 'off'"
                            class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold"
                            :class="active === tab.key ? 'bg-white/25 text-white' : 'bg-amber-100 text-amber-700'"
                        ><span class="h-1.5 w-1.5 rounded-full" :class="active === tab.key ? 'bg-white' : 'bg-amber-500'"></span> Inactiv</span>
                        <span
                            v-if="tab.count"
                            class="rounded-full px-1.5 text-[11px] tabular-nums"
                            :class="active === tab.key ? 'bg-white/20' : 'bg-ivt-paper-2'"
                        >{{ tab.count }}</span>
                    </button>
                </div>
            </div>

            <!-- v-show keeps each form's state (e.g. a 2FA setup in progress) while switching tabs -->
            <div v-show="active === 'date'" class="space-y-5">
                <UpdateProfileInformationForm v-if="$page.props.jetstream.canUpdateProfileInformation" :user="$page.props.auth.user" />

                <DeleteUserForm v-if="$page.props.jetstream.hasAccountDeletionFeatures" />
            </div>

            <div v-if="$page.props.jetstream.canUpdatePassword" v-show="active === 'parola'">
                <UpdatePasswordForm />
            </div>

            <div v-show="active === '2fa'">
                <TwoFactorAuthenticationForm />
            </div>

            <div v-show="active === 'sesiuni'">
                <LogoutOtherBrowserSessionsForm :sessions="sessions" />
            </div>
        </div>
    </component>
</template>
