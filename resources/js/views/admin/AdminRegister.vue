<template>
  <div class="registro-admin-page">
    <div class="registro-container">
      <div class="card shadow-lg">
        <div class="card-header">
          <h4 class="mb-0">
            <i class="bi bi-person-plus-fill me-2"></i>Registrar Nuevo Usuario
          </h4>
        </div>

        <div class="card-body">
          <!-- Mensajes -->
          <div v-if="successMessage" class="alert alert-success" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ successMessage }}
          </div>

          <div v-if="errorMessage" class="alert alert-danger" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ errorMessage }}
          </div>

          <div v-if="Object.keys(errors).length" class="alert alert-danger" role="alert">
            <ul class="mb-0">
              <li v-for="(error, field) in errors" :key="field">
                {{ error[0] }}
              </li>
            </ul>
          </div>

          <!-- Formulario -->
          <form @submit.prevent="registrarUsuario">
            <div class="row">
              <!-- Nombre completo -->
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
                  placeholder="Juan Pérez"
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
                  placeholder="usuario@example.com"
                />
                <div v-if="errors.email" class="invalid-feedback">
                  {{ errors.email[0] }}
                </div>
              </div>
            </div>

            <div class="row">
              <!-- Contraseña -->
              <div class="col-md-6 mb-3">
                <label for="password" class="form-label">
                  <i class="bi bi-lock me-1"></i>Contraseña
                </label>
                <div class="password-input-wrapper">
                  <input
                    :type="showPassword ? 'text' : 'password'"
                    id="password"
                    v-model="form.password"
                    class="form-control"
                    :class="{ 'is-invalid': errors.password }"
                    required
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
              </div>

              <!-- Confirmar contraseña -->
              <div class="col-md-6 mb-3">
                <label for="password_confirmation" class="form-label">
                  <i class="bi bi-lock-fill me-1"></i>Confirmar contraseña
                </label>
                <div class="password-input-wrapper">
                  <input
                    :type="showPasswordConfirm ? 'text' : 'password'"
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    class="form-control"
                    required
                    :disabled="loading"
                    placeholder="Repite la contraseña"
                  />
                  <button
                    type="button"
                    class="password-toggle"
                    @click="showPasswordConfirm = !showPasswordConfirm"
                    tabindex="-1"
                  >
                    <i :class="showPasswordConfirm ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                  </button>
                </div>
              </div>
            </div>

            <!-- ROL -->
            <div class="mb-4">
              <label for="role_id" class="form-label">
                <i class="bi bi-shield-check me-1"></i>Rol del usuario
              </label>
              <select
                id="role_id"
                v-model="form.role_id"
                class="form-select"
                :class="{ 'is-invalid': errors.role_id }"
                required
                :disabled="loading"
              >
                <option value="">Selecciona un rol</option>
                <option value="1">Administrador</option>
                <option value="2">Usuario Normal</option>
              </select>
              <div v-if="errors.role_id" class="invalid-feedback">
                {{ errors.role_id[0] }}
              </div>
              <small class="form-text text-muted">
                <i class="bi bi-info-circle me-1"></i>
                Los administradores tienen acceso total al sistema
              </small>
            </div>

            <!-- Botones -->
            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-primary flex-grow-1" :disabled="loading">
                <i v-if="loading" class="bi bi-arrow-clockwise spin-animation me-2"></i>
                <i v-else class="bi bi-person-plus-fill me-2"></i>
                {{ loading ? 'Registrando...' : 'Registrar Usuario' }}
              </button>
              <button 
                type="button" 
                class="btn btn-outline-secondary"
                @click="resetForm"
                :disabled="loading"
              >
                <i class="bi bi-x-circle me-2"></i>Limpiar
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Card informativa -->
      <div class="info-card mt-3">
        <i class="bi bi-lightbulb-fill me-2"></i>
        <div>
          <strong>Nota:</strong> El nuevo usuario recibirá un correo con sus credenciales de acceso.
          Asegúrate de que el correo electrónico sea válido.
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import api from '@/api/axios';

export default {
  name: 'AdminRegister',
  data() {
    return {
      form: {
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        role_id: '',
      },
      errors: {},
      successMessage: '',
      errorMessage: '',
      loading: false,
      showPassword: false,
      showPasswordConfirm: false,
    };
  },
  methods: {
    async registrarUsuario() {
      this.loading = true;
      this.errors = {};
      this.successMessage = '';
      this.errorMessage = '';

      try {
        console.log('🔄 Registrando usuario...', this.form);

        const response = await api.post('/admin/register', this.form);

        console.log('✅ Usuario registrado:', response.data);

        this.successMessage = `✅ Usuario "${this.form.name}" registrado exitosamente`;
        
        // Limpiar formulario
        this.resetForm();

        // Scroll al top
        window.scrollTo({ top: 0, behavior: 'smooth' });

        setTimeout(() => {
          this.successMessage = '';
        }, 5000);

      } catch (error) {
        console.error('❌ Error al registrar usuario:', error);
        console.error('📄 Respuesta:', error.response);

        if (error.response?.status === 422) {
          this.errors = error.response.data.errors || {};
          this.errorMessage = 'Por favor corrige los errores en el formulario';
        } else if (error.response?.status === 403) {
          this.errorMessage = '⛔ No tienes permisos para realizar esta acción';
        } else if (error.response?.status === 401) {
          this.errorMessage = '⛔ Sesión expirada. Redirigiendo...';
          setTimeout(() => {
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            this.$router.push('/login');
          }, 2000);
        } else {
          this.errorMessage = error.response?.data?.message || 'Error al registrar el usuario';
        }

        window.scrollTo({ top: 0, behavior: 'smooth' });
      } finally {
        this.loading = false;
      }
    },

    resetForm() {
      this.form = {
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        role_id: '',
      };
      this.errors = {};
      this.errorMessage = '';
      this.showPassword = false;
      this.showPasswordConfirm = false;
    },
  },
};
</script>

<style scoped>
/* ===========================
   ESTILOS DEL FORMULARIO
=========================== */
.registro-admin-page {
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
  min-height: 100vh;
  padding: 2rem 1rem;
}

.registro-container {
  max-width: 900px;
  margin: 0 auto;
}

/* Card */
.card {
  border: none;
  border-radius: 1rem;
  overflow: hidden;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
}

.card-header {
  background: linear-gradient(135deg, #15AEBF 0%, #032840 100%);
  color: white;
  padding: 1.5rem 2rem;
  border: none;
}

.card-header h4 {
  font-weight: 700;
  margin: 0;
  display: flex;
  align-items: center;
}

.card-body {
  padding: 2rem;
  background: white;
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

.alert ul {
  padding-left: 1.5rem;
}

/* Form */
.form-label {
  font-weight: 600;
  color: #374151;
  margin-bottom: 0.5rem;
  font-size: 0.95rem;
  display: flex;
  align-items: center;
}

.form-control,
.form-select {
  padding: 0.75rem 1rem;
  border: 2px solid #e5e7eb;
  border-radius: 0.5rem;
  font-size: 0.95rem;
  transition: all 0.3s ease;
  background: white;
}

.form-control:focus,
.form-select:focus {
  border-color: #15AEBF;
  box-shadow: 0 0 0 0.25rem rgba(21, 174, 191, 0.15);
  outline: none;
}

.form-control.is-invalid,
.form-select.is-invalid {
  border-color: #ef4444;
}

.form-control:disabled,
.form-select:disabled {
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

.form-text {
  display: block;
  margin-top: 0.5rem;
  font-size: 0.875rem;
  color: #6b7280;
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
  z-index: 10;
}

.password-toggle:hover {
  color: #15AEBF;
}

/* Select personalizado */
.form-select {
  cursor: pointer;
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
  background-repeat: no-repeat;
  background-position: right 0.75rem center;
  background-size: 16px 12px;
}

/* Botones */
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
  transform: translateY(-2px);
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
  background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
  border: 1px solid #fbbf24;
  border-radius: 0.75rem;
  padding: 1rem 1.5rem;
  color: #92400e;
  font-size: 0.9rem;
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.info-card i {
  font-size: 1.5rem;
  margin-top: 0.15rem;
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
  .registro-admin-page {
    padding: 1rem 0.5rem;
  }

  .card-body {
    padding: 1.5rem;
  }

  .card-header {
    padding: 1rem 1.5rem;
  }

  .card-header h4 {
    font-size: 1.1rem;
  }

  .d-flex.gap-2 {
    flex-direction: column;
  }

  .btn {
    width: 100%;
  }
}
</style>