# 🛒 E-Commerce API

Una robusta API RESTful para plataforma de e-commerce publicitario desarrollada con Laravel 12, que permite la gestión completa de campañas publicitarias, medios, proveedores y sistemas de pago.

<p align="center">
<img src="https://img.shields.io/badge/Laravel-12.0-red?style=for-the-badge&logo=laravel" alt="Laravel 12">
<img src="https://img.shields.io/badge/PHP-8.2-blue?style=for-the-badge&logo=php" alt="PHP 8.2">
<img src="https://img.shields.io/badge/MySQL-Database-orange?style=for-the-badge&logo=mysql" alt="MySQL">
<img src="https://img.shields.io/badge/Swagger-API_Docs-green?style=for-the-badge&logo=swagger" alt="Swagger">
</p>

## 📋 Tabla de Contenidos

- [Características](#-características)
- [Requisitos](#-requisitos)
- [Instalación](#-instalación)
- [Configuración](#-configuración)
- [Uso](#-uso)
- [API Endpoints](#-api-endpoints)
- [Documentación API](#-documentación-api)
- [Pruebas](#-pruebas)
- [Contribución](#-contribución)
- [Licencia](#-licencia)

## ✨ Características

### 🎯 Funcionalidades Principales
- **Gestión de Medios Publicitarios**: CRUD completo para medios con catálogo filtrable
- **Sistema de Campañas**: Creación y gestión de campañas publicitarias con validación de fechas
- **Gestión de Proveedores**: Control de proveedores de medios publicitarios
- **Sistema de Pagos**: Procesamiento de pagos con validaciones de negocio
- **Reglas de Precio**: Sistema dinámico de descuentos con fechas de validez
- **Items de Campaña**: Asignación de medios a campañas con cálculo automático de precios

### 🔧 Características Técnicas
- **API RESTful** con arquitectura escalable
- **Autenticación Bearer Token** con Laravel Sanctum
- **Documentación Swagger/OpenAPI 3.0** completa
- **Validación robusta** de datos de entrada
- **Manejo de errores** centralizado
- **Sistema de roles** (Admin, Client, Provider)
- **Cálculo automático de precios** con aplicación de descuentos
- **Validación de disponibilidad** para evitar conflictos de reserva

## 📋 Requisitos

- **PHP** >= 8.2
- **Composer** >= 2.0
- **Node.js** >= 18.0 y **npm** >= 9.0
- **MySQL** >= 8.0 (o MariaDB >= 10.4)
- **Git** para el control de versiones

### Extensiones PHP Requeridas
- OpenSSL
- PDO
- Mbstring
- Tokenizer
- XML
- Ctype
- JSON
- BCMath
- Fileinfo

## 🚀 Instalación

### 1. Clonar el Repositorio
```bash
git clone https://github.com/Nannis96/e-commerce.git
cd e-commerce
```

### 2. Instalar Dependencias
```bash
# Dependencias de PHP
composer install

# Dependencias de Node.js
npm install
```

### 3. Configuración del Entorno
```bash
# Copiar archivo de configuración
cp .env.example .env

# Generar clave de aplicación
php artisan key:generate
```

### 4. Configurar Base de Datos
Edita el archivo `.env` con tus credenciales de base de datos:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ecommerce
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_contraseña
```

### 5. Ejecutar Migraciones
```bash
# Crear la base de datos (asegúrate de que exista)
# Ejecutar migraciones y seeders
php artisan migrate --seed
```

### 6. Publicar Configuración de Swagger
```bash
php artisan vendor:publish --provider="L5Swagger\L5SwaggerServiceProvider"
```

### 7. Generar Documentación API
```bash
php artisan l5-swagger:generate
```

## ⚙️ Configuración

### Variables de Entorno Importantes

```env
# Aplicación
APP_NAME="E-Commerce API"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Base de Datos
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ecommerce
DB_USERNAME=root
DB_PASSWORD=

# Sanctum (Autenticación)
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1

# Swagger (Opcional)
L5_SWAGGER_GENERATE_ALWAYS=true
L5_SWAGGER_OPEN_API_SPEC_VERSION=3.0.0
```

### Configuración de Permisos (Linux/macOS)
```bash
# Permisos para directorios de storage y cache
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

## 🎮 Uso

### Desarrollo Local

#### Opción 1: Comando Artisan Simple
```bash
php artisan serve
```
La aplicación estará disponible en `http://localhost:8000`

#### Opción 2: Entorno de Desarrollo Completo
```bash
# Ejecuta servidor, queue, logs y vite concurrentemente
composer run dev
```

Este comando iniciará:
- 🌐 Servidor web en `http://localhost:8000`
- 📊 Worker de colas
- 📝 Monitor de logs
- ⚡ Vite para assets front-end

# Optimizar para producción
php artisan config:cache
php artisan route:cache
php artisan view:cache
composer install --no-dev --optimize-autoloader
```

## 🌐 API Endpoints

### 🔐 Autenticación
```
POST   /api/auth/login          # Iniciar sesión
POST   /api/auth/register       # Registrar usuario
POST   /api/auth/logout         # Cerrar sesión
```

### 📺 Medios Publicitarios
```
GET    /api/media               # Listar medios (con filtros)
POST   /api/media               # Crear medio
GET    /api/media/{id}          # Obtener medio específico
PUT    /api/media/{id}          # Actualizar medio
DELETE /api/media/{id}          # Eliminar medio
GET    /api/media/catalog       # Catálogo con filtros avanzados
```

### 💰 Reglas de Precio
```
GET    /api/price-rules         # Listar reglas de precio
POST   /api/price-rules         # Crear regla de precio
GET    /api/price-rules/{id}    # Obtener regla específica
PUT    /api/price-rules/{id}    # Actualizar regla
DELETE /api/price-rules/{id}    # Eliminar regla
```

### 📅 Campañas
```
GET    /api/campaigns           # Listar campañas
POST   /api/campaigns           # Crear campaña
GET    /api/campaigns/{id}      # Obtener campaña específica
PUT    /api/campaigns/{id}      # Actualizar campaña
DELETE /api/campaigns/{id}      # Eliminar campaña
POST   /api/campaigns/{id}/cancel # Cancelar campaña
```

### 📋 Items de Campaña
```
GET    /api/campaign-items      # Listar items de campaña
POST   /api/campaign-items      # Crear item de campaña
GET    /api/campaign-items/{id} # Obtener item específico
PUT    /api/campaign-items/{id} # Actualizar item
DELETE /api/campaign-items/{id} # Eliminar item
POST   /api/campaign-items/{id}/accept  # Aceptar (Proveedor)
POST   /api/campaign-items/{id}/reject  # Rechazar (Proveedor)
```

### 💳 Pagos
```
GET    /api/payments            # Listar pagos
POST   /api/payments            # Procesar pago
GET    /api/payments/{id}       # Obtener pago específico
```

## 📚 Documentación API

### Swagger UI
Una vez que tengas el proyecto ejecutándose, puedes acceder a la documentación completa de la API en:

```
http://localhost:8000/api/documentation
```

### Características de la Documentación
- ✅ **Especificación OpenAPI 3.0** completa
- ✅ **Interfaz Swagger UI** interactiva
- ✅ **Ejemplos de request/response** para cada endpoint
- ✅ **Esquemas de validación** detallados
- ✅ **Autenticación Bearer Token** documentada
- ✅ **Códigos de error** explicados

### Regenerar Documentación
```bash
php artisan l5-swagger:generate
```

## 🔧 Comandos Útiles

### Artisan Commands
```bash
# Limpiar caché
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Verificar estado del sistema
php artisan about

# Ejecutar seeders específicos
php artisan db:seed --class=UserSeeder

# Refrescar base de datos
php artisan migrate:fresh --seed
```

### Estándares de Código
- Seguir **PSR-12** para PHP
- Usar **Laravel Pint** para formateo: `./vendor/bin/pint`
- Escribir **pruebas** para nuevas funcionalidades
- Documentar cambios en **Swagger annotations**

## 🐛 Resolución de Problemas

### Problemas Comunes

#### Error de Permisos
```bash
sudo chown -R $USER:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

#### Error de Base de Datos
```bash
# Verificar conexión
php artisan tinker
DB::connection()->getPdo();
```

#### Error de Swagger
```bash
# Regenerar documentación
php artisan l5-swagger:generate
```

### Logs
```bash
# Ver logs en tiempo real
tail -f storage/logs/laravel.log

# O usando Pail (incluido en el proyecto)
php artisan pail
```

## 📄 Licencia

Este proyecto está licenciado bajo la [Licencia MIT](https://opensource.org/licenses/MIT).

## 👥 Créditos

Desarrollado con ❤️ usando:
- [Laravel Framework](https://laravel.com/)
- [L5-Swagger](https://github.com/DarkaOnLine/L5-Swagger) para documentación API
- [Laravel Sanctum](https://laravel.com/docs/sanctum) para autenticación
- [Pest PHP](https://pestphp.com/) para testing

---

<p align="center">
Hecho con ❤️ para simplificar la gestión de campañas publicitarias
</p>
