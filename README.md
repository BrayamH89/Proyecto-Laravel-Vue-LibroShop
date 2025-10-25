📚 Librería Digital - Documentación Completa
📋 Tabla de Contenidos

Descripción del Proyecto
Tecnologías Utilizadas
Requisitos Previos
Instalación
Configuración
Estructura de la Base de Datos
API Endpoints
Decisiones Técnicas
Características Principales
Troubleshooting


📖 Descripción del Proyecto
Librería Digital es una aplicación web full-stack para la gestión y venta de libros digitales. Incluye un panel de administración completo y una interfaz de usuario para explorar y comprar libros.
Características Principales:

🔐 Sistema de autenticación con roles (Admin/Usuario)
📚 Catálogo de libros con filtros avanzados
🛒 Sistema de compras y pagos
📊 Dashboard administrativo con estadísticas
🏷️ Gestión de categorías y libros
👤 Gestión de perfiles y usuarios
💳 Historial de compras
🖼️ Carga de imágenes y archivos PDF


🛠️ Tecnologías Utilizadas
Backend

Laravel 12 - Framework PHP
Sanctum - Autenticación API
SQLite - Base de datos (puede cambiarse a MySQL/PostgreSQL)
PHP 8.2+

Frontend

Vue 3 - Framework JavaScript
Vue Router - Enrutamiento SPA
Axios - Cliente HTTP
Bootstrap 5 - Framework CSS
Bootstrap Icons - Iconografía
Vite - Build tool


📦 Requisitos Previos
Asegúrate de tener instalado:

PHP >= 8.2
Composer (gestor de dependencias PHP)
Node.js >= 18.x y npm (o yarn)
Git
SQLite (o MySQL/MariaDB si prefieres)


🚀 Instalación
1️⃣ Clonar el Repositorio
bashgit clone https://github.com/tu-usuario/libreria-digital.git
cd libreria-digital
2️⃣ Instalación del Backend (Laravel)
bash# Instalar dependencias de PHP
composer install

# Copiar archivo de configuración
cp .env.example .env

# Generar key de aplicación
php artisan key:generate

# Crear base de datos SQLite
touch database/database.sqlite

# Ejecutar migraciones
php artisan migrate

# Crear enlace simbólico para almacenamiento público
php artisan storage:link

# (Opcional) Seed de datos iniciales
php artisan db:seed --class=RoleSeeder
3️⃣ Instalación del Frontend (Vue.js)
bash# Instalar dependencias de Node.js
npm install

# Compilar assets para desarrollo
npm run dev

# O compilar para producción
npm run build
4️⃣ Iniciar Servidores
Terminal 1 - Backend:
bashphp artisan serve
# Laravel correrá en http://127.0.0.1:8000
Terminal 2 - Frontend (desarrollo):
bashnpm run dev
# Vite correrá en http://localhost:5173

⚙️ Configuración
Archivo .env
Copia y modifica el archivo .env.example:
env# Configuración de la aplicación
APP_NAME="Librería Digital"
APP_ENV=local
APP_KEY=base64:TU_KEY_GENERADA
APP_DEBUG=true
APP_URL=http://localhost:8000

# Configuración de base de datos (SQLite)
DB_CONNECTION=sqlite
# Para MySQL/MariaDB, descomentar:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=libreria_digital
# DB_USERNAME=root
# DB_PASSWORD=

# Configuración de sesión
SESSION_DRIVER=database
SESSION_LIFETIME=120

# Configuración de cola
QUEUE_CONNECTION=database

# Configuración de caché
CACHE_STORE=database

# Configuración de correo (opcional)
MAIL_MAILER=log
MAIL_FROM_ADDRESS="hola@libreria.com"
MAIL_FROM_NAME="${APP_NAME}"

# URL de la API para Vue (en desarrollo)
VITE_API_URL=http://127.0.0.1:8000
Configuración CORS
El archivo config/cors.php está configurado para aceptar peticiones desde:

http://localhost:5173 (Vite dev server)
http://localhost:3000
http://127.0.0.1:5173
http://127.0.0.1:3000


🗄️ Estructura de la Base de Datos
Migrations
Las migraciones se encuentran en database/migrations/ y se ejecutan en orden cronológico:
1. users - Tabla de usuarios
phpSchema::create('users', function (Blueprint $table) {
    $table->id();
    $table->foreignId('role_id')->default(2)->constrained('roles');
    $table->string('name');
    $table->string('email')->unique();
    $table->timestamp('email_verified_at')->nullable();
    $table->string('password');
    $table->string('avatar')->nullable();
    $table->rememberToken();
    $table->timestamps();
});
2. roles - Tabla de roles
phpSchema::create('roles', function (Blueprint $table) {
    $table->id();
    $table->string('nombre')->unique();
    $table->timestamps();
});
Roles por defecto:

1 → Admin
2 → Usuario normal

3. libros - Tabla de libros
phpSchema::create('libros', function (Blueprint $table) {
    $table->id();
    $table->string('titulo');
    $table->string('autor')->nullable();
    $table->text('descripcion')->nullable();
    $table->unsignedInteger('precio_cents'); // Precio en centavos
    $table->string('moneda', 3)->default('COP');
    $table->string('imagen_path')->nullable();
    $table->string('contenido_path')->nullable(); // PDF/EPUB
    $table->timestamps();
});
4. categorias - Tabla de categorías
phpSchema::create('categorias', function (Blueprint $table) {
    $table->id();
    $table->string('nombre')->unique();
    $table->string('slug')->unique();
    $table->text('descripcion')->nullable();
    $table->timestamps();
});
5. categoria_libro - Tabla pivote (Muchos a Muchos)
phpSchema::create('categoria_libro', function (Blueprint $table) {
    $table->id();
    $table->foreignId('libro_id')->constrained('libros')->onDelete('cascade');
    $table->foreignId('categoria_id')->constrained('categorias')->onDelete('cascade');
    $table->timestamps();
    $table->unique(['libro_id', 'categoria_id']);
});
6. compras - Tabla de compras
phpSchema::create('compras', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
    $table->foreignId('libro_id')->constrained('libros')->onDelete('cascade');
    $table->integer('cantidad')->default(1);
    $table->unsignedInteger('precio_unitario_cents');
    $table->unsignedInteger('total_cents');
    $table->string('moneda', 3)->default('COP');
    $table->enum('metodo_pago', ['transferencia', 'tarjeta', 'paypal', 'efectivo'])
        ->default('tarjeta');
    $table->enum('estado_pago', ['pendiente', 'pagado', 'rechazado'])
        ->default('pendiente');
    $table->string('transaction_id')->nullable();
    $table->json('meta')->nullable();
    $table->timestamps();
});
```

### Relaciones
```
users (1) ──┬── (N) compras
            └── (1) roles

libros (N) ──── (N) categorias  [categoria_libro]

compras (N) ──── (1) libros
compras (N) ──── (1) users

🔌 API Endpoints
Base URL: http://127.0.0.1:8000/api
🔓 Públicas (sin autenticación)
MétodoEndpointDescripciónPOST/loginIniciar sesiónPOST/registerRegistro de usuario normalGET/librosListar libros públicos (con filtros)GET/libros/{id}Detalle de un libroGET/categoriasListar todas las categorías
Ejemplo: Login
jsonPOST /api/login
Content-Type: application/json

{
  "email": "admin@example.com",
  "password": "password123"
}

// Respuesta
{
  "token": "1|abc123...",
  "user": {
    "id": 1,
    "role": "admin",
    "name": "Admin User",
    "email": "admin@example.com"
  }
}
```

### 🔒 Protegidas (requieren token)

**Headers requeridos:**
```
Authorization: Bearer {token}
Content-Type: application/json
👤 Usuario Autenticado
MétodoEndpointDescripciónGET/userInformación del usuario actualPOST/logoutCerrar sesiónGET/perfilVer perfilPOST/perfilActualizar perfil (multipart/form-data)DELETE/perfil/avatarEliminar avatar
💳 Compras
MétodoEndpointDescripciónGET/comprasMis compras (paginadas)POST/comprasCrear compraGET/compras/{id}Detalle de compraGET/compras/estadisticasEstadísticas del usuarioPATCH/compras/{id}/cancelarCancelar compra pendiente
Ejemplo: Crear Compra
jsonPOST /api/compras
Authorization: Bearer {token}

{
  "libro_id": 1,
  "cantidad": 1,
  "metodo_pago": "tarjeta"
}

// Respuesta
{
  "message": "¡Compra realizada exitosamente!",
  "compra": {
    "id": 1,
    "cantidad": 1,
    "total_cents": 2500000,
    "moneda": "COP",
    "metodo_pago": "tarjeta",
    "estado": "completada",
    "created_at": "2025-10-24 10:30:00",
    "libro": {
      "id": 1,
      "titulo": "Cien Años de Soledad",
      "autor": "Gabriel García Márquez",
      "imagen_url": "...",
      "precio_cents": 2500000
    }
  }
}
🔐 Admin (requieren rol admin)
📚 Libros
MétodoEndpointDescripciónGET/admin/librosListar libros (paginados)POST/admin/librosCrear libro (multipart/form-data)GET/admin/libros/{id}Ver libroPUT/PATCH/admin/libros/{id}Actualizar libroDELETE/admin/libros/{id}Eliminar libro
Ejemplo: Crear Libro
jsonPOST /api/admin/libros
Authorization: Bearer {token}
Content-Type: multipart/form-data

{
  "titulo": "Cien Años de Soledad",
  "autor": "Gabriel García Márquez",
  "descripcion": "Obra maestra del realismo mágico...",
  "precio": 25000, // En pesos (se convierte a centavos)
  "categorias": [1, 2], // IDs de categorías
  "imagen": (archivo),
  "pdf": (archivo)
}
🏷️ Categorías
MétodoEndpointDescripciónGET/admin/categoriasListar categoríasPOST/admin/categoriasCrear categoríaGET/admin/categorias/{id}Ver categoríaPUT/PATCH/admin/categorias/{id}Actualizar categoríaDELETE/admin/categorias/{id}Eliminar categoría
📊 Dashboard
MétodoEndpointDescripciónGET/admin/dashboardEstadísticas del dashboard
Respuesta:
json{
  "ventasHoy": 500000, // centavos
  "totalVentas": 15000000,
  "totalLibros": 42,
  "ventas": [
    {
      "id": 1,
      "libro": "Cien Años de Soledad",
      "cliente": "Juan Pérez",
      "total": 2500000,
      "fecha": "2025-10-24 10:30",
      "estado": "pagado"
    }
  ]
}
💵 Ventas
MétodoEndpointDescripciónGET/admin/ventasListar todas las ventasGET/admin/ventas/{id}Detalle de ventaPATCH/admin/ventas/{id}/estadoActualizar estado de pago
Ejemplo: Actualizar Estado
jsonPATCH /api/admin/ventas/1/estado
Authorization: Bearer {token}

{
  "estado_pago": "pagado" // pendiente | pagado | rechazado
}
👥 Usuarios
MétodoEndpointDescripciónPOST/admin/registerRegistrar usuario con rolGET/admin/usuariosListar usuariosDELETE/admin/usuarios/{id}Eliminar usuario
Ejemplo: Registro Admin
jsonPOST /api/admin/register
Authorization: Bearer {token}

{
  "name": "Nuevo Admin",
  "email": "admin2@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "role_id": 1 // 1=admin, 2=user
}

🎯 Decisiones Técnicas
1. Arquitectura SPA (Single Page Application)
Justificación:

Mejor experiencia de usuario sin recargas de página
Separación clara entre frontend y backend
Facilita escalabilidad y mantenimiento
Permite desarrollar app móvil usando la misma API

2. Laravel Sanctum para Autenticación
Justificación:

Ligero y simple comparado con OAuth2/Passport
Perfecto para SPAs y aplicaciones móviles
Tokens de autenticación persistentes
Soporta CSRF para cookies stateful

3. Precios en Centavos (Enteros)
Justificación:

Evita errores de redondeo con decimales
Más preciso para cálculos monetarios
Facilita integración con pasarelas de pago
Estándar en la industria (Stripe, PayPal, etc.)

php// ✅ Correcto
'precio_cents' => 2500000 // $25,000.00 COP

// ❌ Evitar
'precio' => 25000.00 // Float puede tener problemas de precisión
4. SQLite por Defecto
Justificación:

Configuración cero para desarrollo
No requiere servidor de BD adicional
Fácil de migrar a MySQL/PostgreSQL
Perfecto para prototipado rápido

Para producción se recomienda MySQL/PostgreSQL:
envDB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=libreria_digital
DB_USERNAME=root
DB_PASSWORD=tu_password
5. Middleware de Roles Personalizado
Ubicación: app/Http/Middleware/AdminMiddleware.php
Justificación:

Control granular de acceso
Fácil de extender con más roles
Mensajes de error claros

php// Verificación simple y efectiva
if ($user->role_id != 1) {
    return response()->json([
        'message' => 'No tienes permisos de administrador.'
    ], 403);
}
6. Almacenamiento de Archivos Local
Justificación:

Simplifica desarrollo inicial
Fácilmente migrable a S3/DigitalOcean Spaces
Enlaces simbólicos para acceso público

bashphp artisan storage:link
# Crea enlace: public/storage -> storage/app/public
7. Vue 3 Composition API
Justificación:

Mejor organización del código
Reutilización de lógica con composables
Mejor TypeScript support
Más moderno y mantenible

8. Validación de Archivos
Implementado:

Imágenes: max 2MB (jpg, png, gif)
PDFs: max 10MB (pdf, epub, docx, txt)

Ubicación: Controllers y Vue components
9. Paginación API
Justificación:

Mejor rendimiento con grandes datasets
Reduce carga del servidor
Mejora UX con carga progresiva

php$libros = Libro::with('categorias')->paginate(12);
10. CORS Configurado para Desarrollo Local
Ubicación: config/cors.php
php'allowed_origins' => [
    'http://localhost:5173',
    'http://127.0.0.1:5173',
],
'supports_credentials' => true,

✨ Características Principales
👤 Para Usuarios

Catálogo de Libros

Filtros por categoría
Filtros por rango de precio
Búsqueda por título/autor
Vista de cuadrícula responsiva


Sistema de Compras

Proceso de checkout simplificado
Múltiples métodos de pago
Confirmación inmediata
Historial de compras


Gestión de Perfil

Actualizar información personal
Cambiar contraseña
Subir/cambiar avatar
Ver estadísticas personales



👨‍💼 Para Administradores

Dashboard

Ventas del día
Total de ventas
Cantidad de libros
Últimas 20 ventas


Gestión de Libros

CRUD completo
Carga de portadas
Carga de archivos digitales
Asignación de categorías múltiples


Gestión de Categorías

Crear/editar/eliminar
Slug automático
Asociación con libros


Gestión de Ventas

Ver todas las ventas
Actualizar estados de pago
Filtros y búsqueda


Gestión de Usuarios

Crear usuarios con roles
Listar todos los usuarios
Eliminar usuarios
Control de permisos




🐛 Troubleshooting
Problema: "Class 'PDO' not found"
Solución: Instalar extensión PHP PDO
bash# Ubuntu/Debian
sudo apt-get install php8.2-sqlite3

# Windows (descomentar en php.ini)
extension=pdo_sqlite
Problema: "Storage link already exists"
Solución:
bash# Eliminar enlace existente
rm public/storage

# Recrear enlace
php artisan storage:link
Problema: "CORS policy: No 'Access-Control-Allow-Origin'"
Solución:

Verificar que Laravel esté corriendo en http://127.0.0.1:8000
Verificar config/cors.php
Limpiar caché de configuración:

bashphp artisan config:clear
php artisan cache:clear
Problema: "Unauthenticated" en rutas protegidas
Solución:

Verificar que el token esté en localStorage
Verificar headers de Axios:

javascript// resources/js/api/axios.js
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})
Problema: Imágenes no se muestran
Solución:

Verificar enlace simbólico:

bashphp artisan storage:link

Verificar permisos:

bashchmod -R 775 storage
chmod -R 775 bootstrap/cache
Problema: "SQLSTATE[HY000]: General error: 1 no such table"
Solución: Ejecutar migraciones
bashphp artisan migrate:fresh
php artisan db:seed --class=RoleSeeder
Problema: Vue Router muestra página en blanco
Solución:

Verificar que Vite esté corriendo (npm run dev)
Verificar consola del navegador para errores
Limpiar caché del navegador (Ctrl + Shift + Delete)


📝 Scripts Útiles
bash# Desarrollo completo (backend + frontend + logs + queue)
composer run dev

# Solo backend
php artisan serve

# Solo frontend
npm run dev

# Build producción
npm run build

# Limpiar todo
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Resetear base de datos
php artisan migrate:fresh --seed

# Ver rutas API
php artisan route:list --path=api

# Generar usuario admin manualmente
php artisan tinker
>>> $user = \App\Models\User::create([
...   'name' => 'Admin',
...   'email' => 'admin@example.com',
...   'password' => \Hash::make('password'),
...   'role_id' => 1
... ]);

📄 Licencia
Este proyecto está bajo la licencia MIT.

👥 Contribuir
Las contribuciones son bienvenidas. Por favor:

Fork el proyecto
Crea una rama para tu feature (git checkout -b feature/nueva-caracteristica)
Commit tus cambios (git commit -m 'Agregar nueva característica')
Push a la rama (git push origin feature/nueva-caracteristica)
Abre un Pull Request


📞 Soporte
Si encuentras algún problema o tienes preguntas:

Abre un Issue en GitHub
Revisa la sección de Troubleshooting
Consulta la documentación de Laravel y Vue.js


¡Gracias por usar Librería Digital! 📚✨
