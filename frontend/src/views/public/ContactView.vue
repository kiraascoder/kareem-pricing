<template>
  <section class="contact-page">
    <span>GET IN TOUCH</span>
    <h1>Contact Us</h1>

    <p>
      Memiliki pertanyaan tentang paket event atau proses booking? Hubungi tim
      Kareem Entertainment melalui kanal komunikasi resmi.
    </p>

    <RouterLink to="/packages"> Lihat Paket Event → </RouterLink>
  </section>
</template>

<style scoped>
.contact-page {
  min-height: 70vh;
  padding: 100px 8%;
  color: #fafaf9;
}

span {
  color: #f97316;
  font-size: 11px;
  letter-spacing: 3px;
}

h1 {
  font-family: Cinzel, serif;
  font-size: 45px;
}

p {
  max-width: 600px;
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
