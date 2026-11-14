<template>
  <section class="public-page">
    <span>ABOUT KAREEM</span>
    <h1>Tentang Kami</h1>
    <p>
      Kareem Entertainment menghadirkan layanan perencanaan event yang membantu
      Anda menyelenggarakan acara sesuai kebutuhan.
    </p>
    <RouterLink to="/packages"> Jelajahi Paket Event → </RouterLink>
  </section>
</template>

<style scoped>
.public-page {
  min-height: 70vh;
  padding: 100px 8%;
  background: #0b0b0d;
  color: #fafaf9;
}

span {
  color: #f97316;
  letter-spacing: 3px;
  font-size: 12px;
}

h1 {
  font-family: Cinzel, serif;
  font-size: clamp(32px, 5vw, 52px);
}

p {
  max-width: 650px;
  color: #a1a1aa;
  line-height: 1.9;
}

a {
  display: inline-block;
  margin-top: 25px;
  color: #f97316;
  text-decoration: none;
}
</style>
