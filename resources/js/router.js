import { createRouter, createWebHistory } from 'vue-router'

// === Layouts ===
import AuthLayout from '@/components/AuthLayout.vue'
import AdminLayout from '@/components/AdminLayout.vue'
import UserLayout from '@/components/UserLayout.vue'

// === Vistas públicas ===
import Login from '@/views/Login.vue'

// === Vistas de administrador ===
import AdminDashboard from '@/views/admin/AdminDashboard.vue'
import AdminLibrosListar from '@/views/admin/AdminLibrosListar.vue'
import AdminLibrosCrear from '@/views/admin/AdminLibrosCrear.vue'
import AdminLibrosEditar from '@/views/admin/AdminLibrosEditar.vue'
import AdminCategorias from '@/views/admin/AdminCategorias.vue'
import AdminVentas from '@/views/admin/AdminVentas.vue'
import AdminPerfil from '@/views/admin/AdminPerfil.vue'
import AdminRegister from '@/views/admin/AdminRegister.vue'
import AdminListarUsuarios from '@/views/admin/AdminListarUsuarios.vue'

// === Vistas de usuario normal ===
import UserHome from '@/views/user/Home.vue'
import UserCompras from '@/views/user/Compras.vue'
import UserCheckout from '@/views/user/Checkout.vue' // ✅ AGREGAR ESTA IMPORTACIÓN

// === Definición de rutas ===
const routes = [
  // 🔹 Autenticación (login/register)
  {
    path: '/',
    component: AuthLayout,
    children: [
      { path: '', redirect: '/login' },
      { path: 'login', name: 'Login', component: Login },
    ],
  },

  // 🔹 Panel del administrador
  {
    path: '/admin',
    component: AdminLayout,
    meta: { requiresAuth: true, role: 'admin' },
    children: [
      { path: 'dashboard', name: 'AdminDashboard', component: AdminDashboard },
      { path: 'libros/listar', name: 'AdminLibrosListar', component: AdminLibrosListar },
      { path: 'libros/crear', name: 'AdminLibrosCrear', component: AdminLibrosCrear },
      { path: 'libros/editar/:id?', name: 'AdminLibrosEditar', component: AdminLibrosEditar },
      { path: 'categorias', name: 'AdminCategorias', component: AdminCategorias },
      { path: 'ventas', name: 'AdminVentas', component: AdminVentas },
      { path: 'perfil', name: 'AdminPerfil', component: AdminPerfil },
      { path: 'register', name: 'AdminRegister', component: AdminRegister },
      { path: 'listar/usuarios', name: 'AdminListarUsuarios', component: AdminListarUsuarios },
    ],
  },

  // 🔹 Panel del usuario normal
  {
    path: '/users',
    component: UserLayout,
    meta: { requiresAuth: true, role: 'user' }, // ✅ AGREGAR meta para proteger rutas
    children: [
      { 
        path: 'home', 
        name: 'UserHome', 
        component: UserHome 
      },
      { 
        path: 'compras', 
        name: 'UserCompras', 
        component: UserCompras 
      },
      // ✅ AGREGAR RUTA DE CHECKOUT
      { 
        path: 'checkout/:id', 
        name: 'UserCheckout', 
        component: UserCheckout,
        props: true // Pasar el ID como prop
      }
    ],
  },

  // 🔹 Cualquier ruta no encontrada → login
  { path: '/:pathMatch(.*)*', redirect: '/login' },
]

// === Configuración del router ===
const router = createRouter({
  history: createWebHistory(),
  routes,
})

// =============================
// 🔒 Protección de rutas
// =============================
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token')
  const user = localStorage.getItem('user') ? JSON.parse(localStorage.getItem('user')) : null

  // 🚫 Sin token → redirigir al login
  if (to.meta.requiresAuth && !token) {
    return next('/login')
  }

  // 🔁 Ya logueado → evitar volver al login/register
  if ((to.path === '/login' || to.path === '/register') && token && user) {
    return next(user.role === 'admin' ? '/admin/dashboard' : '/users/home')
  }

  // 🔐 Rol incorrecto → redirigir a su panel
  if (to.meta.role && user && to.meta.role !== user.role) {
    return next(user.role === 'admin' ? '/admin/dashboard' : '/users/home')
  }

  next()
})

export default router