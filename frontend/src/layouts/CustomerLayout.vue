<script setup>
import { computed, ref } from "vue";
import { useRouter } from "vue-router";
import api from "../services/api";

const router = useRouter();
const sidebarOpen = ref(false);

const user = computed(() => {
  try {
    return JSON.parse(sessionStorage.getItem("kareem_user") || "{}");
  } catch {
    return {};
  }
});

const menus = [
  { label: "Dashboard", path: "/customer", icon: "◈" },
  { label: "Katalog Paket", path: "/customer/packages", icon: "◇" },
  { label: "Booking Saya", path: "/customer/bookings", icon: "▦" },
  { label: "Pembayaran", path: "/customer/payments", icon: "▣" },
];

async function logout() {
  try {
    await api.post("/auth/logout");
  } catch (error) {
    console.warn("Logout API:", error);
  } finally {
    sessionStorage.removeItem("kareem_token");
    sessionStorage.removeItem("kareem_user");
    router.replace("/login");
  }
}
</script>

<template>
  <div class="customer-shell">
    <aside class="customer-sidebar" :class="{ open: sidebarOpen }">
      <div class="brand">
        <div class="brand-logo">K</div>

        <div class="brand-info">
          <strong>KAREEM</strong>
          <small>EVENT EXPERIENCE</small>
        </div>
      </div>

      <div class="menu-label">CUSTOMER AREA</div>

      <nav aria-label="Navigasi customer">
        <RouterLink
          v-for="menu in menus"
          :key="menu.path"
          :to="menu.path"
          class="menu-link"
          active-class="active"
          @click="sidebarOpen = false"
        >
          <span class="menu-icon">{{ menu.icon }}</span>
          {{ menu.label }}
        </RouterLink>
      </nav>

      <div class="sidebar-footer">
        <div class="account">
          <div class="avatar">
            {{ (user.name || "C").charAt(0).toUpperCase() }}
          </div>

          <div class="account-info">
            <strong>{{ user.name || "Customer" }}</strong>
            <small>{{ user.email || "Customer Account" }}</small>
          </div>
        </div>

        <button class="logout-button" @click="logout">Keluar Akun</button>
      </div>
    </aside>

    <div
      v-if="sidebarOpen"
      class="sidebar-overlay"
      @click="sidebarOpen = false"
    ></div>

    <div class="customer-main">
      <header class="topbar">
        <div class="topbar-left">
          <button
            class="menu-toggle"
            aria-label="Buka menu customer"
            :aria-expanded="sidebarOpen"
            @click="sidebarOpen = !sidebarOpen"
          >
            ☰
          </button>

          <span>Customer Workspace</span>
        </div>

        <div class="topbar-right">
          <span>{{ user.name || "Customer" }}</span>
          <div class="avatar small">
            {{ (user.name || "C").charAt(0).toUpperCase() }}
          </div>
        </div>
      </header>

      <main class="customer-content">
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
  color: #f5f0e8;
}

.customer-sidebar {
  position: sticky;
  top: 0;
  display: flex;
  flex-direction: column;
  flex: 0 0 265px;
  height: 100vh;
  padding: 28px 16px;
  border-right: 1px solid #303036;
  background: #141416;
}

.brand {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 0 8px 38px;
}

.brand-logo {
  display: grid;
  place-items: center;
  width: 44px;
  height: 44px;
  border: 1px solid #f97316;
  border-radius: 11px;
  color: #f97316;
  font-family: "Cinzel", serif;
  font-size: 25px;
}

.brand-info strong {
  display: block;
  font-family: "Cinzel", serif;
  font-size: 18px;
  letter-spacing: 0.08em;
}

.brand-info small {
  display: block;
  margin-top: 5px;
  color: #a1a1aa;
  font-size: 9px;
  letter-spacing: 0.14em;
}

.menu-label {
  padding: 0 14px 14px;
  color: #71717a;
  font-size: 10px;
  letter-spacing: 0.15em;
}

nav {
  display: grid;
  gap: 6px;
}

.menu-link {
  display: flex;
  align-items: center;
  gap: 13px;
  padding: 14px;
  border-radius: 9px;
  color: #a1a1aa;
  font-size: 13px;
  text-decoration: none;
  transition: 0.2s;
}

.menu-link:hover {
  background: #252529;
  color: #fff;
}

.menu-link.active {
  background: #f973161c;
  color: #fb923c;
  font-weight: 700;
}

.menu-icon {
  width: 20px;
  text-align: center;
  font-size: 19px;
}

.sidebar-footer {
  margin-top: auto;
}

.account {
  display: flex;
  align-items: center;
  gap: 11px;
  padding: 16px 8px;
  border-top: 1px solid #303036;
}

.avatar {
  display: grid;
  place-items: center;
  flex-shrink: 0;
  width: 38px;
  height: 38px;
  border-radius: 50%;
  background: #f9731625;
  color: #fb923c;
  font-weight: 700;
}

.account-info {
  min-width: 0;
}

.account-info strong,
.account-info small {
  display: block;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.account-info strong {
  font-size: 12px;
}

.account-info small {
  margin-top: 5px;
  color: #a1a1aa;
  font-size: 10px;
}

.logout-button {
  width: 100%;
  padding: 12px;
  border: 1px solid #404047;
  border-radius: 9px;
  background: transparent;
  color: #d4d4d8;
  cursor: pointer;
}

.logout-button:hover {
  border-color: #f97316;
  color: #fb923c;
}

.customer-main {
  flex: 1;
  min-width: 0;
}

.topbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  min-height: 76px;
  padding: 0 32px;
  border-bottom: 1px solid #303036;
  background: #111113;
}

.topbar-left,
.topbar-right {
  display: flex;
  align-items: center;
  gap: 14px;
  font-size: 13px;
}

.topbar-right {
  color: #a1a1aa;
}

.avatar.small {
  width: 34px;
  height: 34px;
}

.customer-content {
  max-width: 1500px;
  margin: 0 auto;
  padding: 32px;
}

.menu-toggle,
.sidebar-overlay {
  display: none;
}

@media (max-width: 850px) {
  .customer-sidebar {
    position: fixed;
    z-index: 30;
    width: 265px;
    transform: translateX(-100%);
    transition: transform 0.25s;
  }

  .customer-sidebar.open {
    transform: translateX(0);
  }

  .sidebar-overlay {
    display: block;
    position: fixed;
    inset: 0;
    z-index: 20;
    background: #000a;
  }

  .menu-toggle {
    display: block;
    border: 0;
    background: transparent;
    color: #f97316;
    font-size: 22px;
    cursor: pointer;
  }

  .topbar {
    padding: 0 18px;
  }

  .customer-content {
    padding: 20px;
  }

  .topbar-right > span {
    display: none;
  }
}
</style>
