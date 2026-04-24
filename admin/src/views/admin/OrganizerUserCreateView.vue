<template>
    <div class="space-y-6">
        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-sm">
            <router-link
                to="/admin/organizers"
                class="text-primary hover:underline"
            >
                Organizadores
            </router-link>
            <span class="text-text-muted">/</span>
            <router-link
                :to="`/admin/organizers/${organizerId}`"
                class="text-primary hover:underline"
            >
                {{ organizerName }}
            </router-link>
            <span class="text-text-muted">/</span>
            <span class="text-text-muted">Adicionar Usuário</span>
        </nav>

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-white mb-1">
                    Adicionar Novo Usuário
                </h1>
                <p class="text-text-muted">
                    Vincule um novo administrador ou membro da equipe para esta
                    organização.
                </p>
            </div>
            <button
                @click="goBack"
                class="flex items-center gap-2 px-4 py-2 border border-surface-elevated text-text-muted rounded-lg hover:bg-surface hover:text-white transition-colors"
            >
                <span class="material-symbols-outlined text-[20px]"
                    >arrow_back</span
                >
                Voltar
            </button>
        </div>

        <!-- Form Card -->
        <div
            class="max-w-[800px] mx-auto bg-card-bg border border-surface-elevated rounded-xl overflow-hidden relative"
        >
            <!-- Top gradient line -->
            <div
                class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-transparent via-primary/50 to-transparent"
            ></div>

            <form @submit.prevent="handleSubmit" class="p-8 space-y-8">
                <!-- Nome Completo -->
                <div class="space-y-2">
                    <label
                        for="name"
                        class="block text-sm font-medium text-text-muted"
                    >
                        Nome Completo
                    </label>
                    <div class="relative">
                        <div
                            class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
                        >
                            <span
                                class="material-symbols-outlined text-text-muted"
                                >person</span
                            >
                        </div>
                        <input
                            id="name"
                            v-model="form.name"
                            type="text"
                            placeholder="Ex: Ana Souza"
                            class="block w-full pl-10 pr-3 py-3 bg-surface border border-surface-elevated rounded-lg text-white placeholder-text-muted focus:outline-none focus:border-primary transition-all"
                            :class="{ 'border-red-500': errors.name }"
                        />
                    </div>
                    <p v-if="errors.name" class="text-xs text-red-500">
                        {{ errors.name[0] }}
                    </p>
                </div>

                <!-- E-mail -->
                <div class="space-y-2">
                    <label
                        for="email"
                        class="block text-sm font-medium text-text-muted"
                    >
                        E-mail
                    </label>
                    <div class="relative">
                        <div
                            class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
                        >
                            <span
                                class="material-symbols-outlined text-text-muted"
                                >email</span
                            >
                        </div>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            placeholder="Ex: ana.souza@email.com"
                            class="block w-full pl-10 pr-3 py-3 bg-surface border border-surface-elevated rounded-lg text-white placeholder-text-muted focus:outline-none focus:border-primary transition-all"
                            :class="{ 'border-red-500': errors.email }"
                        />
                    </div>
                    <p v-if="errors.email" class="text-xs text-red-500">
                        {{ errors.email[0] }}
                    </p>
                </div>

                <!-- Cargo (Permissões) -->
                <div class="space-y-3">
                    <label class="block text-sm font-medium text-text-muted">
                        Cargo (Permissões)
                    </label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Admin Option -->
                        <label class="cursor-pointer group">
                            <input
                                type="radio"
                                v-model="form.role"
                                value="admin"
                                class="peer sr-only"
                            />
                            <div
                                class="p-4 rounded-lg bg-surface border border-surface-elevated peer-checked:border-primary peer-checked:border-2 transition-all h-full flex flex-col hover:border-text-muted"
                                :class="{
                                    'shadow-[0_0_20px_rgba(0,230,118,0.2)]':
                                        form.role === 'admin',
                                }"
                            >
                                <div
                                    class="flex items-center justify-between mb-2"
                                >
                                    <span
                                        class="flex items-center gap-2 font-bold text-white group-hover:text-primary transition-colors"
                                    >
                                        <span
                                            class="material-symbols-outlined text-primary"
                                            >admin_panel_settings</span
                                        >
                                        Admin
                                    </span>
                                    <div
                                        class="w-5 h-5 rounded-full border flex items-center justify-center transition-all"
                                        :class="
                                            form.role === 'admin'
                                                ? 'border-primary bg-primary'
                                                : 'border-text-muted'
                                        "
                                    >
                                        <div
                                            v-if="form.role === 'admin'"
                                            class="w-2.5 h-2.5 rounded-full bg-black"
                                        ></div>
                                    </div>
                                </div>
                                <p
                                    class="text-xs text-text-muted leading-relaxed"
                                >
                                    Acesso total às configurações da
                                    organização, eventos e financeiro.
                                </p>
                            </div>
                        </label>

                        <!-- Staff Option -->
                        <label class="cursor-pointer group">
                            <input
                                type="radio"
                                v-model="form.role"
                                value="staff"
                                class="peer sr-only"
                            />
                            <div
                                class="p-4 rounded-lg bg-surface border border-surface-elevated peer-checked:border-primary peer-checked:border-2 transition-all h-full flex flex-col hover:border-text-muted"
                                :class="{
                                    'shadow-[0_0_20px_rgba(0,230,118,0.2)]':
                                        form.role === 'staff',
                                }"
                            >
                                <div
                                    class="flex items-center justify-between mb-2"
                                >
                                    <span
                                        class="flex items-center gap-2 font-bold text-white group-hover:text-primary transition-colors"
                                    >
                                        <span
                                            class="material-symbols-outlined"
                                            :class="
                                                form.role === 'staff'
                                                    ? 'text-primary'
                                                    : 'text-text-muted'
                                            "
                                            >badge</span
                                        >
                                        Staff
                                    </span>
                                    <div
                                        class="w-5 h-5 rounded-full border flex items-center justify-center transition-all"
                                        :class="
                                            form.role === 'staff'
                                                ? 'border-primary bg-primary'
                                                : 'border-text-muted'
                                        "
                                    >
                                        <div
                                            v-if="form.role === 'staff'"
                                            class="w-2.5 h-2.5 rounded-full bg-black"
                                        ></div>
                                    </div>
                                </div>
                                <p
                                    class="text-xs text-text-muted leading-relaxed"
                                >
                                    Acesso restrito. Pode visualizar dados
                                    básicos e realizar check-in.
                                </p>
                            </div>
                        </label>
                    </div>
                    <p v-if="errors.role" class="text-xs text-red-500">
                        {{ errors.role[0] }}
                    </p>
                </div>

                <!-- Aviso de ativação -->
                <div class="flex items-start gap-3 p-4 bg-primary/5 border border-primary/20 rounded-lg">
                    <span class="material-symbols-outlined text-primary text-[20px] mt-0.5 shrink-0">mark_email_unread</span>
                    <p class="text-sm text-text-muted">
                        Um e-mail de ativação será enviado ao usuário com um link para ele definir a própria senha.
                        O link expira em <strong class="text-white">48 horas</strong>.
                    </p>
                </div>
            </form>

            <!-- Footer -->
            <div
                class="px-8 py-6 bg-surface/30 border-t border-surface-elevated flex justify-end items-center gap-4"
            >
                <button
                    type="button"
                    @click="goBack"
                    class="px-6 py-2.5 text-sm font-medium text-text-muted hover:text-white transition-colors"
                >
                    Cancelar
                </button>
                <button
                    @click="handleSubmit"
                    :disabled="isSubmitting"
                    class="px-6 py-2.5 bg-primary text-black font-bold rounded-lg hover:brightness-110 transition-all flex items-center gap-2 shadow-[0_0_20px_rgba(0,230,118,0.4)] hover:shadow-[0_0_30px_rgba(0,230,118,0.6)] disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span v-if="isSubmitting" class="animate-spin">
                        <span class="material-symbols-outlined text-lg"
                            >progress_activity</span
                        >
                    </span>
                    <span v-else class="material-symbols-outlined text-lg"
                        >link</span
                    >
                    Vincular Usuário
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useToast } from "@/composables/useToast";
import api from "@/api/axios";
import { useOrganizersStore } from "@/stores/organizers";

const route = useRoute();
const router = useRouter();
const toast = useToast();
const organizersStore = useOrganizersStore();

const organizerId = route.params.id;
const organizerName = ref("Carregando...");

const form = ref({
    name: "",
    email: "",
    role: "staff",
});

const errors = ref({});
const isSubmitting = ref(false);

onMounted(async () => {
    const result = await organizersStore.fetchOrganizer(organizerId);
    if (result.success) {
        organizerName.value = result.data.name;
    } else {
        toast.error("Erro ao carregar organizador");
        router.push("/admin/organizers");
    }
});

const goBack = () => {
    router.push(`/admin/organizers/${organizerId}`);
};

const handleSubmit = async () => {
    errors.value = {};

    // Frontend validation
    if (!form.value.name.trim()) {
        errors.value.name = ["O nome é obrigatório"];
    }
    if (!form.value.email.trim()) {
        errors.value.email = ["O e-mail é obrigatório"];
    }

    if (Object.keys(errors.value).length > 0) {
        return;
    }

    isSubmitting.value = true;

    try {
        const response = await api.post(
            `/admin/organizers/${organizerId}/users`,
            {
                name: form.value.name,
                email: form.value.email,
                role: form.value.role,
            },
        );

        toast.success("Usuário vinculado com sucesso!");
        router.push(`/admin/organizers/${organizerId}`);
    } catch (err) {
        if (err.response?.status === 422) {
            if (err.response.data.errors) {
                errors.value = err.response.data.errors;
            } else {
                toast.error(err.response.data.message || "Erro de validação");
            }
        } else {
            toast.error(
                err.response?.data?.message || "Erro ao vincular usuário",
            );
        }
    } finally {
        isSubmitting.value = false;
    }
};
</script>
