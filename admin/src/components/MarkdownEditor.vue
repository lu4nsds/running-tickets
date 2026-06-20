<script setup>
import { ref, nextTick } from "vue";
import MarkdownContent from "@/components/MarkdownContent.vue";

const model = defineModel({ type: String, default: "" });

const props = defineProps({
    label: { type: String, default: "Descrição" },
    id: { type: String, default: "description" },
    placeholder: {
        type: String,
        default:
            "Descreva os detalhes do evento, percurso, premiação, etc. Use os botões acima para formatar.",
    },
    rows: { type: Number, default: 8 },
});

const showPreview = ref(false);
const textareaEl = ref(null);

const toolbar = [
    { icon: "format_bold", title: "Negrito", action: () => surround("**", "**", "negrito") },
    { icon: "format_italic", title: "Itálico", action: () => surround("*", "*", "itálico") },
    { icon: "title", title: "Título", action: () => prefixLines(() => "## ") },
    { icon: "format_list_bulleted", title: "Lista", action: () => prefixLines(() => "- ") },
    { icon: "format_list_numbered", title: "Lista numerada", action: () => prefixLines((i) => `${i + 1}. `) },
    { icon: "link", title: "Link", action: insertLink },
];

function setSelection(start, end) {
    nextTick(() => {
        const el = textareaEl.value;
        if (!el) return;
        el.focus({ preventScroll: true });
        el.setSelectionRange(start, end);
    });
}

// Envolve a seleção com marcadores (ex.: **negrito**). Sem seleção, insere o
// placeholder e o deixa selecionado para o usuário sobrescrever.
function surround(before, after, placeholder) {
    const el = textareaEl.value;
    if (!el) return;
    const { selectionStart: start, selectionEnd: end } = el;
    const value = model.value ?? "";
    const selected = value.slice(start, end) || placeholder;
    model.value =
        value.slice(0, start) + before + selected + after + value.slice(end);
    const innerStart = start + before.length;
    setSelection(innerStart, innerStart + selected.length);
}

// Prefixa cada linha abrangida pela seleção (títulos e listas).
function prefixLines(makePrefix) {
    const el = textareaEl.value;
    if (!el) return;
    const { selectionStart: start, selectionEnd: end } = el;
    const value = model.value ?? "";
    const lineStart = value.lastIndexOf("\n", start - 1) + 1;
    const nextBreak = value.indexOf("\n", end);
    const lineEnd = nextBreak === -1 ? value.length : nextBreak;
    const block = value.slice(lineStart, lineEnd);
    const newBlock = block
        .split("\n")
        .map((line, i) => makePrefix(i) + line)
        .join("\n");
    model.value = value.slice(0, lineStart) + newBlock + value.slice(lineEnd);
    setSelection(lineStart, lineStart + newBlock.length);
}

// Transforma a seleção em [texto](url) e posiciona o cursor na URL.
function insertLink() {
    const el = textareaEl.value;
    if (!el) return;
    const { selectionStart: start, selectionEnd: end } = el;
    const value = model.value ?? "";
    const text = value.slice(start, end) || "texto";
    const url = "https://";
    model.value =
        value.slice(0, start) + `[${text}](${url})` + value.slice(end);
    const urlStart = start + text.length + 3; // [text](
    setSelection(urlStart, urlStart + url.length);
}
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-2">
            <label
                :for="id"
                class="block text-xs font-medium text-text-muted uppercase tracking-wider"
            >
                {{ label }}
            </label>
            <div class="flex gap-1 text-xs">
                <button
                    type="button"
                    @click="showPreview = false"
                    :class="[
                        'px-3 py-1 rounded-md transition-colors',
                        !showPreview
                            ? 'bg-surface-elevated text-white'
                            : 'text-text-muted hover:text-white',
                    ]"
                >
                    Editar
                </button>
                <button
                    type="button"
                    @click="showPreview = true"
                    :class="[
                        'px-3 py-1 rounded-md transition-colors',
                        showPreview
                            ? 'bg-surface-elevated text-white'
                            : 'text-text-muted hover:text-white',
                    ]"
                >
                    Visualizar
                </button>
            </div>
        </div>

        <!-- Toolbar (só no modo edição) -->
        <div
            v-show="!showPreview"
            class="flex items-center gap-1 border border-surface-elevated border-b-0 rounded-t-lg bg-surface px-2 py-1.5"
        >
            <button
                v-for="item in toolbar"
                :key="item.icon"
                type="button"
                :title="item.title"
                :aria-label="item.title"
                @click="item.action"
                class="w-8 h-8 flex items-center justify-center rounded-md text-text-secondary hover:bg-surface-elevated hover:text-white transition-colors"
            >
                <span class="material-symbols-outlined text-[20px]">{{
                    item.icon
                }}</span>
            </button>
        </div>

        <textarea
            v-show="!showPreview"
            :id="id"
            ref="textareaEl"
            v-model="model"
            :rows="rows"
            :placeholder="placeholder"
            class="w-full bg-surface border border-surface-elevated rounded-b-lg px-4 py-3 text-white placeholder-text-muted focus:outline-none focus:border-primary transition-colors resize-y"
        ></textarea>

        <div
            v-show="showPreview"
            class="w-full min-h-[12rem] bg-surface border border-surface-elevated rounded-lg px-4 py-3"
        >
            <MarkdownContent v-if="model" :source="model" />
            <p v-else class="text-text-muted text-sm">
                Nada para visualizar ainda.
            </p>
        </div>

        <p class="text-text-muted text-xs mt-1">
            Selecione um trecho e use os botões para formatar. Suporta Markdown
            (negrito, listas, títulos e links).
        </p>
    </div>
</template>
