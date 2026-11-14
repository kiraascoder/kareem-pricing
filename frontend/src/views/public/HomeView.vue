<script setup>
import { useRouter } from "vue-router";

const router = useRouter();

const services = [
  {
    title: "Wedding Events",
    description: "Perencanaan momen pernikahan yang berkesan.",
    icon: "✧",
  },
  {
    title: "Corporate Events",
    description: "Solusi event profesional untuk perusahaan.",
    icon: "◇",
  },
  {
    title: "Private Events",
    description: "Acara pribadi yang dirancang sesuai kebutuhan.",
    icon: "✦",
  },
];

function explorePackages() {
  router.push("/packages");
}
</script>

<template>
  <div>
    <section class="hero">
      <div class="hero-overlay"></div>

      <div class="hero-content">
        <div class="eyebrow">KAREEM ENTERTAINMENT</div>

        <h1>
          Crafting Moments,<br />
          <span>Creating Memories.</span>
        </h1>

        <p>
          Wujudkan event istimewa dengan perencanaan profesional, pilihan paket
          fleksibel, dan estimasi harga berbasis teknologi AI.
        </p>

        <div class="hero-actions">
          <button class="primary-btn" @click="explorePackages">
            Explore Packages →
          </button>

          <RouterLink to="/about" class="outline-btn">
            Discover More
          </RouterLink>
        </div>
      </div>
    </section>

    <section class="services">
      <div class="section-heading">
        <span>WHAT WE OFFER</span>
        <h2>Our Event Experiences</h2>
        <p>Pilihan layanan untuk berbagai kebutuhan event.</p>
      </div>

      <div class="service-grid">
        <div
          v-for="service in services"
          :key="service.title"
          class="service-card"
        >
          <div class="service-icon">
            {{ service.icon }}
          </div>

          <h3>{{ service.title }}</h3>
          <p>{{ service.description }}</p>
        </div>
      </div>
    </section>

    <section class="ai-section">
      <div>
        <span>SMART EVENT PLANNING</span>
        <h2>AI-Powered Pricing</h2>
        <p>
          Kareem Pricing membantu proses estimasi harga event berdasarkan
          informasi pemesanan. Penawaran akhir tetap melalui proses pemeriksaan
          oleh admin.
        </p>

        <RouterLink to="/booking" class="primary-btn">
          Start Your Booking →
        </RouterLink>
      </div>
    </section>

    <section class="cta">
      <span>YOUR MOMENT MATTERS</span>
      <h2>Ready to Create Something Extraordinary?</h2>
      <p>Jelajahi paket event dan mulai rencanakan acara Anda.</p>
      <RouterLink to="/packages" class="primary-btn">
        View Packages →
      </RouterLink>
    </section>
  </div>
</template>

<style scoped>
.hero {
  min-height: 650px;
  position: relative;
  display: flex;
  align-items: center;
  padding: 90px 8%;
  background:
    linear-gradient(90deg, #0b0b0df5, #0b0b0d88),
    url("https://images.unsplash.com/photo-1519167758481-83f550bb49b3?auto=format&fit=crop&w=1800&q=85")
      center/cover;
}

.hero-content {
  position: relative;
  max-width: 760px;
}

.eyebrow,
.section-heading span,
.ai-section span,
.cta span {
  color: #f97316;
  letter-spacing: 3px;
  font-size: 11px;
  font-weight: 700;
}

.hero h1 {
  margin: 24px 0;
  font-family: Cinzel, serif;
  font-size: clamp(38px, 5vw, 65px);
  line-height: 1.2;
}

.hero h1 span {
  color: #fb923c;
}

.hero p {
  max-width: 550px;
  color: #d4d4d8;
  font-size: 15px;
  line-height: 1.9;
}

.hero-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 15px;
  margin-top: 35px;
}

.primary-btn,
.outline-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 15px 25px;
  border-radius: 5px;
  text-decoration: none;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
}

.primary-btn {
  background: #f97316;
  color: #fff;
  border: 1px solid #f97316;
}

.outline-btn {
  background: transparent;
  color: #fff;
  border: 1px solid #52525b;
}

.services {
  padding: 100px 8%;
}

.section-heading {
  text-align: center;
  margin-bottom: 45px;
}

.section-heading h2,
.ai-section h2,
.cta h2 {
  font-family: Cinzel, serif;
  font-size: clamp(25px, 3vw, 37px);
}

.section-heading p,
.service-card p,
.ai-section p,
.cta p {
  color: #a1a1aa;
  line-height: 1.8;
  font-size: 13px;
}

.service-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 22px;
}

.service-card {
  padding: 35px;
  border: 1px solid #303036;
  background: #18181b;
  border-radius: 8px;
}

.service-icon {
  font-size: 36px;
  color: #f97316;
}

.service-card h3 {
  margin: 24px 0 12px;
  font-size: 17px;
}

.ai-section {
  padding: 100px 8%;
  background: #18181b;
}

.ai-section > div {
  max-width: 650px;
}

.ai-section p {
  margin-bottom: 30px;
}

.cta {
  padding: 100px 8%;
  text-align: center;
}

.cta p {
  margin-bottom: 30px;
}

@media (max-width: 800px) {
  .hero {
    min-height: 540px;
  }

  .service-grid {
    grid-template-columns: 1fr;
  }

  .services,
  .ai-section,
  .cta {
    padding: 65px 6%;
  }
}
</style>
