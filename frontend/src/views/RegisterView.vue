<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";
import api from "../services/api";

const router = useRouter();

const loading = ref(false);
const error = ref("");

const showPassword = ref(false);

const form = ref({
  name: "",
  email: "",
  phone: "",
  password: "",
  password_confirmation: "",
});

async function register() {
  if (loading.value) return;

  loading.value = true;
  error.value = "";

  try {
    await api.post("/auth/register", form.value);
    router.push("/login");
  } catch (err) {
    const errors = err.response?.data?.errors;

    error.value = errors
      ? Object.values(errors).flat().join(" ")
      : err.response?.data?.message ||
        "Registrasi gagal. Silakan coba kembali.";
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <main class="register-page">
    <div class="register-container">
      <section class="brand-panel">
        <div class="brand">
          <div class="brand-symbol">K</div>
          <div class="brand-name">KAREEM <span>PRICING</span></div>
        </div>

        <div class="brand-content">
          <div class="eyebrow">THE ART OF EVENT PLANNING</div>

          <h1>
            Every Great Event
            <em>Begins Here.</em>
          </h1>

          <p>
            Bergabung dengan Kareem Pricing dan mulai merencanakan event dengan
            estimasi harga yang lebih terukur melalui teknologi AI.
          </p>

          <div class="brand-line"></div>

          <div class="brand-features">
            <div>01 — Pilih paket event</div>
            <div>02 — Dapatkan estimasi harga AI</div>
            <div>03 — Kelola booking secara online</div>
          </div>
        </div>

        <div class="brand-bottom">KAREEM PRICING — EVENT MANAGEMENT</div>
      </section>

      <section class="form-panel">
        <div class="form-wrapper">
          <div class="form-heading">
            <div class="eyebrow">CREATE YOUR ACCOUNT</div>
            <h2>Buat Akun</h2>
            <p>
              Lengkapi informasi berikut untuk memulai perjalanan event Anda.
            </p>
          </div>

          <form @submit.prevent="register">
            <div class="field">
              <label for="name">Nama Lengkap</label>
              <input
                id="name"
                v-model.trim="form.name"
                type="text"
                maxlength="255"
                autocomplete="name"
                placeholder="Masukkan nama lengkap"
                required
              />
            </div>

            <div class="field">
              <label for="email">Alamat Email</label>
              <input
                id="email"
                v-model.trim="form.email"
                type="email"
                autocomplete="email"
                placeholder="nama@email.com"
                required
              />
            </div>

            <div class="field">
              <label for="phone">Nomor Telepon</label>
              <input
                id="phone"
                v-model.trim="form.phone"
                type="tel"
                autocomplete="tel"
                placeholder="08xxxxxxxxxx"
                required
              />
            </div>

            <div class="field">
              <label for="password">Password</label>
              <div class="password-wrapper">
                <input
                  id="password"
                  v-model="form.password"
                  :type="showPassword ? 'text' : 'password'"
                  minlength="8"
                  autocomplete="new-password"
                  placeholder="Minimal 8 karakter"
                  required
                />

                <button
                  type="button"
                  class="password-toggle"
                  @click="showPassword = !showPassword"
                >
                  {{ showPassword ? "Sembunyikan" : "Lihat" }}
                </button>
              </div>
            </div>

            <div class="field">
              <label for="confirmation"> Konfirmasi Password </label>
              <input
                id="confirmation"
                v-model="form.password_confirmation"
                :type="showPassword ? 'text' : 'password'"
                minlength="8"
                autocomplete="new-password"
                placeholder="Ulangi password"
                required
              />
            </div>

            <div v-if="error" class="error-message" role="alert">
              {{ error }}
            </div>

            <button type="submit" class="register-button" :disabled="loading">
              {{ loading ? "Memproses Registrasi..." : "Buat Akun →" }}
            </button>
          </form>

          <div class="login-link">
            Sudah memiliki akun?
            <RouterLink to="/login">Masuk Sekarang</RouterLink>
          </div>

          <div class="form-footer">© Kareem Pricing. All rights reserved.</div>
        </div>
      </section>
    </div>
  </main>
</template>

<style scoped>
.register-page {
  min-height: 100vh;
  background: #0b0b0d;
  color: #f5f0e8;
}

.register-container {
  display: grid;
  grid-template-columns: 1fr 1fr;
  min-height: 100vh;
}

.brand-panel {
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 45px 60px;
  border-right: 1px solid #303036;
  background:
    radial-gradient(circle at 50% 65%, #f973161c, transparent 55%),
    linear-gradient(145deg, #1b1b1f, #0b0b0d);
}

.brand {
  display: flex;
  align-items: center;
  gap: 14px;
}

.brand-symbol {
  display: grid;
  place-items: center;
  width: 45px;
  height: 45px;
  border: 1px solid #f97316;
  border-radius: 10px;
  color: #fb923c;
  font-family: "Cinzel", serif;
  font-size: 25px;
}

.brand-name {
  font-family: "Cinzel", serif;
  font-size: 17px;
  font-weight: 700;
  letter-spacing: 0.08em;
}

.brand-name span {
  color: #fb923c;
}

.brand-content {
  max-width: 480px;
}

.eyebrow {
  color: #f97316;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.2em;
}

.brand-content h1 {
  margin: 22px 0;
  font-family: "Cinzel", serif;
  font-size: clamp(32px, 4vw, 52px);
  line-height: 1.3;
}

.brand-content h1 em {
  display: block;
  color: #fb923c;
  font-style: normal;
}

.brand-content p {
  max-width: 410px;
  color: #a1a1aa;
  font-size: 13px;
  line-height: 2;
}

.brand-line {
  width: 65px;
  height: 2px;
  margin: 30px 0;
  background: #f97316;
}

.brand-features {
  display: grid;
  gap: 18px;
  color: #d4d4d8;
  font-size: 12px;
}

.brand-bottom {
  color: #71717a;
  font-size: 10px;
  letter-spacing: 0.15em;
}

.form-panel {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 45px 35px;
}

.form-wrapper {
  width: 100%;
  max-width: 410px;
}

.form-heading {
  margin-bottom: 32px;
}

.form-heading h2 {
  margin: 12px 0;
  font-family: "Cinzel", serif;
  font-size: 30px;
}

.form-heading p {
  color: #a1a1aa;
  font-size: 13px;
  line-height: 1.8;
}

.field {
  margin-bottom: 19px;
}

.field label {
  display: block;
  margin-bottom: 9px;
  color: #d4d4d8;
  font-size: 12px;
  font-weight: 600;
}

.field input {
  width: 100%;
  min-height: 46px;
  padding: 12px 14px;
  border: 1px solid #38383d;
  border-radius: 8px;
  outline: none;
  background: #18181b;
  color: #f5f0e8;
  font-family: inherit;
  font-size: 13px;
  box-sizing: border-box;
}

.field input::placeholder {
  color: #71717a;
}

.field input:focus {
  border-color: #f97316;
  box-shadow: 0 0 0 3px #f9731615;
}

.password-wrapper {
  position: relative;
}

.password-wrapper input {
  padding-right: 100px;
}

.password-toggle {
  position: absolute;
  top: 50%;
  right: 12px;
  transform: translateY(-50%);
  border: 0;
  background: transparent;
  color: #fb923c;
  font-size: 11px;
  cursor: pointer;
}

.error-message {
  margin-bottom: 20px;
  padding: 13px;
  border: 1px solid #7f1d1d;
  border-radius: 8px;
  background: #ef44440a;
  color: #fca5a5;
  font-size: 12px;
  line-height: 1.7;
}

.register-button {
  width: 100%;
  min-height: 48px;
  margin-top: 6px;
  border: 0;
  border-radius: 9px;
  background: #f97316;
  color: #111113;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  transition: 0.2s;
}

.register-button:hover {
  background: #fb923c;
}

.register-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.login-link {
  margin-top: 25px;
  color: #a1a1aa;
  font-size: 12px;
  text-align: center;
}

.login-link a {
  margin-left: 5px;
  color: #fb923c;
  font-weight: 700;
  text-decoration: none;
}

.form-footer {
  margin-top: 55px;
  color: #71717a;
  font-size: 10px;
  text-align: center;
}

@media (max-width: 900px) {
  .register-container {
    grid-template-columns: 1fr;
  }

  .brand-panel {
    padding: 25px;
  }

  .brand-content,
  .brand-bottom {
    display: none;
  }

  .form-panel {
    padding: 35px 20px;
  }
}
</style>
