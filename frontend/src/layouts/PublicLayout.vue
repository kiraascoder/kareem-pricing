<script setup>
import { ref } from "vue";
import { RouterLink, RouterView } from "vue-router";

const menuOpen = ref(false);
</script>

<template>
  <div class="public-layout">
    <header class="navbar">
      <RouterLink to="/" class="logo"> KAREEM<span>.</span> </RouterLink>

      <button class="menu-toggle" @click="menuOpen = !menuOpen">☰</button>

      <nav :class="{ open: menuOpen }">
        <RouterLink to="/" @click="menuOpen = false"> Beranda </RouterLink>
        <RouterLink to="/about" @click="menuOpen = false"> Tentang </RouterLink>
        <RouterLink to="/packages" @click="menuOpen = false">
          Paket Event
        </RouterLink>
        <RouterLink to="/booking" @click="menuOpen = false">
          Booking
        </RouterLink>
        <RouterLink to="/contact" @click="menuOpen = false">
          Kontak
        </RouterLink>
        <RouterLink to="/login" class="login-btn"> Login </RouterLink>
      </nav>
    </header>

    <main>
      <RouterView />
    </main>

    <footer class="footer">
      <div>
        <h2>KAREEM<span>.</span></h2>
        <p>Exceptional Event Experiences</p>
      </div>

      <p>
        © {{ new Date().getFullYear() }}
        Kareem Entertainment.
      </p>
    </footer>
  </div>
</template>

<style scoped>
.public-layout {
  min-height: 100vh;
  background: #0b0b0d;
  color: #f5f5f4;
  font-family: Inter, sans-serif;
}

.navbar {
  min-height: 80px;
  padding: 0 7%;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #27272a;
  position: sticky;
  top: 0;
  z-index: 50;
  background: #0b0b0df5;
}

.logo {
  font-family: Cinzel, serif;
  font-size: 25px;
  font-weight: 700;
  color: #fff;
  text-decoration: none;
}

.logo span,
.footer span {
  color: #f97316;
}

nav {
  display: flex;
  align-items: center;
  gap: 28px;
}

nav a {
  color: #a1a1aa;
  font-size: 13px;
  text-decoration: none;
  transition: 0.2s;
}

nav a:hover,
nav a.router-link-exact-active {
  color: #f97316;
}

.login-btn {
  background: #f97316;
  color: #fff !important;
  padding: 11px 22px;
  border-radius: 5px;
}

.menu-toggle {
  display: none;
  border: none;
  background: transparent;
  color: white;
  font-size: 25px;
  cursor: pointer;
}

.footer {
  padding: 45px 7%;
  border-top: 1px solid #27272a;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 20px;
}

.footer h2 {
  font-family: Cinzel, serif;
}

.footer p {
  color: #71717a;
  font-size: 12px;
}

@media (max-width: 900px) {
  .menu-toggle {
    display: block;
  }

  nav {
    display: none;
    position: absolute;
    top: 80px;
    left: 0;
    right: 0;
    padding: 24px 7%;
    background: #18181b;
    flex-direction: column;
    align-items: stretch;
  }

  nav.open {
    display: flex;
  }

  .footer {
    flex-direction: column;
    text-align: center;
  }
}
</style>
