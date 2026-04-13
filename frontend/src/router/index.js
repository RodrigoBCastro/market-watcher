import { createRouter, createWebHistory } from 'vue-router'

const TOKEN_KEY = 'marketwatcher.token'

const routes = [
  {
    path: '/',
    redirect: { name: 'dashboard' },
  },
  {
    path: '/login',
    name: 'login',
  },
  {
    path: '/dashboard',
    name: 'dashboard',
  },
  {
    path: '/portfolio',
    name: 'portfolio',
  },
  {
    path: '/risk',
    name: 'risk',
  },
  {
    path: '/performance',
    name: 'performance',
  },
  {
    path: '/alerts',
    name: 'alerts',
  },
  {
    path: '/assets',
    name: 'assets',
  },
  {
    path: '/assets/:ticker',
    name: 'asset-detail',
  },
  {
    path: '/briefs',
    name: 'briefs',
  },
  {
    path: '/calls',
    name: 'calls',
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: { name: 'dashboard' },
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to) => {
  const token = localStorage.getItem(TOKEN_KEY)

  if (to.name === 'login') {
    return true
  }

  if (!token) {
    return {
      name: 'login',
      query: { redirect: to.fullPath },
    }
  }

  return true
})

export default router
