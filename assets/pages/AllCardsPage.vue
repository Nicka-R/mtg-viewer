<script setup>
import { ref } from 'vue'

const cards = ref([])
const loadingCards = ref(true)
const page = ref(1)
const limit = 100
const totalPages = ref(1)

async function loadCards() {
    loadingCards.value = true
    const res = await fetch(`/api/card/all?page=${page.value}&limit=${limit}`)
    const result = await res.json()
    cards.value = result.data
    totalPages.value = result.pages
    loadingCards.value = false
}

function goToPage(p) {
    if (p >= 1 && p <= totalPages.value) {
        page.value = p
        loadCards()
    }
}

loadCards()
</script>

<template>
    <div>
        <h1>Toutes les cartes</h1>
        <div class="pagination">
            <button @click="goToPage(page - 1)" :disabled="page === 1">Précédent</button>
            <span>Page {{ page }} / {{ totalPages }}</span>
            <button @click="goToPage(page + 1)" :disabled="page === totalPages">Suivant</button>
        </div>
    </div>
    <div class="card-list">
        <div v-if="loadingCards">Loading...</div>
        <div v-else>
            <div class="card-result" v-for="card in cards" :key="card.id">
                <router-link :to="{ name: 'get-card', params: { uuid: card.uuid } }">
                    {{ card.name }} <span>({{ card.uuid }})</span>
                </router-link>
            </div>
        </div>
    </div>
</template>