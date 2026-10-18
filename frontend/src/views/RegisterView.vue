<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";
import api from "../services/api";

const router = useRouter();
const loading = ref(false);
const error = ref("");

const form = ref({
  name: "",
  email: "",
  phone: "",
  password: "",
  password_confirmation: "",
});

async function register() {
  loading.value = true;
  error.value = "";

  try {
    await api.post("/auth/register", form.value);
    router.push("/login");
  } catch (err) {
    const errors = err.response?.data?.errors;

    error.value = errors
      ? Object.values(errors).flat().join(" ")
      : err.response?.data?.message || "Registrasi gagal.";
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <main class="auth-page">
    <section class="auth-card">
      <div class="brand">KAREEM<span>PRICING</span></div>

      <h1>Buat Akun</h1>
      <p>Daftar untuk memesan layanan event.</p>

      <form @submit.prevent="register">
        <label>Nama Lengkap</label>
        <input v-model="form.name" required maxlength="255" />

        <label>Email</label>
        <input v-model="form.email" type="email" required />

        <label>Nomor Telepon</label>
        <input v-model="form.phone" type="tel" required />

        <label>Password</label>
        <input v-model="form.password" type="password" minlength="8" required />

        <label>Konfirmasi Password</label>
        <input
          v-model="form.password_confirmation"
          type="password"
          minlength="8"
          required
        />

        <div v-if="error" class="error">{{ error }}</div>

        <button type="submit" :disabled="loading">
          {{ loading ? "Memproses..." : "Daftar" }}
        </button>
      </form>

      <p class="footer-text">
        Sudah punya akun?
        <RouterLink to="/login">Masuk</RouterLink>
      </p>
    </section>
  </main>
</template>
