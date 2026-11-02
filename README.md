# PHPost Risus LTS (2027)

![PHP Version](https://img.shields.io/badge/PHP-8.5.12--1-617CBE?style=plastic)
![Smarty Version](https://img.shields.io/badge/Smarty-5.8.4-E99521?style=plastic)
![Database](https://shields.io/badge/Database-MySQLi-2092B3?style=plastic)
![License](https://shields.io/badge/Licencia-MIT-323232?style=plastic)

**PHPost Risus LTS** es una plataforma de comunidad y marcadores sociales inspirada en el clásico estilo de redes como *Taringa!*. Cuenta con sistemas completos de publicaciones, fotos, muros, mensajería privada, rangos, medallas y un panel avanzado de moderación.

Esta versión representa un esfuerzo de modernización comunitaria sobre el motor clásico de PHPost, adaptándolo a estándares modernos de seguridad, rendimiento y compatibilidad con entornos PHP actuales.

---

## Características Destacadas (LTS)

* **Infraestructura Moderna:** Núcleo reestructurado por dominios con Inyección de Dependencias, capa HTTP nativa y paginación optimizada (Keyset/Cursor).
* **Seguridad Reforzada:** Almacenamiento seguro de contraseñas con hashing moderno y migración transparente, mitigación de fallos críticos de autenticación y encriptación binaria de IPs.
* **Capa de Datos Híbrida:** Fachada multi-driver con soporte nativo para MySQLi y compatibilidad extendida mediante PDO.
* **Asistentes Fluidos:** Procesos de instalación y actualización guiados en tiempo real mediante colas de tareas asíncronas (AJAX).

---

## Requisitos del Sistema

Antes de comenzar, asegúrate de que tu servidor cumpla con lo siguiente:

* **PHP:** Versión **8.1** o superior.
* **Extensiones PHP Requeridas:** `pdo_mysql`, `mysqli`, `json`, `mbstring` e `intl`.
* **Base de Datos:** MySQL o MariaDB *(el esquema inteligente se adapta automáticamente a la versión de tu servidor)*.
* **Servidor Web:** Apache con `mod_rewrite` habilitado (o configuración equivalente en Nginx).

---

## Instalación desde Cero

1. **Descarga el código:** Clona este repositorio o sube los archivos directamente a tu directorio raíz.
2. **Prepara el entorno:** Crea una base de datos vacía configurada preferentemente con el juego de caracteres `utf8mb4`.
3. **Ejecuta el asistente:** Accede desde tu navegador a `://tu-sitio.com` y sigue los pasos en pantalla.
4. **Finalización:** Una vez concluido el proceso, **elimina por completo la carpeta `/install`** por motivos de seguridad.

---

## Proceso de Actualización (Upgrade)

Si vienes de una versión anterior de PHPost Risus, puedes migrar de forma segura:

1. **Resguardo obligatorio:** Realiza un **backup completo** de tus archivos y de la base de datos actual antes de proceder.
2. **Ejecuta la migración:** Sube los archivos nuevos y accede desde tu navegador a `://tu-sitio.com`.
3. **Migración transparente:** El actualizador es inteligente; no altera tu contenido histórico (usuarios, posts, fotos, comentarios) y modernizará los hashes de las contraseñas de tus usuarios de forma automática la próxima vez que inicien sesión.
4. **Finalización:** Al terminar, **elimina por completo la carpeta `/upgrade`**.

---

## Comunidad y Soporte

¡Las contribuciones y los reportes de errores son fundamentales para mantener este proyecto vivo!

* **Reportar Errores o Sugerencias:**
  * Repositorio Original (Isidro): [Issues GitHub](https://github.com/isidromlc/PHPost/issues)
  * Repositorio DevBroken: [Issues GitHub](https://github.com/DevBroken/PHPost/issues)
* **Enviar Pull Requests:** Si deseas colaborar con código, abre un PR detallando el cambio, cómo probarlo y confirmando si afecta a la estructura de datos existente.

---

> ⚠️ **Nota de Seguridad:** Si descubres una vulnerabilidad crítica de seguridad, te solicitamos reportarla de manera privada a los mantenedores antes de hacerla pública.