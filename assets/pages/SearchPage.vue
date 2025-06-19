<script setup>
import { ref, watch } from 'vue'

const search = ref('')
const cards = ref([])
const loadingCards = ref(false)
let debounceTimeout = null
let lastQuery = ''

//délai pour éviter les requêtes trop fréquentes
const DEBOUNCE_DELAY = 500
watch(search, (newVal) => {
    if (debounceTimeout) clearTimeout(debounceTimeout)
    if (newVal.length >= 3) {
        debounceTimeout = setTimeout(async () => {
            loadingCards.value = true
            lastQuery = newVal
            try {
                const res = await fetch(`/api/card/search?q=${encodeURIComponent(newVal)}`)
                if (!res.ok) throw new Error('Erreur lors de la recherche')
                // Ignore si la valeur a changé depuis le début de la requête
                if (search.value === newVal) {
                    cards.value = await res.json()
                }
            } catch (e) {
                cards.value = []
            }
            loadingCards.value = false
        }, DEBOUNCE_DELAY)
    } else {
        cards.value = []
    }
})
</script>

<template>
    <div>
        <h1>Rechercher une Carte</h1>
        <input v-model="search" placeholder="Nom de la carte..." />
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
