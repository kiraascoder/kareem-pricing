
<script setup>
import CustomerLayout from '../layouts/CustomerLayout.vue'

defineProps({
  title: { type: String, required: true },
})
</script>

<template>
  <CustomerLayout>
    <div class="roman-eyebrow">KAREEM PRICING</div>
    <h1 class="roman-title">{{ title }}</h1>
    <p style="color: #a1a1aa">
      Halaman ini akan diimplementasikan pada fase berikutnya.
    </p>
  </CustomerLayout>
</template>
