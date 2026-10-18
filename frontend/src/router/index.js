import { createRouter, createWebHistory } from "vue-router";

import LoginView from "../views/LoginView.vue";
import RegisterView from "../views/RegisterView.vue";
import CustomerView from "../views/CustomerView.vue";
import AdminView from "../views/AdminView.vue";

const routes = [
  { path: "/", redirect: "/login" },
  { path: "/login", component: LoginView },
  { path: "/register", component: RegisterView },
  {
    path: "/customer",
    component: CustomerView,
    meta: { requiresAuth: true, roles: ["customer"] },
  },
  {
    path: "/admin",
    component: AdminView,
    meta: {
      requiresAuth: true,
      roles: ["admin", "super_admin"],
    },
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach((to) => {
  if (!to.meta.requiresAuth) return true;

  const token = sessionStorage.getItem("kareem_token");
  const rawUser = sessionStorage.getItem("kareem_user");

  if (!token || !rawUser) return "/login";

  let user;

  try {
    user = JSON.parse(rawUser);
  } catch {
    return "/login";
  }

  if (!to.meta.roles.includes(user.role)) {
    return "/login";
  }

  return true;
});

export default router;
