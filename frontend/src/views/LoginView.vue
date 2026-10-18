
<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../services/api'

const router = useRouter()

const email = ref('')
const password = ref('')
const loading = ref(false)
const error = ref('')

async function login() {
  loading.value = true
  error.value = ''

  try {
    const response = await api.post('/auth/login', {
      email: email.value,
      password: password.value,
    })

    const payload = response.data.data

    if (!payload?.token || !payload?.user) {
      throw new Error('Respons login tidak lengkap.')
    }

    sessionStorage.setItem('kareem_token', payload.token)
    sessionStorage.setItem(
      'kareem_user',
      JSON.stringify(payload.user),
    )

    if (['admin', 'super_admin'].includes(payload.user.role)) {
      router.push('/admin')
    } else {
      router.push('/customer')
    }
  } catch (err) {
    error.value =
      err.response?.data?.message ||
      err.message ||
      'Login gagal.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <main class="auth-page">
    <section class="auth-card">
      <div class="brand">KAREEM<span>PRICING</span></div>

      <h1>Selamat Datang</h1>
      <p>Masuk untuk mengelola kebutuhan event Anda.</p>

      <form @submit.prevent="login">
        <label for="email">Email</label>
        <input
          id="email"
          v-model="email"
          type="email"
          autocomplete="email"
          placeholder="nama@email.com"
          required
        />

        <label for="password">Password</label>
        <input
          id="password"
          v-model="password"
          type="password"
          autocomplete="current-password"
          placeholder="Masukkan password"
          required
        />

        <div v-if="error" class="error">{{ error }}</div>

        <button type="submit" :disabled="loading">
          {{ loading ? 'Memproses...' : 'Masuk' }}
        </button>
      </form>

      <p class="footer-text">
        Belum punya akun?
        <RouterLink to="/register">Daftar sekarang</RouterLink>
      </p>
    </section>
  </main>
</template>
