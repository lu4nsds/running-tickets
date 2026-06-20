<script setup>
import { computed } from "vue";
import { marked } from "marked";
import DOMPurify from "dompurify";

// Links abrem em nova aba com segurança (regulamento, mapas, etc.)
DOMPurify.addHook("afterSanitizeAttributes", (node) => {
    if (node.tagName === "A") {
        const href = node.getAttribute("href");
        // URL sem protocolo (ex.: "google.com") vira https:// para não ser
        // tratada como caminho relativo do app.
        if (href && !/^(https?:|mailto:|tel:|#)/i.test(href)) {
            node.setAttribute("href", `https://${href}`);
        }
        node.setAttribute("target", "_blank");
        node.setAttribute("rel", "noopener noreferrer");
    }
});

const props = defineProps({
    source: { type: String, default: "" },
});

const html = computed(() =>
    DOMPurify.sanitize(
        marked.parse(props.source ?? "", { gfm: true, breaks: true }),
        { ADD_ATTR: ["target", "rel"] }
    )
);
</script>

<template>
    <div
        class="markdown-content prose prose-invert prose-sm sm:prose-base max-w-none leading-relaxed prose-a:text-primary prose-a:break-words prose-headings:text-white prose-strong:text-white prose-img:rounded-lg"
        v-html="html"
    ></div>
</template>

<style scoped>
/* Listas: garante bullets/numeração e indentação independente do preflight do
   Tailwind (que zera list-style/padding) — mantém admin e client idênticos. */
.markdown-content :deep(ul),
.markdown-content :deep(ol) {
    margin-top: 1.25em;
    margin-bottom: 1.25em;
    padding-left: 1.625em;
}

.markdown-content :deep(ul) {
    list-style-type: disc;
}

.markdown-content :deep(ol) {
    list-style-type: decimal;
}

.markdown-content :deep(li) {
    margin-top: 0.5em;
    margin-bottom: 0.5em;
    padding-left: 0.375em;
}

.markdown-content :deep(table) {
    display: block;
    width: max-content;
    max-width: 100%;
    overflow-x: auto;
}

.markdown-content :deep(p),
.markdown-content :deep(li) {
    overflow-wrap: anywhere;
}

.markdown-content :deep(img) {
    max-width: 100%;
    height: auto;
}

/* Links sempre destacados e clicáveis */
.markdown-content :deep(a) {
    color: #00e676;
    text-decoration: underline;
    font-weight: 500;
}
</style>
