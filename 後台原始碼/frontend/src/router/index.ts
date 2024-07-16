import { createRouter, createWebHistory } from "vue-router";

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
  	{ path: '/', component: () => import('../views/Dashboard.vue') },
    { path: '/cronjobs', component: () => import('../views/Cronjob.vue') },
  	{ path: '/records', component: () => import('../views/Records.vue') },
    { path: '/members', component: () => import('../views/Members.vue') },
    { path: '/workshops', component: () => import('../views/Workshops.vue') },
    { path: '/login', component: () => import('../views/PageLogin.vue') },
    { path: '/logout', component: () => import('../views/PageLogout.vue') },
    { path: '/:pathMatch(.*)*', component: () => import('../views/PageError.vue') }
   
  ],
});

export default router;
