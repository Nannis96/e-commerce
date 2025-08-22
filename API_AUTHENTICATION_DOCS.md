# API Authentication Documentation

Esta API utiliza Laravel Sanctum para la autenticación basada en tokens. A continuación se detallan todos los endpoints disponibles.

## Base URL
```
http://localhost:8000/api
```

## Endpoints de Autenticación

### 1. Registro de Usuario
**POST** `/auth/register`

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Body:**
```json
{
    "name": "Juan Pérez",
    "email": "juan@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

**Respuesta Exitosa (201):**
```json
{
    "success": true,
    "message": "User registered successfully",
    "data": {
        "user": {
            "id": 1,
            "name": "Juan Pérez",
            "email": "juan@example.com"
        },
        "token": "1|abcdef123456...",
        "token_type": "Bearer"
    }
}
```

### 2. Inicio de Sesión
**POST** `/auth/login`

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Body:**
```json
{
    "email": "juan@example.com",
    "password": "password123"
}
```

**Respuesta Exitosa (200):**
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 1,
            "name": "Juan Pérez",
            "email": "juan@example.com"
        },
        "token": "2|ghijkl789012...",
        "token_type": "Bearer"
    }
}
```

**Respuesta Error (401):**
```json
{
    "success": false,
    "message": "Invalid credentials"
}
```

### 3. Cerrar Sesión (Token Actual)
**POST** `/auth/logout`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {token}
```

**Respuesta Exitosa (200):**
```json
{
    "success": true,
    "message": "Logged out successfully"
}
```

### 4. Cerrar Sesión en Todos los Dispositivos
**POST** `/auth/logout-all`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {token}
```

**Respuesta Exitosa (200):**
```json
{
    "success": true,
    "message": "Logged out from all devices successfully"
}
```

### 5. Obtener Perfil del Usuario
**GET** `/auth/profile`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {token}
```

**Respuesta Exitosa (200):**
```json
{
    "success": true,
    "message": "User profile retrieved successfully",
    "data": {
        "user": {
            "id": 1,
            "name": "Juan Pérez",
            "email": "juan@example.com",
            "created_at": "2025-08-22T10:30:00.000000Z",
            "updated_at": "2025-08-22T10:30:00.000000Z"
        }
    }
}
```

### 6. Refrescar Token
**POST** `/auth/refresh`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {token}
```

**Respuesta Exitosa (200):**
```json
{
    "success": true,
    "message": "Token refreshed successfully",
    "data": {
        "token": "3|mnopqr345678...",
        "token_type": "Bearer"
    }
}
```

### 7. Obtener Usuario (Endpoint Original)
**GET** `/user`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {token}
```

**Respuesta Exitosa (200):**
```json
{
    "id": 1,
    "name": "Juan Pérez",
    "email": "juan@example.com",
    "email_verified_at": null,
    "created_at": "2025-08-22T10:30:00.000000Z",
    "updated_at": "2025-08-22T10:30:00.000000Z"
}
```

## Manejo de Errores

### Errores de Validación (422)
```json
{
    "success": false,
    "message": "Validation errors",
    "errors": {
        "email": ["El campo email es obligatorio."],
        "password": ["La contraseña debe tener al menos 8 caracteres."]
    }
}
```

### Error de Autenticación (401)
```json
{
    "success": false,
    "message": "Token not provided or invalid"
}
```

### Recurso No Encontrado (404)
```json
{
    "success": false,
    "message": "Resource not found"
}
```

### Método No Permitido (405)
```json
{
    "success": false,
    "message": "Method not allowed"
}
```

## Uso del Token

Una vez que obtienes un token al registrarte o iniciar sesión, debes incluirlo en el header `Authorization` de todas las peticiones protegidas:

```
Authorization: Bearer {tu_token_aqui}
```

## Ejemplos con cURL

### Registro:
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Juan Pérez",
    "email": "juan@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

### Login:
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "juan@example.com",
    "password": "password123"
  }'
```

### Obtener perfil:
```bash
curl -X GET http://localhost:8000/api/auth/profile \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer TU_TOKEN_AQUI"
```

## Notas Importantes

1. **Todos los tokens son únicos** y se generan usando Laravel Sanctum
2. **Los tokens no tienen expiración** por defecto, pero puedes configurar una en `config/sanctum.php`
3. **Las validaciones están en español** para una mejor experiencia de usuario
4. **Todas las respuestas son en formato JSON** con una estructura consistente
5. **Los errores de validación** incluyen mensajes personalizados en español
