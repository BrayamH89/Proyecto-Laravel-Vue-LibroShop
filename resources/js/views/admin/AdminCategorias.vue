<template>
  <div class="formulario" :style="{ backgroundImage: `url(${backgroundImage})` }">
    <div class="admin-container">
      <div class="admin-card">
        <h1 class="admin-title">
          <i class="bi bi-bookmarks-fill"></i> Gestión de Categorías
        </h1>

        <!-- Mensajes de éxito o error -->
        <div v-if="successMessage" class="alert alert-success mb-4" role="alert">
          {{ successMessage }}
        </div>
        <div v-if="errorMessages.length" class="alert alert-danger mb-4" role="alert">
          <ul class="list-disc pl-5 mb-0">
            <li v-for="error in errorMessages" :key="error">⚠️ {{ error }}</li>
          </ul>
        </div>

        <!-- Formulario para crear categoría -->
        <form @submit.prevent="createCategoria" class="mb-6">
          <div class="form-group">
            <div class="input-group">
              <input
                type="text"
                v-model="newCategoria.nombre"
                placeholder="Nombre de la categoría"
                class="form-control"
                required
                :disabled="loading"
              />
              <button type="submit" class="btn_categorias" :disabled="loading">
                <i v-if="loading" class="bi bi-bookmark-plus"></i>
                <i v-else class="fa-solid fa-circle-plus me-2"></i>
                {{ loading ? 'Creando...' : 'Crear categoría' }}
              </button>
            </div>
          </div>
        </form>

        <!-- Listado de categorías -->
        <h2 class="section-title">Categorías existentes</h2>
        <div v-if="categorias.length">
          <ul class="categoria-list">
            <li v-for="categoria in categorias" :key="categoria.id" class="categoria-item">
              <span>{{ categoria.nombre }}</span>
              <button
                @click="deleteCategoria(categoria.id)"
                class="btn_eliminar_categoria"
                :disabled="loading"
              >
                <i class="bi bi-trash3-fill"></i> Eliminar
              </button>
            </li>
          </ul>
        </div>
        <p v-else class="text-muted">No hay categorías creadas aún.</p>
      </div>
    </div>
  </div>
</template>

<script>
// ✅ CAMBIADO: Importar la instancia configurada de axios
import api from '@/api/axios';

export default {
  name: 'GestionCategorias',
  data() {
    return {
      categorias: [],
      newCategoria: {
        nombre: '',
      },
      successMessage: '',
      errorMessages: [],
      loading: false,
      backgroundImage: '/storage/logos/Fondo.png',
    };
  },
  mounted() {
    this.fetchCategorias();
  },
  methods: {
    async fetchCategorias() {
      try {
        // ✅ Usando la instancia api configurada (ya incluye el token automáticamente)
        const response = await api.get('/admin/categorias');
        this.categorias = response.data;
        console.log('Categorías cargadas:', this.categorias);
      } catch (error) {
        console.error('Error al cargar categorías:', error.response || error);
        if (error.response?.status === 401) {
          this.errorMessages = ['Sesión expirada. Por favor inicia sesión nuevamente.'];
          setTimeout(() => {
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            this.$router.push('/login');
          }, 2000);
        } else {
          this.errorMessages = ['Error al cargar las categorías.'];
        }
      }
    },
    async createCategoria() {
      this.loading = true;
      this.errorMessages = [];
      this.successMessage = '';
      
      try {
        console.log('Enviando categoría:', this.newCategoria);
        // ✅ Usando la instancia api configurada
        const response = await api.post('/admin/categorias', this.newCategoria);
        console.log('Respuesta del servidor:', response.data);
        
        this.successMessage = 'Categoría creada exitosamente.';
        this.newCategoria.nombre = '';
        this.fetchCategorias();
        setTimeout(() => this.successMessage = '', 5000);
      } catch (error) {
        console.error('Error completo:', error.response || error);
        
        if (error.response?.status === 422) {
          this.errorMessages = Object.values(error.response.data.errors).flat();
        } else if (error.response?.status === 401) {
          this.errorMessages = ['No autorizado. Por favor inicia sesión nuevamente.'];
          setTimeout(() => {
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            this.$router.push('/login');
          }, 2000);
        } else if (error.response?.status === 403) {
          this.errorMessages = ['No tienes permisos para realizar esta acción.'];
        } else {
          this.errorMessages = [error.response?.data?.message || 'Error al crear la categoría.'];
        }
      } finally {
        this.loading = false;
      }
    },
    async deleteCategoria(id) {
      if (!confirm('¿Seguro de eliminar esta categoría?')) return;
      
      this.loading = true;
      this.errorMessages = [];
      this.successMessage = '';
      
      try {
        // ✅ Usando la instancia api configurada
        await api.delete(`/admin/categorias/${id}`);
        this.successMessage = 'Categoría eliminada exitosamente.';
        this.fetchCategorias();
        setTimeout(() => this.successMessage = '', 5000);
      } catch (error) {
        console.error('Error completo:', error.response || error);
        
        if (error.response?.status === 401) {
          this.errorMessages = ['Sesión expirada. Por favor inicia sesión nuevamente.'];
        } else if (error.response?.status === 403) {
          this.errorMessages = ['No tienes permisos para realizar esta acción.'];
        } else {
          this.errorMessages = [error.response?.data?.message || 'Error al eliminar la categoría.'];
        }
      } finally {
        this.loading = false;
      }
    },
  },
};
</script>

<style scoped>
/* ===========================
   ESTILOS PARA GESTIÓN DE CATEGORÍAS
=========================== */

/* Fondo general */
.formulario {
  background-size: cover;
  background-position: center;
  font-family: 'Poppins', sans-serif;
  min-height: 100vh;
  display: flex;
  justify-content: center;
  align-items: flex-start;
  padding: 30px;
}

/* Contenedor principal */
.admin-container {
  max-width: 1200px;
  width: 100%;
}

.admin-card {
  background: #fff;
  border-radius: 0.75rem;
  box-shadow: 0 6px 18px rgba(0, 0, 0, 0.15);
  padding: 2rem;
  margin-bottom: 2rem;
}

.admin-title {
  font-size: 1.8rem;
  color: #2c3e50;
  text-align: center;
  margin-bottom: 2rem;
  font-weight: bold;
}

.section-title {
  font-size: 1.3rem;
  color: #34495e;
  margin: 2rem 0 1rem;
  font-weight: 600;
}

/* Mensajes */
.alert {
  border-radius: 0.5rem;
  padding: 0.75rem 1rem;
  font-size: 0.95rem;
  margin-bottom: 1rem;
}

.alert-success {
  background-color: #d1fae5;
  border-left: 5px solid #10b981;
  color: #065f46;
}

.alert-danger {
  background-color: #fee2e2;
  border-left: 5px solid #ef4444;
  color: #991b1b;
}

.list-disc {
  list-style-type: disc;
}

.mb-4 {
  margin-bottom: 1.5rem;
}

.pl-5 {
  padding-left: 1.25rem;
}

.mb-0 {
  margin-bottom: 0;
}

.mb-6 {
  margin-bottom: 2rem;
}

.me-2 {
  margin-right: 0.5rem;
}

/* Formulario */
.form-group {
  margin-bottom: 1.5rem;
}

.input-group {
  display: flex;
  gap: 0.75rem;
  align-items: center;
}

.form-control {
  flex: 1;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  padding: 0.75rem 1rem;
  font-size: 0.95rem;
  transition: all 0.3s ease;
  box-sizing: border-box;
}

.form-control:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 0.25rem rgba(37, 99, 235, 0.15), 0 2px 6px rgba(37, 99, 235, 0.1);
  outline: none;
  transform: translateY(-1px);
}

.form-control:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Botón crear */
.btn_categorias {
  background: linear-gradient(135deg, #032840, #15AEBF);
  color: #fff;
  padding: 0.75rem 1rem;
  font-weight: 600;
  font-size: 0.95rem;
  border-radius: 0.5rem;
  border: none;
  cursor: pointer;
  text-align: center;
  transition: all 0.3s ease;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
  white-space: nowrap;
}

.btn_categorias:hover:not(:disabled) {
  background: linear-gradient(135deg, #15AEBF, #032840);
  transform: translateY(-2px) scale(1.02);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  color: #ffffff;
}

.btn_categorias:active:not(:disabled) {
  transform: scale(0.98);
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
}

.btn_categorias:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

/* Lista de categorías */
.categoria-list {
  list-style: none;
  margin: 0;
  padding: 0;
}

.categoria-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 0.5rem;
  border-bottom: 1px solid #e5e7eb;
  font-size: 0.95rem;
  transition: background-color 0.3s ease;
}

.categoria-item:hover {
  background-color: #f8f9fa;
}

.categoria-item span {
  color: #374151;
  font-weight: 500;
}

/* Botón eliminar */
.btn_eliminar_categoria {
  background-color: #fff;
  color: #dc3545;
  border: 1px solid #dc3545;
  border-radius: 0.5rem;
  padding: 0.5rem 0.75rem;
  font-size: 0.9rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
}

.btn_eliminar_categoria:hover:not(:disabled) {
  background-color: #dc3545;
  color: white;
  transform: translateY(-1px);
  box-shadow: 0 4px 10px rgba(220, 53, 69, 0.2);
}

.btn_eliminar_categoria:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Texto muted */
.text-muted {
  color: #6c757d;
  font-style: italic;
  text-align: center;
  margin-top: 2rem;
}

/* Responsive */
@media (max-width: 768px) {
  .formulario {
    padding: 1rem;
  }

  .admin-card {
    padding: 1.5rem;
  }

  .admin-title {
    font-size: 1.5rem;
  }

  .input-group {
    flex-direction: column;
    gap: 0.5rem;
  }

  .btn_categorias {
    width: 100%;
  }

  .categoria-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }

  .btn_eliminar_categoria {
    width: 100%;
    text-align: center;
  }
}
</style>