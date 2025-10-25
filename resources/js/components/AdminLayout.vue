<template>
  <div class="layout-admin d-flex flex-column min-vh-100 bg-light">
    <!-- HEADER -->
    <header class="header shadow-sm d-flex justify-content-between align-items-center px-3">
      <div class="d-flex align-items-center gap-2">
        <!-- Botón menú -->
        <button class="burger-btn" @click="toggleMenu" aria-label="Toggle menu">
          <i class="bi bi-list"></i>
        </button>
        <h4><i class="bi bi-book-fill me-2"></i>Librería Digital</h4>
      </div>

      <!-- PERFIL CON AVATAR -->
      <div class="dropdown ">
        <a href="#" class="nav-link text-white d-flex align-items-center gap-2" @click.prevent="togglePerfil">
          <img 
            :src="userAvatar" 
            :alt="userName"
            class="avatar-header"
          />
          <span class="c">{{ userName }}</span>
          <!-- Ícono que cambia según el estado -->
          <i :class="perfilOpen ? 'bi bi-emoji-laughing-fill' : '<bi bi-emoji-astonished-fill'" class="ms-1 perfil-icon"></i>
        </a>
        <ul v-show="perfilOpen" class="dropdown-menu dropdown-menu-end show">
          <li>
            <RouterLink class="dropdown-item" to="/admin/perfil" @click="perfilOpen = false">
              <i class="bi bi-person-circle"></i> Editar Perfil
            </RouterLink>
          </li>
          <li>
            <button class="dropdown-item text-danger" @click="logout">
              <i class="bi bi-door-open-fill"></i> Cerrar Sesión
            </button>
          </li>
        </ul>
      </div>
    </header>

    <!-- SIDEBAR -->
    <aside :class="['sidebar', { active: menuOpen }]">
      <div class="sidebar-header text-center py-3 border-bottom">
        <h5 class="mb-0"><i class="bi bi-inboxes"></i> Panel Admin</h5>
      </div>
      <nav class="nav flex-column admin-nav mt-3">
        <span class="sidebar-title px-3 mb-2">
          <i class="bi bi-gear-wide-connected"></i> ADMINISTRACIÓN
        </span>
        
        <RouterLink class="sidebar-link" to="/admin/dashboard" @click="closeMenu">
          <i class="bi bi-speedometer2"></i> Dashboard
        </RouterLink>

        <!-- Libros -->
        <div class="sidebar-dropdown">
          <a href="#" class="sidebar-link dropdown-toggle" @click.prevent="toggleLibros">
            <i class="bi bi-book-half"></i> Libros
            <i :class="['bi', librosOpen ? 'bi-chevron-down' : 'bi-chevron-right', 'ms-auto']"></i>
          </a>
          <div v-show="librosOpen" class="submenu">
            <RouterLink class="sidebar-link sub-link" to="/admin/libros/listar" @click="closeMenu">
              <i class="bi bi-journals"></i> Listar libros
            </RouterLink>
            <RouterLink class="sidebar-link sub-link" to="/admin/libros/crear" @click="closeMenu">
              <i class="bi bi-journal-arrow-up"></i> Subir libros
            </RouterLink>
            <RouterLink class="sidebar-link sub-link" to="/admin/libros/editar" @click="closeMenu">
              <i class="bi bi-pen-fill"></i> Editar libros
            </RouterLink>
          </div>
        </div>

        <RouterLink class="sidebar-link" to="/admin/categorias" @click="closeMenu">
          <i class="bi bi-bookmarks"></i> Categorías
        </RouterLink>
        
        <RouterLink class="sidebar-link" to="/admin/ventas" @click="closeMenu">
          <i class="bi bi-basket"></i> Ventas
        </RouterLink>

        <hr class="mx-3 my-2" />

        <!-- GESTIÓN DE USUARIOS -->
        <span class="sidebar-title px-3 mb-2 mt-2">
          <i class="bi bi-people-fill"></i> USUARIOS
        </span>
        
        <RouterLink class="sidebar-link" to="/admin/register" @click="closeMenu">
          <i class="bi bi-person-plus-fill"></i> Registrar Usuario
        </RouterLink>

        <RouterLink class="sidebar-link" to="/admin/listar/usuarios" @click="closeMenu">
          <i class="bi bi-people"></i> Listar Usuarios
        </RouterLink>

        <hr class="mx-3 my-2" />
        
        <button class="btn-logout" @click="logout">
          <i class="bi bi-door-open-fill"></i> Cerrar Sesión
        </button>
      </nav>
    </aside>

    <!-- Overlay -->
    <div :class="['overlay', { active: menuOpen }]" @click="closeMenu"></div>

    <!-- CONTENIDO -->
    <main class="content flex-grow-1">
      <RouterView />
    </main>

    <!-- FOOTER -->
    <footer class="footer text-light py-3">
      <div class="container text-center">
        <p class="mb-1">© {{ currentYear }} Librería Digital. Todos los derechos reservados.</p>
        <div class="social-links text-center"> 
          <a href="#" class="me-3" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
          <a href="#" class="me-3" aria-label="Twitter"><i class="bi bi-twitter"></i></a>
          <a href="#" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/api/axios'

const router = useRouter()
const menuOpen = ref(false)
const perfilOpen = ref(false)
const librosOpen = ref(false)

const user = ref(null)
const currentYear = new Date().getFullYear()

// Computed para datos del usuario
const userName = computed(() => user.value?.name || 'Usuario')
const userAvatar = computed(() => {
  if (user.value?.avatar) {
    return user.value.avatar
  }
  // Avatar por defecto con initials
  const name = user.value?.name || 'Usuario'
  return `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=15AEBF&color=fff&size=100&bold=true`
})

// Cargar datos del usuario
onMounted(async () => {
  try {
    const storedUser = localStorage.getItem('user')
    if (storedUser) {
      user.value = JSON.parse(storedUser)
    }
    
    // Obtener datos actualizados del servidor
    const response = await api.get('/user')
    user.value = response.data
    localStorage.setItem('user', JSON.stringify(response.data))
  } catch (error) {
    console.error('Error al cargar usuario:', error)
  }
})

function toggleMenu() { 
  menuOpen.value = !menuOpen.value 
}

function closeMenu() { 
  menuOpen.value = false 
  perfilOpen.value = false
}

function togglePerfil() { 
  perfilOpen.value = !perfilOpen.value 
}

function toggleLibros() { 
  librosOpen.value = !librosOpen.value 
}

function logout() {
  localStorage.removeItem('token')
  localStorage.removeItem('user')
  router.push('/login')
}
</script>

<style scoped>
@import "../../css/admin.css";

/* Avatar en el header */
.avatar-header {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid white;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.avatar-header:hover {
  transform: scale(1.1);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.user-name {
  font-weight: 1000;
  font-size: 0.95rem;
  width: 50px;
}

/* Ícono de perfil con animación */
.perfil-icon {
  transition: transform 0.3s ease, color 0.3s ease;
  font-size: 0.85rem;
}

.nav-link:hover .perfil-icon {
  color: #F7C221;
}

/* Submenu animation */
.submenu {
  animation: slideDown 0.3s ease;
  overflow: hidden;
}

.content {
  margin-left: 250px;
}

@keyframes slideDown {
  from {
    opacity: 0;
    max-height: 0;
  }
  to {
    opacity: 1;
    max-height: 300px;
  }
}

/* Chevron animation */
.sidebar-link i.bi-chevron-down,
.sidebar-link i.bi-chevron-right {
  transition: transform 0.3s ease;
  font-size: 0.8rem;
}

/* Sidebar active state */
.sidebar.active { 
  transform: translateX(0); 
}

.overlay.active { 
  display: block; 
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .user-name {
    display: none;
  }
  
  .avatar-header {
    width: 35px;
    height: 35px;
  }
}
</style>