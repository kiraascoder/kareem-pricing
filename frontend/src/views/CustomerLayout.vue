
<script setup>
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../services/api'

const router = useRouter()
const menuOpen = ref(false)

const user = computed(() => {
  try {
    return JSON.parse(
      sessionStorage.getItem('kareem_user') || '{}'
    )
  } catch {
    return {}
  }
})

const navigation = [
  { label: 'Dashboard', to: '/customer' },
  { label: 'Paket Event', to: '/customer/packages' },
  { label: 'Booking Saya', to: '/customer/bookings' },
  { label: 'Pembayaran', to: '/customer/payments' },
]

async function logout() {
  try {
    await api.post('/auth/logout')
  } catch (error) {
    console.warn('Logout API gagal:', error)
  } finally {
    sessionStorage.removeItem('kareem_token')
    sessionStorage.removeItem('kareem_user')
    router.replace('/login')
  }
}
</script>

<template>
  <div class="customer-shell">
    <aside class="sidebar" :class="{ open: menuOpen }">
      <div class="sidebar-brand">
        <div class="brand-icon">K</div>
        <div>
          <strong class="roman-title">
            KAREEM<span>PRICING</span>
          </strong>
          <small>EVENT MANAGEMENT</small>
        </div>
      </div>

      <div class="sidebar-label">MAIN MENU</div>

      <nav aria-label="Navigasi customer">
        <RouterLink
          v-for="item in navigation"
          :key="item.to"
          :to="item.to"
          class="nav-link"
          active-class="active"
          @click="menuOpen = false"
        >
          {{ item.label }}
        </RouterLink>
      </nav>

      <div class="sidebar-bottom">
        <button class="logout-button" @click="logout">
          Keluar Akun
        </button>
      </div>
    </aside>

    <div
      v-if="menuOpen"
      class="mobile-overlay"
      @click="menuOpen = false"
    />

    <div class="main-area">
      <header class="topbar">
        <button
          class="menu-toggle"
          aria-label="Buka navigasi"
          @click="menuOpen = !menuOpen"
        >
          ☰
        </button>

        <span class="topbar-title">Customer Workspace</span>

        <div class="user-profile">
          <div class="avatar">
            {{ (user.name || 'U').charAt(0).toUpperCase() }}
          </div>
          <span>{{ user.name || 'Customer' }}</span>
        </div>
      </header>

      <main class="page-content">
        <slot />
      </main>
    </div>
  </div>
</template>

<style scoped>
.customer-shell {
  display: flex;
  min-height: 100vh;
  background: #0b0b0d;
}

.sidebar {
  position: sticky;
  top: 0;
  display: flex;
  flex-direction: column;
  width: 260px;
  height: 100vh;
  flex-shrink: 0;
  padding: 28px 16px;
  border-right: 1px solid #303036;
  background: #141416;
}

.sidebar-brand {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 0 8px 40px;
}

.brand-icon {
  display: grid;
  place-items: center;
  width: 43px;
  height: 43px;
  border: 1px solid #f97316;
  border-radius: 10px;
  color: #f97316;
  font-family: 'Cinzel', serif;
  font-size: 24px;
}

.sidebar-brand strong {
  display: block;
  font-size: 12px;
}

.sidebar-brand strong span {
  color: #f97316;
}

.sidebar-brand small {
  display: block;
  margin-top: 5px;
  color: #71717a;
  font-size: 9px;
  letter-spacing: .1em;
}

.sidebar-label {
  padding: 0 15px 13px;
  color: #71717a;
  font-size: 10px;
  letter-spacing: .16em;
}

.sidebar nav {
  display: grid;
  gap: 6px;
}

.nav-link {
  display: block;
  padding: 14px 16px;
  border-radius: 9px;
  color: #a1a1aa;
  font-size: 13px;
  transition: .2s;
}

.nav-link:hover {
  background: #222226;
  color: #fff;
}

.nav-link.active {
  background: #f973161c;
  color: #fb923c;
  font-weight: 700;
}

.sidebar-bottom {
  margin-top: auto;
}

.logout-button {
  width: 100%;
  padding: 13px;
  border: 1px solid #39393f;
  border-radius: 9px;
  background: transparent;
  color: #d4d4d8;
}

.main-area {
  flex: 1;
  min-width: 0;
}

.topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 78px;
  padding: 0 32px;
  border-bottom: 1px solid #303036;
  background: #111113;
}

.topbar-title {
  font-size: 14px;
  font-weight: 600;
}

.user-profile {
  display: flex;
  align-items: center;
  gap: 10px;
  color: #d4d4d8;
  font-size: 12px;
}

.avatar {
  display: grid;
  place-items: center;
  width: 35px;
  height: 35px;
  border-radius: 50%;
  background: #f9731626;
  color: #fb923c;
  font-weight: 700;
}

.page-content {
  max-width: 1440px;
  margin: auto;
  padding: 32px;
}

.menu-toggle {
  display: none;
  border: 0;
  background: transparent;
  color: #f97316;
  font-size: 22px;
}

.mobile-overlay {
  display: none;
}

@media (max-width: 800px) {
  .sidebar {
    position: fixed;
    z-index: 30;
    transform: translateX(-100%);
    transition: transform .25s;
  }

  .sidebar.open {
    transform: translateX(0);
  }

  .mobile-overlay {
    display: block;
    position: fixed;
    inset: 0;
    z-index: 20;
    background: #0009;
  }

  .menu-toggle {
    display: block;
  }

  .topbar {
    padding: 0 18px;
  }

  .page-content {
    padding: 20px;
  }

  .user-profile span {
    display: none;
  }
}
</style>
