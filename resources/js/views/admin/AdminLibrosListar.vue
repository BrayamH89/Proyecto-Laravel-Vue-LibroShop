<template>
  <div class="formulario" :style="{ backgroundImage: `url(${backgroundImage})` }">
    <div class="admin-container">
      <div class="admin-card">
        <div class="header-section">
          <h1 class="admin-title">
            <i class="bi bi-list-columns-reverse"></i> Lista de Libros
          </h1>
          <button @click="irACrear" class="btn-crear">
            <i class="bi bi-journal-arrow-up"></i> Nuevo Libro
          </button>
        </div>

        <!-- Mensajes de éxito o error -->
        <div v-if="successMessage" class="alert alert-success mb-4" role="alert">
          <i class="fas fa-check-circle me-2"></i>{{ successMessage }}
        </div>
        <div v-if="errorMessages.length" class="alert alert-danger mb-4" role="alert">
          <ul class="list-disc pl-5 mb-0">
            <li v-for="error in errorMessages" :key="error">⚠️ {{ error }}</li>
          </ul>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="loading-container">
          <i class="bi bi-stack-overflow"></i>
          <p>Cargando libros...</p>
        </div>

        <!-- Tabla de libros -->
        <div v-else class="table-container">
          <table class="libros-table" v-if="libros.length > 0">
            <thead>
              <tr>
                <th>Portada</th>
                <th>Título</th>
                <th>Autor</th>
                <th>Categorías</th>
                <th>Precio</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="libro in libros" :key="libro.id">
                <td>
                  <img
                    v-if="libro.portada_url"
                    :src="libro.portada_url"
                    :alt="libro.titulo"
                    class="img-portada"
                  />
                  <div v-else class="sin-portada">
                    <i class="bi bi-file-earmark-richtext"></i>
                  </div>
                </td>
                <td class="titulo-col">{{ libro.titulo || 'Sin título' }}</td>
                <td>{{ libro.autor || 'Desconocido' }}</td>
                <td>
                  <span class="categoria-badge">
                    {{ libro.categoria || 'Sin categoría' }}
                  </span>
                </td>
                <td class="precio-col">
                  ${{ formatPrecio(libro.precio) }} {{ libro.moneda || 'COP' }}
                </td>
                <td>
                  <div class="action-buttons">
                    <button @click="editarLibro(libro.id)" class="btn-editar" title="Editar">
                      <i class="bi bi-pen-fill"></i>
                    </button>
                    <button @click="eliminarLibro(libro.id)" class="btn-eliminar" title="Eliminar">
                      <i class="bi bi-x"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>

          <!-- Sin libros -->
          <div v-else class="sin-libros">
            <i class="fas fa-book-open fa-4x"></i>
            <p>No hay libros registrados aún</p>
            <button @click="irACrear" class="btn-crear-primero">
              <i class="bi bi-journal-arrow-up"></i> Crear primer libro
            </button>
          </div>
        </div>

        <!-- Paginación -->
        <div v-if="pagination.last_page > 1" class="pagination">
          <button
            @click="cambiarPagina(pagination.current_page - 1)"
            :disabled="pagination.current_page === 1"
            class="btn-pagination"
          >
            <i class="bi bi-pen-fill"></i> Editar
          </button>
          
          <span class="pagination-info">
            Página {{ pagination.current_page }} de {{ pagination.last_page }}
          </span>
          
          <button
            @click="cambiarPagina(pagination.current_page + 1)"
            :disabled="pagination.current_page === pagination.last_page"
            class="btn-pagination"
          >
            <i class="bi bi-x"></i> Eliminar
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import api from '@/api/axios';

export default {
  name: 'AdminLibrosListar',
  data() {
    return {
      libros: [],
      pagination: {
        current_page: 1,
        last_page: 1,
        total: 0,
      },
      successMessage: '',
      errorMessages: [],
      loading: false,
      backgroundImage: '/storage/logos/Fondo.png',
    };
  },
  mounted() {
    this.fetchLibros();
  },
  methods: {
    async fetchLibros(page = 1) {
      this.loading = true;
      this.errorMessages = [];
      
      try {
        console.log('Obteniendo libros...');
        const response = await api.get(`/admin/libros?page=${page}`);
        console.log('Respuesta completa:', response.data);
        console.log('Primer libro (si existe):', response.data.data?.[0] || response.data[0]);
        
        // Manejar respuesta paginada
        if (response.data.data) {
          this.libros = response.data.data;
          this.pagination = {
            current_page: response.data.current_page,
            last_page: response.data.last_page,
            total: response.data.total,
          };
        } else {
          // Si no viene paginado
          this.libros = response.data;
        }
        
        console.log('Libros cargados:', this.libros);
      } catch (error) {
        console.error('Error al cargar libros:', error.response || error);
        
        if (error.response?.status === 401) {
          this.errorMessages = ['Sesión expirada. Por favor inicia sesión nuevamente.'];
          setTimeout(() => {
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            this.$router.push('/login');
          }, 2000);
        } else {
          this.errorMessages = ['Error al cargar los libros.'];
        }
      } finally {
        this.loading = false;
      }
    },
    
    cambiarPagina(page) {
      if (page >= 1 && page <= this.pagination.last_page) {
        this.fetchLibros(page);
      }
    },
    
    formatPrecio(precio) {
      // Si el precio ya viene formateado como string
      if (typeof precio === 'string') {
        return precio;
      }
      // Si viene como número
      return Number(precio).toFixed(2);
    },
    
    irACrear() {
      this.$router.push('/admin/libros/crear');
    },
    
    editarLibro(id) {
      this.$router.push(`/admin/libros/editar/${id}`);
    },
    
    async eliminarLibro(id) {
      if (!confirm('¿Estás seguro de eliminar este libro?')) return;
      
      try {
        await api.delete(`/admin/libros/${id}`);
        this.successMessage = 'Libro eliminado exitosamente.';
        this.fetchLibros(this.pagination.current_page);
        setTimeout(() => this.successMessage = '', 3000);
      } catch (error) {
        console.error('Error al eliminar libro:', error);
        this.errorMessages = ['Error al eliminar el libro.'];
      }
    },
  },
};
</script>

<style scoped>
/* ===========================
   ESTILOS PARA LISTAR LIBROS
=========================== */

/* Fondo general */
.formulario {
  background-size: cover;
  background-position: center;
  font-family: 'Poppins', sans-serif;
  min-height: 100vh;
  padding: 30px;
}

/* Contenedor principal */
.admin-container {
  max-width: 1400px;
  width: 100%;
  margin: 0 auto;
}

.admin-card {
  background: #fff;
  border-radius: 0.75rem;
  box-shadow: 0 6px 18px rgba(0, 0, 0, 0.15);
  padding: 2rem;
  margin-bottom: 2rem;
}

/* Header */
.header-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.admin-title {
  font-size: 1.8rem;
  color: #2c3e50;
  font-weight: bold;
  margin: 0;
}

.btn-crear {
  background: linear-gradient(135deg, #032840, #15AEBF);
  color: #fff;
  padding: 0.75rem 1.5rem;
  font-weight: 600;
  font-size: 0.95rem;
  border-radius: 0.5rem;
  border: none;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

.btn-crear:hover {
  background: linear-gradient(135deg, #15AEBF, #032840);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
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

.me-2 {
  margin-right: 0.5rem;
}

/* Loading */
.loading-container {
  text-align: center;
  padding: 4rem 2rem;
  color: #6c757d;
}

.loading-container i {
  color: #15AEBF;
  margin-bottom: 1rem;
}

/* Tabla */
.table-container {
  overflow-x: auto;
}

.libros-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 1rem;
}

.libros-table thead {
  background-color: #f8f9fa;
  border-bottom: 2px solid #dee2e6;
}

.libros-table th {
  padding: 1rem 0.75rem;
  text-align: left;
  font-weight: 600;
  color: #374151;
  font-size: 0.9rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.libros-table tbody tr {
  border-bottom: 1px solid #e5e7eb;
  transition: background-color 0.3s ease;
}

.libros-table tbody tr:hover {
  background-color: #f8f9fa;
}

.libros-table td {
  padding: 1rem 0.75rem;
  vertical-align: middle;
}

/* Portada */
.img-portada {
  width: 60px;
  height: 80px;
  object-fit: cover;
  border-radius: 0.375rem;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

.sin-portada {
  width: 60px;
  height: 80px;
  background-color: #e5e7eb;
  border-radius: 0.375rem;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #9ca3af;
  font-size: 1.5rem;
}

/* Columnas específicas */
.titulo-col {
  font-weight: 600;
  color: #1f2937;
  max-width: 300px;
}

.precio-col {
  font-weight: 600;
  color: #059669;
}

/* Badge de categoría */
.categoria-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  background-color: #dbeafe;
  color: #1e40af;
  border-radius: 1rem;
  font-size: 0.85rem;
  font-weight: 500;
}

/* Botones de acción */
.action-buttons {
  display: flex;
  gap: 0.5rem;
}

.btn-editar,
.btn-eliminar {
  padding: 0.5rem 0.75rem;
  border: none;
  border-radius: 0.375rem;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 0.9rem;
}

.btn-editar {
  background-color: #3b82f6;
  color: white;
}

.btn-editar:hover {
  background-color: #2563eb;
  transform: translateY(-2px);
}

.btn-eliminar {
  background-color: #ef4444;
  color: white;
}

.btn-eliminar:hover {
  background-color: #dc2626;
  transform: translateY(-2px);
}

/* Sin libros */
.sin-libros {
  text-align: center;
  padding: 4rem 2rem;
  color: #6c757d;
}

.sin-libros i {
  color: #d1d5db;
  margin-bottom: 1rem;
}

.sin-libros p {
  font-size: 1.1rem;
  margin-bottom: 1.5rem;
}

.btn-crear-primero {
  background: linear-gradient(135deg, #032840, #15AEBF);
  color: #fff;
  padding: 0.75rem 1.5rem;
  font-weight: 600;
  border-radius: 0.5rem;
  border: none;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-crear-primero:hover {
  background: linear-gradient(135deg, #15AEBF, #032840);
  transform: translateY(-2px);
}

/* Paginación */
.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 1rem;
  margin-top: 2rem;
  padding-top: 1.5rem;
  border-top: 1px solid #e5e7eb;
}

.btn-pagination {
  padding: 0.5rem 1rem;
  background-color: #f3f4f6;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-pagination:hover:not(:disabled) {
  background-color: #e5e7eb;
}

.btn-pagination:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.pagination-info {
  font-weight: 500;
  color: #374151;
}

/* Responsive */
@media (max-width: 768px) {
  .formulario {
    padding: 1rem;
  }

  .admin-card {
    padding: 1.5rem;
  }

  .header-section {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }

  .btn-crear {
    width: 100%;
    text-align: center;
  }

  .libros-table {
    font-size: 0.85rem;
  }

  .libros-table th,
  .libros-table td {
    padding: 0.5rem;
  }

  .img-portada {
    width: 40px;
    height: 60px;
  }

  .action-buttons {
    flex-direction: column;
  }
}
</style>