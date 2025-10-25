<template>
  <div class="formulario" :style="{ backgroundImage: `url(${backgroundImage})` }">
    <div class="admin-container">
      <div class="admin-card">
        <h1 class="admin-title">
          <i class="fas fa-edit me-2"></i>Editar Libro
        </h1>

        <!-- Mensajes de éxito o error -->
        <div v-if="successMessage" class="alert alert-success mb-4" role="alert">
          <i class="fas fa-check-circle me-2"></i>{{ successMessage }}
        </div>
        <div v-if="errorMessages.length" class="alert alert-danger mb-4" role="alert">
          <ul class="list-disc pl-5 mb-0">
            <li v-for="error in errorMessages" :key="error">⚠️ {{ error }}</li>
          </ul>
        </div>

        <!-- Loading inicial -->
        <div v-if="loadingLibros" class="loading-container">
          <i class="fas fa-spinner fa-spin fa-2x"></i>
          <p>Cargando libros...</p>
        </div>

        <!-- Selección del libro -->
        <div v-else-if="!libroId" class="form-group mb-4">
          <label for="libroSelect" class="form-label">
            Selecciona un libro para editar
          </label>
          <select
            id="libroSelect"
            v-model="selectedLibroId"
            class="form-control"
            @change="cargarLibro"
          >
            <option value="">-- Selecciona un libro --</option>
            <option v-for="libro in libros" :key="libro.id" :value="libro.id">
              {{ libro.titulo }}
            </option>
          </select>
        </div>

        <!-- Formulario de edición -->
        <form v-if="selectedLibroId || libroId" @submit.prevent="actualizarLibro">
          <!-- Título -->
          <div class="form-group">
            <label for="titulo" class="form-label">
              Título del libro <span class="text-danger">*</span>
            </label>
            <input
              type="text"
              id="titulo"
              v-model="form.titulo"
              class="form-control"
              placeholder="Título del libro"
              required
              :disabled="loading"
            />
          </div>

          <!-- Autor -->
          <div class="form-group">
            <label for="autor" class="form-label">
              Autor
            </label>
            <input
              type="text"
              id="autor"
              v-model="form.autor"
              class="form-control"
              placeholder="Nombre del autor"
              :disabled="loading"
            />
          </div>

          <!-- Descripción -->
          <div class="form-group">
            <label for="descripcion" class="form-label">
              Descripción
            </label>
            <textarea
              id="descripcion"
              v-model="form.descripcion"
              class="form-control"
              rows="4"
              placeholder="Descripción del libro..."
              :disabled="loading"
            ></textarea>
          </div>

          <!-- Precio -->
          <div class="form-group">
            <label for="precio" class="form-label">
              Precio (COP) <span class="text-danger">*</span>
            </label>
            <input
              type="number"
              id="precio"
              v-model="form.precio"
              class="form-control"
              placeholder="Precio"
              step="0.01"
              min="0"
              required
              :disabled="loading"
            />
          </div>

          <!-- Categorías -->
          <div class="form-group">
            <label for="categorias" class="form-label">
              Categorías
            </label>
            <select
              id="categorias"
              v-model="form.categorias"
              class="form-control"
              multiple
              :disabled="loading"
            >
              <option v-for="categoria in categorias" :key="categoria.id" :value="categoria.id">
                {{ categoria.nombre }}
              </option>
            </select>
            <small class="form-text text-muted">
              Mantén presionado Ctrl (Windows) o Cmd (Mac) para seleccionar múltiples categorías
            </small>
          </div>

          <!-- Portada actual -->
          <div v-if="portadaActual" class="form-group">
            <label class="form-label">
              Portada actual
            </label>
            <div class="image-preview">
              <img :src="portadaActual" alt="Portada actual" class="preview-img" />
            </div>
          </div>

          <!-- Nueva portada -->
          <div class="form-group">
            <label for="imagen" class="form-label">
              Cambiar portada
            </label>
            <input
              type="file"
              id="imagen"
              @change="handleImageUpload"
              class="form-control"
              accept="image/*"
              :disabled="loading"
            />
            <small class="form-text text-muted">
              Deja vacío si no deseas cambiar la portada. Tamaño máximo: 2MB
            </small>
            
            <!-- Preview de nueva imagen -->
            <div v-if="imagePreview" class="image-preview mt-3">
              <img :src="imagePreview" alt="Preview" class="preview-img" />
              <button type="button" @click="removeImage" class="btn-remove-preview">
                <i class="bi bi-x"></i>
              </button>
            </div>
          </div>

          <!-- Archivo actual -->
          <div v-if="archivoActual" class="form-group">
            <label class="form-label">
              Archivo actual
            </label>
            <div class="file-info">
              <i class="fas fa-file-alt me-2"></i>
              <span>Archivo PDF existente</span>
              <a :href="archivoActual" target="_blank" class="btn-view-file">
                <i class="bi bi-eye-fill"></i>
              </a>
            </div>
          </div>

          <!-- Nuevo archivo -->
          <div class="form-group">
            <label for="pdf" class="form-label">
              Cambiar archivo
            </label>
            <input
              type="file"
              id="pdf"
              @change="handlePdfUpload"
              class="form-control"
              accept=".pdf,.epub,.docx,.txt"
              :disabled="loading"
            />
            <small class="form-text text-muted">
              Deja vacío si no deseas cambiar el archivo. Tamaño máximo: 10MB
            </small>
            
            <!-- Nombre del nuevo archivo -->
            <div v-if="pdfFileName" class="file-info mt-2">
              <i class="fas fa-file-alt me-2"></i>
              <span>{{ pdfFileName }}</span>
              <button type="button" @click="removePdf" class="btn-remove-file">
                <i class="bi bi-x"></i>
              </button>
            </div>
          </div>

          <!-- Botones -->
          <div class="form-actions">
            <button
              type="button"
              @click="goBack"
              class="btn-secondary"
              :disabled="loading"
            >
              <i class="bi bi-box-arrow-left"></i> Volver
            </button>
            <button type="submit" class="btn-primary" :disabled="loading">
              <i v-if="loading" class="bi bi-journal-arrow-up"></i>
              <i v-else class="bi bi-journal-plus"></i>
              {{ loading ? 'Guardando...' : 'Guardar Cambios' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import api from '@/api/axios';

export default {
  name: 'AdminLibrosEditar',
  data() {
    return {
      libros: [],
      categorias: [],
      selectedLibroId: null,
      libroId: null, // ID desde la ruta
      form: {
        titulo: '',
        autor: '',
        descripcion: '',
        precio: '',
        categorias: [],
        imagen: null,
        pdf: null,
      },
      portadaActual: null,
      archivoActual: null,
      imagePreview: null,
      pdfFileName: null,
      successMessage: '',
      errorMessages: [],
      loading: false,
      loadingLibros: false,
      backgroundImage: '/storage/logos/Fondo.png',
    };
  },
  mounted() {
    // Si viene ID por parámetro de ruta
    if (this.$route.params.id) {
      this.libroId = this.$route.params.id;
      this.selectedLibroId = this.$route.params.id;
      this.cargarLibro();
    } else {
      // Si no, mostrar selector
      this.fetchLibros();
    }
    this.fetchCategorias();
  },
  methods: {
    async fetchLibros() {
      this.loadingLibros = true;
      try {
        console.log('Cargando lista de libros...');
        const response = await api.get('/admin/libros');
        console.log('Respuesta libros:', response.data);
        
        // Manejar respuesta paginada o no paginada
        this.libros = response.data.data || response.data;
        console.log('Libros cargados:', this.libros);
      } catch (error) {
        console.error('Error al cargar libros:', error.response || error);
        this.errorMessages = ['Error al cargar la lista de libros.'];
      } finally {
        this.loadingLibros = false;
      }
    },
    
    async fetchCategorias() {
      try {
        const response = await api.get('/admin/categorias');
        this.categorias = response.data;
        console.log('Categorías cargadas:', this.categorias);
      } catch (error) {
        console.error('Error al cargar categorías:', error);
        this.errorMessages = ['Error al cargar las categorías.'];
      }
    },
    
    async cargarLibro() {
      if (!this.selectedLibroId) return;
      
      this.loading = true;
      this.errorMessages = [];
      
      try {
        console.log('Cargando libro ID:', this.selectedLibroId);
        const response = await api.get(`/admin/libros/${this.selectedLibroId}`);
        const libro = response.data;
        console.log('Libro cargado:', libro);
        
        this.form = {
          titulo: libro.titulo || '',
          autor: libro.autor || '',
          descripcion: libro.descripcion || '',
          precio: libro.precio || '',
          categorias: libro.categorias || [],
          imagen: null,
          pdf: null,
        };
        
        this.portadaActual = libro.portada_url || null;
        this.archivoActual = libro.archivo_url || null;
        
        console.log('Formulario actualizado:', this.form);
      } catch (error) {
        console.error('Error al cargar el libro:', error.response || error);
        this.errorMessages = ['Error al cargar los datos del libro.'];
      } finally {
        this.loading = false;
      }
    },
    
    handleImageUpload(event) {
      const file = event.target.files[0];
      if (file) {
        if (file.size > 2 * 1024 * 1024) {
          this.errorMessages = ['La imagen no debe superar los 2MB'];
          event.target.value = '';
          return;
        }
        
        this.form.imagen = file;
        
        const reader = new FileReader();
        reader.onload = (e) => {
          this.imagePreview = e.target.result;
        };
        reader.readAsDataURL(file);
      }
    },
    
    handlePdfUpload(event) {
      const file = event.target.files[0];
      if (file) {
        if (file.size > 10 * 1024 * 1024) {
          this.errorMessages = ['El archivo no debe superar los 10MB'];
          event.target.value = '';
          return;
        }
        
        this.form.pdf = file;
        this.pdfFileName = file.name;
      }
    },
    
    removeImage() {
      this.form.imagen = null;
      this.imagePreview = null;
      document.getElementById('imagen').value = '';
    },
    
    removePdf() {
      this.form.pdf = null;
      this.pdfFileName = null;
      document.getElementById('pdf').value = '';
    },
    
    async actualizarLibro() {
      this.loading = true;
      this.errorMessages = [];
      this.successMessage = '';

      try {
        const formData = new FormData();
        formData.append('titulo', this.form.titulo);
        formData.append('autor', this.form.autor || '');
        formData.append('descripcion', this.form.descripcion || '');
        formData.append('precio', this.form.precio);
        
        // Agregar categorías
        this.form.categorias.forEach((catId) => {
          formData.append('categorias[]', catId);
        });
        
        // Agregar archivos si se seleccionaron nuevos
        if (this.form.imagen) {
          formData.append('imagen', this.form.imagen);
        }
        if (this.form.pdf) {
          formData.append('pdf', this.form.pdf);
        }

        // Usar PUT con _method
        formData.append('_method', 'PUT');

        const response = await api.post(`/admin/libros/${this.selectedLibroId}`, formData, {
          headers: {
            'Content-Type': 'multipart/form-data',
          },
        });

        console.log('Respuesta del servidor:', response.data);
        
        this.successMessage = 'Libro actualizado exitosamente.';
        
        // Redirigir después de 2 segundos
        setTimeout(() => {
          this.$router.push('/admin/libros/listar');
        }, 2000);
        
      } catch (error) {
        console.error('Error completo:', error.response || error);
        
        if (error.response?.status === 422) {
          this.errorMessages = Object.values(error.response.data.errors).flat();
        } else if (error.response?.status === 401) {
          this.errorMessages = ['Sesión expirada. Por favor inicia sesión nuevamente.'];
        } else if (error.response?.status === 403) {
          this.errorMessages = ['No tienes permisos para realizar esta acción.'];
        } else {
          this.errorMessages = [error.response?.data?.message || 'Error al actualizar el libro.'];
        }
      } finally {
        this.loading = false;
      }
    },
    
    goBack() {
      this.$router.push('/admin/libros/listar');
    },
  },
};
</script>

<style scoped>
/* ===========================
   ESTILOS PARA EDITAR LIBRO
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
  max-width: 900px;
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
  padding: 3rem 2rem;
  color: #6c757d;
}

.loading-container i {
  color: #15AEBF;
  margin-bottom: 1rem;
}

/* Formulario */
.form-group {
  margin-bottom: 1.5rem;
}

.form-label {
  display: block;
  font-weight: 600;
  color: #374151;
  margin-bottom: 0.5rem;
  font-size: 0.95rem;
}

.text-danger {
  color: #dc3545;
}

.form-control {
  width: 100%;
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
}

.form-control:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  background-color: #f3f4f6;
}

textarea.form-control {
  resize: vertical;
  min-height: 100px;
}

select.form-control[multiple] {
  min-height: 120px;
}

.form-text {
  display: block;
  margin-top: 0.25rem;
  font-size: 0.85rem;
}

.text-muted {
  color: #6c757d;
}

/* Preview de imagen */
.image-preview {
  position: relative;
  display: inline-block;
  margin-top: 1rem;
}

.preview-img {
  max-width: 200px;
  max-height: 250px;
  border-radius: 0.5rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.btn-remove-preview {
  position: absolute;
  top: -10px;
  right: -10px;
  background-color: #dc3545;
  color: white;
  border: none;
  border-radius: 50%;
  width: 30px;
  height: 30px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
}

.btn-remove-preview:hover {
  background-color: #c82333;
  transform: scale(1.1);
}

/* Info del archivo */
.file-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem;
  background-color: #f8f9fa;
  border-radius: 0.5rem;
  border: 1px solid #e5e7eb;
  margin-top: 0.5rem;
}

.file-info span {
  flex: 1;
  color: #374151;
  font-size: 0.9rem;
}

.btn-view-file,
.btn-remove-file {
  background-color: #3b82f6;
  color: white;
  border: none;
  border-radius: 0.375rem;
  padding: 0.25rem 0.5rem;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 0.85rem;
  text-decoration: none;
}

.btn-view-file:hover {
  background-color: #2563eb;
}

.btn-remove-file {
  background-color: #dc3545;
}

.btn-remove-file:hover {
  background-color: #c82333;
}

/* Botones de acción */
.form-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  margin-top: 2rem;
  padding-top: 1.5rem;
  border-top: 1px solid #e5e7eb;
}

.btn-primary,
.btn-secondary {
  padding: 0.75rem 1.5rem;
  font-weight: 600;
  font-size: 0.95rem;
  border-radius: 0.5rem;
  border: none;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-primary {
  background: linear-gradient(135deg, #032840, #15AEBF);
  color: #fff;
}

.btn-primary:hover:not(:disabled) {
  background: linear-gradient(135deg, #15AEBF, #032840);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.btn-secondary {
  background-color: #6c757d;
  color: #fff;
}

.btn-secondary:hover:not(:disabled) {
  background-color: #5a6268;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.btn-primary:disabled,
.btn-secondary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
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

  .form-actions {
    flex-direction: column;
  }

  .btn-primary,
  .btn-secondary {
    width: 100%;
    justify-content: center;
  }
}
</style>