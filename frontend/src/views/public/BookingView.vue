<script setup>
import { computed } from "vue";
import { useRoute } from "vue-router";

const route = useRoute();

const packageId = computed(() => route.query.package_id || "");

const bookingPath = computed(() => ({
  path: "/customer/bookings/create",
  query: packageId.value ? { package_id: packageId.value } : {},
}));
</script>

<template>
  <section class="booking-page">
    <div class="content">
      <span>START YOUR EXPERIENCE</span>
      <h1>Book Your Event</h1>

      <p>
        Mulai pemesanan event melalui sistem Kareem Pricing. Anda perlu login
        atau membuat akun untuk melanjutkan booking.
      </p>

      <div class="actions">
        <RouterLink :to="bookingPath" class="primary">
          Lanjutkan Booking →
        </RouterLink>

        <RouterLink to="/register" class="secondary"> Buat Akun </RouterLink>
      </div>
    </div>
  </section>
</template>

<style scoped>
.booking-page {
  min-height: 70vh;
  display: grid;
  place-items: center;
  padding: 80px 8%;
  text-align: center;
}

.content {
  max-width: 650px;
}

span {
  color: #f97316;
  letter-spacing: 3px;
  font-size: 11px;
}

h1 {
  font-family: Cinzel, serif;
  font-size: clamp(35px, 5vw, 55px);
}

p {
  color: #a1a1aa;
  line-height: 1.9;
}

.actions {
  display: flex;
  justify-content: center;
  flex-wrap: wrap;
  gap: 15px;
  margin-top: 35px;
}

.actions a {
  padding: 15px 25px;
  border-radius: 5px;
  text-decoration: none;
  font-size: 13px;
}

.primary {
  background: #f97316;
  color: white;
}

.secondary {
  border: 1px solid #52525b;
  color: white;
}
</style>
