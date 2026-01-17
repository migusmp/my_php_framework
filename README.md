# php-vanilla-server

**My own PHP vanilla server** — un “mini framework” estilo Laravel/Symfony pero **sin magia**, pensado para aprender y tener control total: **Router**, **Request/Response**, **MVC**, **middlewares**, **validación**, **sesiones/flash**, **CSRF**, configuración con **.env** y una base lista para crecer.

> Este README documenta lo que hemos ido construyendo: una base sólida para apps PHP modernas, manteniendo el proyecto simple, explícito y escalable.

---

## Objetivos del proyecto

- Tener una base **MVC** clara y mantenible.
- Routing potente: **grupos**, **prefix**, **middlewares**, rutas **GET/POST/PUT/DELETE**, parámetros dinámicos.
- Abstracciones mínimas pero útiles: **Request**, **Response**, helpers.
- Seguridad por defecto: **CSRF**, cookies/sesiones, validación consistente.
- Estructura preparada para “crecer”: Services/Repositories, módulos, CLI tipo *artisan*.

---

## Features (hasta ahora)

- ✅ **Front Controller** (`public/index.php`) como único punto de entrada.
- ✅ **Router** con:
  - Rutas GET/POST/PUT/DELETE
  - Grupos con `prefix()` y `middleware()`
  - Parámetros dinámicos tipo `/users/{id}`
  - 404 / 405 manejados de forma centralizada
- ✅ **Request / Response**
  - Acceso limpio a query params, body, headers, cookies
  - Helpers de respuesta: `json()`, `view()`, `redirect()`, status codes
- ✅ **MVC**
  - Controladores (Controllers)
  - Vistas (Views) con render simple y layout
- ✅ **Middlewares**
  - Middleware global / por ruta / por grupo
  - Base para Auth, Admin, etc.
- ✅ **Sesiones + Flash**
  - Mensajes flash (success/error) para redirects
- ✅ **CSRF**
  - Generación/validación de token en formularios
- ✅ **Validación**
  - `Validator` con reglas típicas (required, email, min, max, etc.)
  - Errores por campo y retorno consistente
- ✅ **Config con .env**
  - Carga de variables de entorno (Dotenv)
  - `config()` centralizado (app/db/session, etc.)
- 🧱 Base preparada para:
  - Services/Repositories
  - Auth/roles
  - Migraciones / ORM / Query builder (roadmap)

---

## Requisitos

- PHP **8.1+** (recomendado 8.2+)
- Composer (si estás usando autoload + dotenv)
- Extensiones típicas:
  - `pdo` + driver de tu DB (`pdo_mysql` / `pdo_pgsql` / `pdo_sqlite`)
  - `mbstring`, `openssl`

---

## Quick start

### 1) Clonar e instalar
```bash
git clone <tu-repo>
cd php-vanilla-server
composer install
