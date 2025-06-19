<script setup>
import { ref, watch, onMounted } from 'vue'

const search = ref('')
const cards = ref([])
const loadingCards = ref(false)
const setCodes = ref([])
const selectedSetCode = ref('')

let debounceTimeout = null

onMounted(async () => {
    // charge les setCodes au montage
    const res = await fetch('/api/card/setcodes')
    setCodes.value = await res.json()
})

watch([search, selectedSetCode], ([newVal, newSetCode]) => {
    if (debounceTimeout) clearTimeout(debounceTimeout)
    if (newVal.length >= 3) {
        debounceTimeout = setTimeout(async () => {
            loadingCards.value = true
            let url = `/api/card/search?q=${encodeURIComponent(newVal)}`
            if (newSetCode) url += `&setCode=${encodeURIComponent(newSetCode)}`
            try {
                const res = await fetch(url)
                if (!res.ok) throw new Error('Erreur lors de la recherche')
                if (search.value === newVal && selectedSetCode.value === newSetCode) {
                    cards.value = await res.json()
                }
            } catch (e) {
                cards.value = []
            }
            loadingCards.value = false
        }, 300)
    } else {
        cards.value = []
    }
})
</script>

<template>
    <div>
        <h1>Rechercher une Carte</h1>
        <input v-model="search" placeholder="Nom de la carte..." />
        <select v-model="selectedSetCode">
            <option value="">Tous les sets</option>
            <option v-for="code in setCodes" :key="code" :value="code">{{ code }}</option>
        </select>
    </div>
    <div class="card-list">
        <div v-if="loadingCards">Loading...</div>
        <div v-else>
            <div class="card" v-for="card in cards" :key="card.uuid">
                <router-link :to="{ name: 'get-card', params: { uuid: card.uuid } }">
                    {{ card.name }} - {{ card.uuid }}
                </router-link>
            </div>
        </div>
    </div>
</template>