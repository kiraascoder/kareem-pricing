<script setup>
import AdminLayout from "../layouts/AdminLayout.vue";

defineProps({
  title: { type: String, required: true },
  description: { type: String, default: "" },
});
</script>

<template>
  <AdminLayout>
    <div class="roman-eyebrow">KAREEM ADMINISTRATION</div>
    <h1 class="roman-title">{{ title }}</h1>
    <p style="color: #a1a1aa; line-height: 1.8">
      {{ description }}
    </p>

    <section class="roman-card" style="padding: 30px; margin-top: 25px">
      Modul ini akan diintegrasikan dengan API Laravel pada tahap berikutnya.
    </section>
  </AdminLayout>
</template>
