<template>
    <Layout title="Editare rol" :breadcrumbs="['Administrare', 'Roluri', 'Editare rol']">
        <Head title="Editare rol" />
        <Title>Editare rol</Title>
        <div>
            <Form @submit.prevent="update" :loading="sending">
                <div class="grid grid-cols-1 md:grid-cols-12 lg:grid-cols-12 gap-4 auto-cols-min">
                    <div class="md:col-span-6 lg:col-span-4">
                        <text-input v-model="form.name" :error="errors.name" label="Denumire rol" />
                    </div>
                    <div class="md:col-span-6 lg:col-span-4">
                        <text-input v-model="form.guard_name" :error="errors.guard_name" label="Guard name" />
                    </div>
                </div>
                <div class="mt-8">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-ivt-ink">Permisii</h3>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-ivt-paper-2 text-primary text-xs font-semibold">
                            {{ form.selected?.length ?? 0 }} selectate
                        </span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        <div v-for="group in permissionGroups" :key="group" class="rounded-2xl border border-ivt-line bg-white p-4">
                            <div class="mb-3 text-xs font-semibold uppercase tracking-wider text-ivt-ink-soft/70">
                                {{ group }}
                            </div>
                            <div class="space-y-1.5">
                                <template v-for="permission in permissions" :key="permission.id">
                                    <label
                                        v-if="permission.group == group"
                                        :for="'permission-' + permission.id"
                                        class="flex cursor-pointer items-center gap-2.5 rounded-xl border px-3 py-2 text-sm transition-colors"
                                        :class="form.selected?.includes(permission.id)
                                            ? 'border-primary/40 bg-primary/5 text-ivt-ink font-medium'
                                            : 'border-ivt-line text-ivt-ink-soft hover:border-primary/40 hover:bg-primary/5'"
                                    >
                                        <input
                                            class="h-4 w-4 rounded border-ivt-line text-primary focus:ring-primary/30"
                                            type="checkbox"
                                            :id="'permission-' + permission.id"
                                            :value="permission.id"
                                            v-model="form.selected"
                                        >
                                        {{ permission.name }}
                                    </label>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </Form>
        </div>
    </Layout>
</template>

<script>
import Layout from '@/Layouts/Layout.vue';
import Label from '@/Components/Label.vue'
import Title from '@/Components/Title.vue'
import TextInput from '@/Components/TextInput.vue'
import SelectInput from '@/Components/SelectInput.vue'
import TextareaInput from '@/Components/TextareaInput.vue'
import Form from "@/Components/Form.vue";
import {Head} from '@inertiajs/vue3'
import VueMultiselect from "vue-multiselect";
import Checkbox from "@/Components/Checkbox.vue";


export default {
    components: {
        Form,
        TextInput,
        SelectInput,
        TextareaInput,
        Title,
        Label,
        Head,
        VueMultiselect,
        Checkbox,
        Layout
    },

    computed: {
    },

    props: {
        role: Object,
        permissions: Object,
        permissionGroups: Object,
        errors: Object,
        date: Array,
        filtersLabel: Array,
        options: Object,
        title: String,
        permissionsAdmin: Object,
        admins: Object,
        parents: Object,
        clients: Object,
    },
    remember: "form",
    data() {
        return {
            form: this.$inertia.form({
                _method: "put",
                name: this.role.name,
                guard_name: this.role.guard_name,
                selected: this.date

            }),
            sending: false,
            isDisabled: true,
            show: false,
            showfilter: false,
        }
    },

    methods: {
        update() {
            this.form.put(`/administration/roles/${this.role.id}/update`, {
                inline: 'default',
                onStart: () => this.sending = true,
                onSuccess: () => {
                    this.$toast.success(this.$page.props.toast.success.message);
                    this.sending = false;
                },
            });
        },
        showPermissions(){
            this.show = !this.show;
        },
        hide(){
            this.show = false;
        },
        showFilters(){
            this.showfilter = !this.showfilter;
        },
        onlyUnique(value, index, self){
            return self.indexOf(value) === index;
        },
        showFirstWord(str){
            return str.split(' ').shift();
        }
    },
    watch: {
        sending: {
            deep: true,
            handler() {
                setTimeout(()=> this.sending = false, 10000)
            }
        }
    }
};
</script>

