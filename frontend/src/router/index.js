import { createRouter, createWebHistory } from "vue-router";

// Authentication
import LoginView from "../views/LoginView.vue";
import RegisterView from "../views/RegisterView.vue";

// Customer
import CustomerView from "../views/CustomerView.vue";
import CustomerSectionView from "../views/CustomerSectionView.vue";
import CustomerPackagesView from "../views/CustomerPackagesView.vue";
import CustomerBookingCreateView from "../views/CustomerBookingCreateView.vue";
import CustomerBookingsView from "../views/CustomerBookingsView.vue";
import CustomerBookingDetailView from "../views/CustomerBookingDetailView.vue";

// Admin
import AdminView from "../views/AdminView.vue";
import AdminSectionView from "../views/AdminSectionView.vue";

// Public Website
import PublicLayout from "../layouts/PublicLayout.vue";
import HomeView from "../views/public/HomeView.vue";

const customerMeta = {
  requiresAuth: true,
  roles: ["customer"],
};

const adminMeta = {
  requiresAuth: true,
  roles: ["admin", "super_admin"],
};

const routes = [
  // =========================================
  // PUBLIC WEBSITE
  // =========================================
  {
    path: "/",
    component: PublicLayout,
    children: [
      {
        path: "",
        name: "home",
        component: HomeView,
      },
      {
        path: "about",
        name: "about",
        component: () => import("../views/public/AboutView.vue"),
      },
      {
        path: "packages",
        name: "public-packages",
        component: () => import("../views/public/PackagesView.vue"),
      },
      {
        path: "booking",
        name: "public-booking",
        component: () => import("../views/public/BookingView.vue"),
      },
      {
        path: "contact",
        name: "contact",
        component: () => import("../views/public/ContactView.vue"),
      },
    ],
  },

  // =========================================
  // AUTHENTICATION
  // =========================================
  {
    path: "/login",
    name: "login",
    component: LoginView,
  },
  {
    path: "/register",
    name: "register",
    component: RegisterView,
  },

  // =========================================
  // CUSTOMER DASHBOARD
  // =========================================
  {
    path: "/customer",
    name: "customer-dashboard",
    component: CustomerView,
    meta: customerMeta,
  },
  {
    path: "/customer/packages",
    name: "customer-packages",
    component: CustomerPackagesView,
    meta: customerMeta,
  },
  {
    path: "/customer/bookings",
    name: "customer-bookings",
    component: CustomerBookingsView,
    meta: customerMeta,
  },
  {
    path: "/customer/bookings/create",
    name: "customer-booking-create",
    component: CustomerBookingCreateView,
    meta: customerMeta,
  },
  {
    path: "/customer/bookings/:id",
    name: "customer-booking-detail",
    component: CustomerBookingDetailView,
    meta: customerMeta,
  },
  {
    path: "/customer/payments",
    name: "customer-payments",
    component: CustomerSectionView,
    props: {
      title: "Pembayaran",
    },
    meta: customerMeta,
  },

  // =========================================
  // ADMIN DASHBOARD
  // =========================================
  {
    path: "/admin",
    name: "admin-dashboard",
    component: AdminView,
    meta: adminMeta,
  },
  {
    path: "/admin/bookings",
    name: "admin-bookings",
    component: AdminSectionView,
    props: {
      title: "Manajemen Booking",
      description: "Kelola permintaan booking dan penawaran harga.",
    },
    meta: adminMeta,
  },
  {
    path: "/admin/payments",
    name: "admin-payments",
    component: AdminSectionView,
    props: {
      title: "Verifikasi Pembayaran",
      description: "Periksa bukti transfer dari customer.",
    },
    meta: adminMeta,
  },
  {
    path: "/admin/packages",
    name: "admin-packages",
    component: AdminSectionView,
    props: {
      title: "Manajemen Paket Event",
      description: "Kelola paket dan kategori event.",
    },
    meta: adminMeta,
  },

  // =========================================
  // 404 NOT FOUND
  // =========================================
  {
    path: "/:pathMatch(.*)*",
    name: "not-found",
    redirect: "/",
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() {
    return { top: 0 };
  },
});

// =========================================
// AUTHENTICATION GUARD
// =========================================

router.beforeEach((to) => {
  if (!to.meta.requiresAuth) {
    return true;
  }

  const token = sessionStorage.getItem("kareem_token");
  const rawUser = sessionStorage.getItem("kareem_user");

  if (!token || !rawUser) {
    return {
      path: "/login",
      query: {
        redirect: to.fullPath,
      },
    };
  }

  let user;

  try {
    user = JSON.parse(rawUser);
  } catch {
    return {
      path: "/login",
      query: {
        redirect: to.fullPath,
      },
    };
  }

  const allowedRoles = to.meta.roles || [];

  if (!allowedRoles.includes(user.role)) {
    if (user.role === "customer") {
      return "/customer";
    }

    if (user.role === "admin" || user.role === "super_admin") {
      return "/admin";
    }

    return "/login";
  }

  return true;
});

export default router;
