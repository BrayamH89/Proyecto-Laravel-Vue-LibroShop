<template>
  <div class="formulario" :style="{ backgroundImage: `url(${backgroundImage})` }">
    <div class="admin-container">
      <div class="admin-card">
        <h1 class="admin-title">
          <i class="bi bi-journal-arrow-up"></i> Subir Nuevo Libro
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

        <!-- Formulario para crear libro -->
        <form @submit.prevent="createLibro" enctype="multipart/form-data">
          <!-- Título -->
          <div class="form-group">
            <label for="titulo" class="form-label">
              Título del libro <span class="text-danger">*</span>
            </label>
            <input
              type="text"
              id="titulo"
              v-model="libro.titulo"
              class="form-control"
              placeholder="Ej: Cien años de soledad"
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
              v-model="libro.autor"
              class="form-control"
              placeholder="Ej: Gabriel García Márquez"
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
              v-model="libro.descripcion"
              class="form-control"
              rows="4"
              placeholder="Describe brevemente el contenido del libro..."
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
              v-model="libro.precio"
              class="form-control"
              placeholder="Ej: 25000"
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
              v-model="libro.categorias"
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

          <!-- Imagen de portada -->
          <div class="form-group">
            <label for="imagen" class="form-label">
              Portada del libro
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
              Formatos: JPG, PNG, GIF. Tamaño máximo: 2MB
            </small>
            
            <!-- Preview de la imagen -->
            <div v-if="imagePreview" class="image-preview mt-3">
              <img :src="imagePreview" alt="Preview" class="preview-img" />
              <button type="button" @click="removeImage" class="btn-remove-preview">
                <i class="bi bi-x"></i>
              </button>
            </div>
          </div>

          <!-- Archivo PDF -->
          <div class="form-group">
            <label for="pdf" class="form-label">
              <i class="fas fa-file-pdf me-2"></i>Archivo del libro
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
              Formatos: PDF, EPUB, DOCX, TXT. Tamaño máximo: 10MB
            </small>
            
            <!-- Nombre del archivo seleccionado -->
            <div v-if="pdfFileName" class="file-info mt-2">
              <i class="bi bi-x"></i>
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
              <i class="bi bi-x-circle-fill"></i> Cancelar
            </button>
            <button type="submit" class="btn-primary" :disabled="loading">
              <i v-if="loading" class="bi bi-journal-arrow-up"></i>
              <i v-else class="bi bi-journal-plus"></i>
              {{ loading ? 'Guardando...' : 'Crear Libro' }}
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
  name: 'AdminLibrosCrear',
  data() {
    return {
      libro: {
        titulo: '',
        autor: '',
        descripcion: '',
        precio: '',
        categorias: [],
        imagen: null,
        pdf: null,
      },
      categorias: [],
      imagePreview: null,
      pdfFileName: null,
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
        const response = await api.get('/admin/categorias');
        this.categorias = response.data;
      } catch (error) {
        console.error('Error al cargar categorías:', error);
        this.errorMessages = ['Error al cargar las categorías.'];
      }
    },
    
    handleImageUpload(event) {
      const file = event.target.files[0];
      if (file) {
        // Validar tamaño (2MB)
        if (file.size > 2 * 1024 * 1024) {
          this.errorMessages = ['La imagen no debe superar los 2MB'];
          event.target.value = '';
          return;
        }
        
        this.libro.imagen = file;
        
        // Crear preview
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
        // Validar tamaño (10MB)
        if (file.size > 10 * 1024 * 1024) {
          this.errorMessages = ['El archivo no debe superar los 10MB'];
          event.target.value = '';
          return;
        }
        
        this.libro.pdf = file;
        this.pdfFileName = file.name;
      }
    },
    
    removeImage() {
      this.libro.imagen = null;
      this.imagePreview = null;
      document.getElementById('imagen').value = '';
    },
    
    removePdf() {
      this.libro.pdf = null;
      this.pdfFileName = null;
      document.getElementById('pdf').value = '';
    },
    
    async createLibro() {
      this.loading = true;
      this.errorMessages = [];
      this.successMessage = '';

      try {
        // Crear FormData para enviar archivos
        const formData = new FormData();
        formData.append('titulo', this.libro.titulo);
        formData.append('autor', this.libro.autor || '');
        formData.append('descripcion', this.libro.descripcion || '');
        formData.append('precio', this.libro.precio);
        
        // Agregar categorías
        this.libro.categorias.forEach((catId) => {
          formData.append('categorias[]', catId);
        });
        
        // Agregar archivos si existen
        if (this.libro.imagen) {
          formData.append('imagen', this.libro.imagen);
        }
        if (this.libro.pdf) {
          formData.append('pdf', this.libro.pdf);
        }

        const response = await api.post('/admin/libros', formData, {
          headers: {
            'Content-Type': 'multipart/form-data',
          },
        });

        console.log('Respuesta del servidor:', response.data);
        
        this.successMessage = 'Libro creado exitosamente.';
        
        // Limpiar formulario
        this.libro = {
          titulo: '',
          autor: '',
          descripcion: '',
          precio: '',
          categorias: [],
          imagen: null,
          pdf: null,
        };
        this.imagePreview = null;
        this.pdfFileName = null;
        document.getElementById('imagen').value = '';
        document.getElementById('pdf').value = '';
        
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
          setTimeout(() => {
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            this.$router.push('/login');
          }, 2000);
        } else if (error.response?.status === 403) {
          this.errorMessages = ['No tienes permisos para realizar esta acción.'];
        } else {
          this.errorMessages = [error.response?.data?.message || 'Error al crear el libro.'];
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
   ESTILOS PARA CREAR LIBRO
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
}

.file-info span {
  flex: 1;
  color: #374151;
  font-size: 0.9rem;
}

.btn-remove-file {
  background-color: #dc3545;
  color: white;
  border: none;
  border-radius: 0.375rem;
  padding: 0.25rem 0.5rem;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 0.85rem;
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
  background-color: #ff0000;
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