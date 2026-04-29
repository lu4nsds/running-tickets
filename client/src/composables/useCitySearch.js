import { ref } from 'vue'

let cache = null
let promise = null

async function loadMunicipios() {
    if (cache) return cache
    if (!promise) {
        promise = fetch('https://servicodados.ibge.gov.br/api/v1/localidades/municipios')
            .then(r => r.json())
            .then(data => {
                cache = data.map(m => m.nome).sort()
                return cache
            })
    }
    return promise
}

function normalize(str) {
    return str.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase()
}

export function useCitySearch() {
    const suggestions = ref([])
    const isLoading = ref(false)

    async function search(query) {
        if (!query || query.length < 2) {
            suggestions.value = []
            return
        }
        isLoading.value = true
        try {
            const all = await loadMunicipios()
            const q = normalize(query)
            suggestions.value = all
                .filter(name => normalize(name).startsWith(q))
                .slice(0, 3)
        } finally {
            isLoading.value = false
        }
    }

    function clear() {
        suggestions.value = []
    }

    return { suggestions, isLoading, search, clear }
}
