
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
  <main class="login-page">
    <section class="login-shell">
      <div class="login-brand">
        <div class="brand-symbol">K</div>
        <div>
          <div class="roman-title brand-name">
            KAREEM <span>PRICING</span>
          </div>
          <small>PREMIUM EVENT PLATFORM</small>
        </div>
      </div>

      <div class="login-content">
        <div class="roman-eyebrow">
          WELCOME TO KAREEM
        </div>

        <h1 class="roman-title">Welcome Back.</h1>
        <p>
          Masuk untuk merencanakan event istimewa Anda.
        </p>

        <form @submit.prevent="login">
          <div class="field">
            <label for="email">Alamat Email</label>
            <input
              id="email"
              v-model="email"
              class="roman-input"
              type="email"
              autocomplete="email"
              placeholder="nama@email.com"
              required
            />
          </div>

          <div class="field">
            <label for="password">Password</label>
            <input
              id="password"
              v-model="password"
              class="roman-input"
              type="password"
              autocomplete="current-password"
              placeholder="Masukkan password"
              required
            />
          </div>

          <div v-if="error" class="login-error" role="alert">
            {{ error }}
          </div>

          <button
            type="submit"
            class="roman-button login-submit"
            :disabled="loading"
          >
            {{ loading ? 'Memproses...' : 'Masuk ke Dashboard →' }}
          </button>
        </form>

        <div class="login-footer">
          Belum memiliki akun?
          <RouterLink to="/register">Daftar sekarang</RouterLink>
        </div>
      </div>

      <div class="login-bottom">
        KAREEM ENTERTAINMENT · EST. EXCELLENCE
      </div>
    </section>
  </main>
</template>

<style scoped>
.login-page {
  min-height: 100vh;
  display: grid;
  place-items: center;
  padding: 24px;
  background:
    radial-gradient(
      ellipse at 50% 0%,
      #f9731615,
      transparent 55%
    ),
    #0b0b0d;
}

.login-shell {
  width: 100%;
  max-width: 470px;
  padding: 36px;
  border: 1px solid #37302b;
  border-radius: 18px;
  background: #151517;
  box-shadow: 0 24px 80px #0007;
}

.login-brand {
  display: flex;
  align-items: center;
  gap: 14px;
  padding-bottom: 28px;
  border-bottom: 1px solid #303036;
}

.brand-symbol {
  display: grid;
  place-items: center;
  width: 46px;
  height: 46px;
  border: 1px solid #f97316;
  border-radius: 11px;
  color: #f97316;
  font-family: 'Cinzel', serif;
  font-size: 27px;
}

.brand-name {
  font-size: 17px;
  font-weight: 700;
}

.brand-name span {
  color: #f97316;
}

.login-brand small {
  color: #a1a1aa;
  font-size: 9px;
  letter-spacing: .16em;
}

.login-content {
  padding: 38px 0 18px;
}

.login-content h1 {
  margin: 12px 0;
  font-size: clamp(26px, 5vw, 34px);
}

.login-content p {
  margin: 0 0 30px;
  color: #a1a1aa;
  font-size: 13px;
  line-height: 1.7;
}

.field {
  margin-bottom: 20px;
}

.field label {
  display: block;
  margin-bottom: 9px;
  font-size: 12px;
  font-weight: 600;
}

.login-submit {
  width: 100%;
  margin-top: 8px;
}

.login-error {
  padding: 12px;
  margin-bottom: 16px;
  border-radius: 8px;
  background: #7f1d1d40;
  color: #fca5a5;
  font-size: 12px;
}

.login-footer {
  margin-top: 27px;
  text-align: center;
  color: #a1a1aa;
  font-size: 12px;
}

.login-footer a {
  color: #fb923c;
  font-weight: 700;
}

.login-bottom {
  padding-top: 24px;
  border-top: 1px solid #303036;
  color: #71717a;
  font-size: 9px;
  letter-spacing: .17em;
  text-align: center;
}

@media (max-width: 480px) {
  .login-shell {
    padding: 24px;
  }
}
</style>

