<template>
    <Layout title="Adaugare permisii" :breadcrumbs="['Administrare', 'Permisii', 'Adaugare permisii']">
        <Head title="Adaugare permisii" />
        <Title>Adauga o permisie</Title>
        <div>
            <form @submit.prevent="store" class="space-y-4">
                <div v-for="(form,index) in forms" :key="index" class="relative rounded-2xl border border-ivt-line bg-white shadow-sm shadow-ivt-ink/5 p-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-ivt-ink">Formular #{{ index + 1 }}</h3>
                        <button
                            v-if="forms.length > 1"
                            type="button"
                            @click.prevent="deleteForm(index)"
                            class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-danger-600 bg-danger-50 transition-colors hover:bg-danger-100"
                        >
                            <Trash2 class="h-3.5 w-3.5" /> Elimină
                        </button>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <text-input v-model="form.name" label="Denumire permisie *" />
                        <text-input v-model="form.group" label="Denumire grupă" />
                        <text-input v-model="form.parent" value=" " label="Denumire părinte" />
                        <text-input v-model="form.guard_name" label="Guard name *" />
                    </div>
                </div>

                <div v-if="counter > 0 && alert" class="flex items-start justify-between gap-3 rounded-2xl border border-danger-200 bg-danger-50 px-4 py-3 text-sm text-danger-700" role="alert">
                    <p class="font-medium">Au apărut erori în formularele {{ arr.toString() }}. Verificați ca toate datele necesare să fie introduse.</p>
                    <button type="button" @click.prevent="alert = false" class="flex-none text-danger-500 hover:text-danger-700" aria-label="Închide">
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <div class="flex items-center justify-between gap-3">
                    <button
                        type="button"
                        @click.prevent="addForm"
                        class="inline-flex items-center gap-2 rounded-xl border border-ivt-line bg-white px-4 py-2 text-sm font-semibold text-ivt-ink-soft transition-colors hover:border-ivt-violet hover:text-primary"
                    >
                        <Plus class="h-4 w-4" /> Adaugă încă una
                    </button>
                    <button
                        type="submit"
                        class="inline-flex items-center gap-2 rounded-xl bg-brand px-5 py-2 text-sm font-semibold text-white shadow-sm shadow-primary/25 transition-colors hover:brightness-110 hover:shadow-glow-violet"
                    >
                        <Check class="h-4 w-4" /> Salvează
                    </button>
                </div>
                <p class="text-xs text-ivt-ink-soft">* Câmp obligatoriu</p>
            </form>
        </div>
    </Layout>
</template>

<script>
import Label from '@/Components/Label.vue'
import Title from '@/Components/Title.vue'
import TextInput from '@/Components/TextInput.vue'
import SelectInput from '@/Components/SelectInput.vue'
import TextareaInput from '@/Components/TextareaInput.vue'
import Layout from '@/Layouts/Layout.vue';
import Form from "@/Components/Form.vue";
import {Head} from '@inertiajs/vue3'
import { Plus, Trash2, Check, X } from '@lucide/vue';


export default {
    components: {
        Form,
        TextInput,
        SelectInput,
        TextareaInput,
        Title,
        Label,
        Head,
        Layout,
        Plus,
        Trash2,
        Check,
        X
    },

    props: {
        roles: Array,
        errors: Object,
        permissions: Array
    },

    data() {
        return {
            form: this.$inertia.form({
                name: null,
                group: ' ',
                parent: null,
                guard_name: null
            }),
            sending: false,
            isDisabled: true,
            forms:[{
                name: '',
                group: '',
                parent: '',
                guard_name: ''
            }],
            counter: 0,
            alert: false,
            arr: []
        }
    },

    methods: {
        store() {
            this.arr = [];
            this.counter = 0;
            this.check();
            if(this.counter == 0 ){
                for(let i=0;i<this.forms.length;i++){
                    this.$inertia.post(('store'), this.forms[i], {
                        inline: 'default',
                        onStart: () => this.sending = true,
                        onSuccess: () => {
                            this.$toast.success(this.$page.props.toast.success.message);
                            this.sending = false;
                        },
                    })
                }
            }

        },
        check(){
            for(let i=0;i<this.forms.length;i++){
                if(this.forms[i].name == '' || this.forms[i].guard_name == ''){
                    this.arr.push(i+1);
                    this.counter++;
                    this.alert = true;
                }
            }
        },
        addForm(){
            this.forms.push({
                name: "",
                group: this.forms[0].group,
                parent: "",
                guard_name: this.forms[0].guard_name
            });
        },
        deleteForm(index){
            this.forms.splice(index,1)
        },
    },
    watch: {
        sending: {
            deep:true,
            handler() {
                setTimeout(()=> this.sending = false, 1000)
            }
        }
    }
};
</script>
