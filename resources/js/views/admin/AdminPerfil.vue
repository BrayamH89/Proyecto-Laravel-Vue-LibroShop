<template>
  <div class="perfil-page">
    <div class="perfil-container">
      <!-- Mensajes -->
      <div v-if="successMessage" class="alert alert-success" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>{{ successMessage }}
      </div>

      <div v-if="errorMessage" class="alert alert-danger" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ errorMessage }}
      </div>

      <!-- Card Principal -->
      <div class="card shadow">
        <div class="card-header">
          <h5 class="card-title mb-0">
            <i class="bi bi-person-circle me-2"></i>Mi Perfil
          </h5>
        </div>

        <div class="card-body">
          <!-- Avatar Section -->
          <div class="avatar-section">
            <div class="avatar-wrapper">
              <img 
                :src="avatarPreview || defaultAvatar" 
                alt="Avatar" 
                class="avatar-img"
              />
              <div class="avatar-overlay">
                <label for="avatar" class="avatar-upload-btn">
                  <i class="bi bi-camera-fill"></i>
                  <input
                    type="file"
                    id="avatar"
                    ref="avatarInput"
                    accept="image/*"
                    @change="handleAvatarChange"
                    :disabled="loading"
                    hidden
                  />
                </label>
              </div>
            </div>
            <div class="avatar-info">
              <h6>{{ form.name || 'Usuario' }}</h6>
              <p class="text-muted">{{ form.email }}</p>
              <span class="badge badge-role">{{ userRole }}</span>
            </div>
          </div>

          <!-- Formulario -->
          <form @submit.prevent="updatePerfil" class="mt-4">
            <div class="row">
              <!-- Nombre -->
              <div class="col-md-6 mb-3">
                <label for="name" class="form-label">
                  <i class="bi bi-person me-1"></i>Nombre completo
                </label>
                <input
                  type="text"
                  id="name"
                  v-model="form.name"
                  class="form-control"
                  :class="{ 'is-invalid': errors.name }"
                  required
                  :disabled="loading"
                  placeholder="Ingresa tu nombre"
                />
                <div v-if="errors.name" class="invalid-feedback">
                  {{ errors.name[0] }}
                </div>
              </div>

              <!-- Email -->
              <div class="col-md-6 mb-3">
                <label for="email" class="form-label">
                  <i class="bi bi-envelope me-1"></i>Correo electrónico
                </label>
                <input
                  type="email"
                  id="email"
                  v-model="form.email"
                  class="form-control"
                  :class="{ 'is-invalid': errors.email }"
                  required
                  :disabled="loading"
                  placeholder="tu@email.com"
                />
                <div v-if="errors.email" class="invalid-feedback">
                  {{ errors.email[0] }}
                </div>
              </div>
            </div>

            <!-- Divider -->
            <hr class="my-4" />
            <h6 class="mb-3">
              <i class="bi bi-shield-lock me-2"></i>Cambiar contraseña (opcional)
            </h6>

            <div class="row">
              <!-- Nueva contraseña -->
              <div class="col-md-6 mb-3">
                <label for="password" class="form-label">Nueva contraseña</label>
                <div class="password-input-wrapper">
                  <input
                    :type="showPassword ? 'text' : 'password'"
                    id="password"
                    v-model="form.password"
                    class="form-control"
                    :class="{ 'is-invalid': errors.password }"
                    :disabled="loading"
                    placeholder="Mínimo 6 caracteres"
                  />
                  <button
                    type="button"
                    class="password-toggle"
                    @click="showPassword = !showPassword"
                    tabindex="-1"
                  >
                    <i :class="showPassword ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                  </button>
                </div>
                <div v-if="errors.password" class="invalid-feedback d-block">
                  {{ errors.password[0] }}
                </div>
                <small class="form-text text-muted">
                  Déjalo en blanco si no deseas cambiar tu contraseña
                </small>
              </div>

              <!-- Confirmar contraseña -->
              <div class="col-md-6 mb-3">
                <label for="password_confirmation" class="form-label">
                  Confirmar contraseña
                </label>
                <div class="password-input-wrapper">
                  <input
                    :type="showPasswordConfirm ? 'text' : 'password'"
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    class="form-control"
                    :disabled="loading || !form.password"
                    placeholder="Repite la contraseña"
                  />
                  <button
                    type="button"
                    class="password-toggle"
                    @click="showPasswordConfirm = !showPasswordConfirm"
                    tabindex="-1"
                    :disabled="!form.password"
                  >
                    <i :class="showPasswordConfirm ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                  </button>
                </div>
              </div>
            </div>

            <!-- Botones -->
            <div class="d-flex gap-2 mt-4">
              <button type="submit" class="btn btn-primary flex-grow-1" :disabled="loading">
                <i v-if="loading" class="bi bi-arrow-clockwise spin-animation me-2"></i>
                <i v-else class="bi bi-save me-2"></i>
                {{ loading ? 'Guardando...' : 'Guardar cambios' }}
              </button>
              <button 
                type="button" 
                class="btn btn-outline-secondary"
                @click="resetForm"
                :disabled="loading"
              >
                <i class="bi bi-x-circle me-2"></i>Cancelar
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Card de información adicional -->
      <div class="info-card mt-3">
        <i class="bi bi-info-circle me-2"></i>
        <span>Tu información está segura y encriptada</span>
      </div>
    </div>
  </div>
</template>

<script>
import api from '@/api/axios';

export default {
  name: 'EditarPerfil',
  data() {
    return {
      form: {
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
      },
      avatarFile: null,
      avatarPreview: null,
      defaultAvatar: 'https://ui-avatars.com/api/?name=Usuario&background=15AEBF&color=fff&size=200',
      userRole: 'Usuario',
      errors: {},
      successMessage: '',
      errorMessage: '',
      loading: false,
      showPassword: false,
      showPasswordConfirm: false,
    };
  },
  mounted() {
    this.fetchPerfil();
  },
  methods: {
    async fetchPerfil() {
      try {
        console.log('🔄 Cargando perfil...');
        const response = await api.get('/admin/perfil');
        const user = response.data;
        
        console.log('✅ Perfil cargado:', user);
        
        this.form.name = user.name || '';
        this.form.email = user.email || '';
        this.avatarPreview = user.avatar || null;
        this.userRole = user.role === 'admin' ? 'Administrador' : 'Usuario';
        
        // Actualizar avatar por defecto con el nombre
        if (!user.avatar && user.name) {
          this.defaultAvatar = `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name)}&background=15AEBF&color=fff&size=200`;
        }
      } catch (error) {
        console.error('❌ Error al cargar perfil:', error);
        this.errorMessage = 'Error al cargar la información del perfil';
        
        if (error.response?.status === 401) {
          setTimeout(() => {
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            this.$router.push('/login');
          }, 2000);
        }
      }
    },

    handleAvatarChange(event) {
      const file = event.target.files[0];
      
      if (!file) return;

      // Validar tamaño (max 2MB)
      if (file.size > 2 * 1024 * 1024) {
        this.errorMessage = 'La imagen no debe superar 2MB';
        this.$refs.avatarInput.value = '';
        return;
      }

      // Validar tipo
      if (!file.type.startsWith('image/')) {
        this.errorMessage = 'Solo se permiten archivos de imagen';
        this.$refs.avatarInput.value = '';
        return;
      }

      this.avatarFile = file;
      
      // Preview
      const reader = new FileReader();
      reader.onload = (e) => {
        this.avatarPreview = e.target.result;
      };
      reader.readAsDataURL(file);
      
      this.errorMessage = '';
      console.log('✅ Imagen seleccionada:', file.name);
    },

    async updatePerfil() {
      this.loading = true;
      this.errors = {};
      this.successMessage = '';
      this.errorMessage = '';

      const formData = new FormData();
      formData.append('name', this.form.name);
      formData.append('email', this.form.email);

      if (this.form.password) {
        formData.append('password', this.form.password);
        formData.append('password_confirmation', this.form.password_confirmation);
      }

      if (this.avatarFile) {
        formData.append('avatar', this.avatarFile);
      }

      try {
        console.log('🔄 Actualizando perfil...');
        
        const response = await api.post('/admin/perfil', formData, {
          headers: {
            'Content-Type': 'multipart/form-data',
          },
        });

        console.log('✅ Perfil actualizado:', response.data);

        this.successMessage = '✅ Perfil actualizado exitosamente';
        
        // Actualizar localStorage con nueva info del usuario
        const currentUser = JSON.parse(localStorage.getItem('user') || '{}');
        const updatedUser = {
          ...currentUser,
          ...response.data.user
        };
        localStorage.setItem('user', JSON.stringify(updatedUser));

        // Limpiar campos de contraseña
        this.form.password = '';
        this.form.password_confirmation = '';
        this.avatarFile = null;
        
        // Recargar perfil
        await this.fetchPerfil();

        // Scroll al top para ver el mensaje
        window.scrollTo({ top: 0, behavior: 'smooth' });

        setTimeout(() => {
          this.successMessage = '';
        }, 5000);

      } catch (error) {
        console.error('❌ Error al actualizar perfil:', error);
        console.error('📄 Respuesta:', error.response);

        if (error.response?.status === 422) {
          this.errors = error.response.data.errors || {};
          this.errorMessage = 'Por favor corrige los errores en el formulario';
        } else if (error.response?.status === 401) {
          this.errorMessage = 'Sesión expirada. Redirigiendo...';
          setTimeout(() => {
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            this.$router.push('/login');
          }, 2000);
        } else {
          this.errorMessage = error.response?.data?.message || 'Error al actualizar el perfil';
        }

        window.scrollTo({ top: 0, behavior: 'smooth' });
      } finally {
        this.loading = false;
      }
    },

    resetForm() {
      this.fetchPerfil();
      this.form.password = '';
      this.form.password_confirmation = '';
      this.avatarFile = null;
      this.errors = {};
      this.errorMessage = '';
      if (this.$refs.avatarInput) {
        this.$refs.avatarInput.value = '';
      }
    },
  },
};
</script>

<style scoped>
/* ===========================
   ESTILOS MEJORADOS
=========================== */

.perfil-page {
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
  min-height: 100vh;
  padding: 2rem 1rem;
}

.perfil-container {
  max-width: 800px;
  margin: 0 auto;
}

/* Alertas */
.alert {
  border-radius: 0.75rem;
  padding: 1rem 1.25rem;
  margin-bottom: 1.5rem;
  font-size: 0.95rem;
  border: none;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  animation: slideDown 0.3s ease;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.alert-success {
  background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
  color: #065f46;
}

.alert-danger {
  background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
  color: #991b1b;
}

/* Card */
.card {
  border: none;
  border-radius: 1rem;
  overflow: hidden;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}

.card-header {
  background: linear-gradient(135deg, #15AEBF 0%, #032840 100%);
  color: white;
  padding: 1.5rem;
  border: none;
}

.card-title {
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0;
  display: flex;
  align-items: center;
}

.card-body {
  padding: 2rem;
  background: white;
}

/* Avatar Section */
.avatar-section {
  display: flex;
  align-items: center;
  gap: 2rem;
  padding-bottom: 2rem;
  border-bottom: 2px solid #f0f0f0;
}

.avatar-wrapper {
  position: relative;
  width: 120px;
  height: 120px;
  flex-shrink: 0;
}

.avatar-img {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  object-fit: cover;
  border: 4px solid #15AEBF;
  box-shadow: 0 4px 12px rgba(21, 174, 191, 0.3);
  transition: transform 0.3s ease;
}

.avatar-wrapper:hover .avatar-img {
  transform: scale(1.05);
}

.avatar-overlay {
  position: absolute;
  bottom: 0;
  right: 0;
}

.avatar-upload-btn {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: linear-gradient(135deg, #15AEBF, #032840);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
  transition: all 0.3s ease;
  border: 3px solid white;
}

.avatar-upload-btn:hover {
  transform: scale(1.1);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.avatar-upload-btn i {
  font-size: 1.1rem;
}

.avatar-info h6 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 700;
  color: #2c3e50;
}

.avatar-info p {
  margin: 0.25rem 0;
  font-size: 0.9rem;
}

.badge-role {
  display: inline-block;
  padding: 0.35rem 0.75rem;
  background: linear-gradient(135deg, #15AEBF, #0891a8);
  color: white;
  border-radius: 1rem;
  font-size: 0.8rem;
  font-weight: 600;
  margin-top: 0.5rem;
}

/* Form */
.form-label {
  font-weight: 600;
  color: #374151;
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
  display: flex;
  align-items: center;
}

.form-control {
  padding: 0.75rem 1rem;
  border: 2px solid #e5e7eb;
  border-radius: 0.5rem;
  font-size: 0.95rem;
  transition: all 0.3s ease;
  background: white;
}

.form-control:focus {
  border-color: #15AEBF;
  box-shadow: 0 0 0 0.25rem rgba(21, 174, 191, 0.15);
  outline: none;
}

.form-control.is-invalid {
  border-color: #ef4444;
}

.form-control:disabled {
  background-color: #f3f4f6;
  cursor: not-allowed;
  opacity: 0.6;
}

.invalid-feedback {
  display: block;
  color: #ef4444;
  font-size: 0.85rem;
  margin-top: 0.35rem;
}

/* Password Input */
.password-input-wrapper {
  position: relative;
}

.password-toggle {
  position: absolute;
  right: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  color: #6b7280;
  cursor: pointer;
  padding: 0.25rem 0.5rem;
  transition: color 0.3s ease;
}

.password-toggle:hover:not(:disabled) {
  color: #15AEBF;
}

.password-toggle:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

/* Buttons */
.btn {
  padding: 0.75rem 1.5rem;
  border-radius: 0.5rem;
  font-weight: 600;
  font-size: 0.95rem;
  transition: all 0.3s ease;
  border: none;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.btn-primary {
  background: linear-gradient(135deg, #15AEBF, #0891a8);
  color: white;
  box-shadow: 0 4px 12px rgba(21, 174, 191, 0.3);
}

.btn-primary:hover:not(:disabled) {
  background: linear-gradient(135deg, #0891a8, #15AEBF);
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(21, 174, 191, 0.4);
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

.btn-outline-secondary {
  background: white;
  border: 2px solid #e5e7eb;
  color: #6b7280;
}

.btn-outline-secondary:hover:not(:disabled) {
  background: #f9fafb;
  border-color: #d1d5db;
}

.d-flex {
  display: flex;
}

.gap-2 {
  gap: 0.5rem;
}

.flex-grow-1 {
  flex-grow: 1;
}

/* Info Card */
.info-card {
  background: linear-gradient(135deg, #eff6ff, #dbeafe);
  border: 1px solid #bfdbfe;
  border-radius: 0.75rem;
  padding: 1rem 1.5rem;
  color: #1e40af;
  font-size: 0.9rem;
  display: flex;
  align-items: center;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

/* Divider */
hr {
  border: none;
  border-top: 2px solid #f0f0f0;
  margin: 2rem 0;
}

/* Animations */
.spin-animation {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 768px) {
  .perfil-page {
    padding: 1rem 0.5rem;
  }

  .card-body {
    padding: 1.5rem;
  }

  .avatar-section {
    flex-direction: column;
    text-align: center;
    gap: 1rem;
  }

  .avatar-wrapper {
    width: 100px;
    height: 100px;
  }

  .avatar-upload-btn {
    width: 35px;
    height: 35px;
  }

  .d-flex.gap-2 {
    flex-direction: column;
  }

  .btn {
    width: 100%;
  }
}
</style>