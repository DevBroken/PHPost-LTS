# Changelog

Todos los cambios relevantes de este proyecto se documentan en este archivo.

El formato sigue [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/) y
el proyecto usa [Versionado Semántico](https://semver.org/lang/es/).

Este registro arranca en el último cambio de 2022 y documenta el trabajo del
fork LTS. El historial anterior permanece en el historial de Git del proyecto.

## [Unreleased]

### Añadido

- Autoloader propio que resuelve el namespace `DevBroken\Utils\…` sobre `inc/`, en lugar de `require_once` manuales.
- Clase `Sources`, que centraliza la resolución de rutas de assets y tema según la extensión del archivo.
- `CssCompiler`, que genera `theme.css` a partir de las utilidades realmente usadas en las plantillas.
- Sistema de diseño en tres capas: `tokens.css`, `utilities.css` y `components.css`.
- Menú de navegación reescrito y plantillas nuevas de cabecera, pie y menú.
- Modifier `metrica`, que abrevia números grandes (`1.500.000` → `1.5M`).
- `tsCore::navigate()` como reemplazo de `redirectTo()`.
- Traducción al español de la licencia MIT en `LICENSE.es.md`.

### Cambiado

- Las plantillas resuelven assets con `$Sources->assets()`, `theme()`, `route()` y `svg()` en lugar del array `$tsRoutes`.
- Los recursos estáticos del tema se mueven a `frontend/assets`: imágenes, sonidos y scripts.
- `tsCore` deja de exponer `getRoutes()` y `createRoute()`.
- Las clases de dominio reciben sus dependencias por constructor, en lugar de usar `global`.
- Los controladores usan niveles de acceso por nombre (`miembro`, `moderador`, `administrador`) en lugar de enteros.
- Las reglas de `.htaccess` dejan de usar el prefijo `/media`.
- La licencia pasa a la MIT canónica; la atribución y las marcas se separan en `ATTRIBUTION.md`.
- **Cambio incompatible:** los estados de publicación pasan a `1 = activo` (`post_status`, `f_status`, `c_status`). Requiere ejecutar la migración de `upgrade/` junto con el despliegue.

### Corregido

- Rutas de assets rotas tras la reorganización: iconos, banderas, categorías, medallas y rangos.
- `register.js` apuntaba a `/media/assets/…`, una ruta que el servidor ya no sirve.
- `Sources` no asignaba `$tsCore` en su constructor, dejando la propiedad tipada sin inicializar.
- `BBCode` recibía `tsCore` cuando esperaba `Sources`, lo que rompía el renderizado de posts, comentarios y firmas.

### Eliminado

- La capa legacy `inc/php/ajax`, duplicada por el enrutador modular de `inc/ajax`.
- El editor wysibb (CSS, fuente de iconos y JavaScript); el editor pasa a ser un textarea plano.
- El CSS por página del tema: `admin`, `auth`, `fotos`, `live`, `mensajes`, `moderacion`, `perfil`, `portal`, `registro` y `wysibb`.
- Los modifiers `getUrl`, `kmg`, `rtrim`, `strlen` y `ucfirst`, sin uso en plantillas.

## [1.0.0] - 2026-11-06

### Añadido

- Asistente de instalación para PHP 8.1+ sobre PDO, con pasos validados y progreso en tiempo real.
- Asistente de actualización adaptativo: inspecciona el esquema existente, crea índices, claves y columnas faltantes sin duplicar datos, y es re-ejecutable.
- Copia de seguridad automática de la base de datos antes de cada actualización.
- Esquema centralizado que se adapta a la versión de MySQL/MariaDB detectada.
- Contenedor de servicios e infraestructura propia: HTTP, paginación keyset/cursor, peticiones y seguridad.
- Fachada de base de datos multi-driver con MySQLi y PDO.
- Almacenamiento binario de direcciones IPv4 e IPv6.
- Hash de contraseñas moderno con migración transparente de los hashes MD5 al iniciar sesión.
- Envío de correo con PHPMailer, SMTP y plantillas dinámicas.
- Bootstrap de aplicación, `tsHome`, `tsSession` y `tsVisitas`.
- Endpoints AJAX modulares para administración y acciones de usuario.
- Estructura `frontend/` para temas, autenticación y AJAX.
- Dependencias vendorizadas (JBBCode, PHPMailer y Smarty) con autoloader propio.

### Cambiado

- Smarty se actualiza y queda encapsulado en `tsSmarty` con tipado estricto.
- Registro y usuarios migran a inyección de dependencias y consultas preparadas.
- La configuración se guarda en JSON nativo en lugar de cadenas de texto.
- El manejo de variables globales se reescribe para no mutar el entorno global.
- `.htaccess` se reordena e incorpora cabeceras de seguridad.
- Las carpetas del sistema se reorganizan (`inc/ext` → `inc/extras`) y los instaladores se unifican.
- `index.php` comprueba si el sitio está instalado antes de continuar.

### Corregido

- Inconsistencias de mayúsculas/minúsculas en rutas que rompían la ejecución en Linux.
- El panel de administración reiniciaba a cero los puntos del usuario al guardar cambios.
- El inicio de sesión automático fallaba tras confirmar la activación de una cuenta nueva.

### Seguridad

- Bypass de autenticación en el login que permitía acceder sin verificar la contraseña.

### Eliminado

- Motor de plantillas obsoleto, sustituido por Smarty vendorizado.
- Plugins de Smarty sin uso, assets duplicados y configuraciones muertas.
- `license.txt` original, reemplazado por `LICENSE`.

[Unreleased]: https://github.com/DevBroken/PHPost-LTS/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/DevBroken/PHPost-LTS/releases/tag/v1.0.0
