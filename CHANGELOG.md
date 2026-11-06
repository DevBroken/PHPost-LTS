# CHANGELOG: PHPost Risus LTS (2027)

### Añadido (Added)
 * **Arquitectura de Base de Datos Unificada**:
    - Esquema centralizado y compartido que automatiza la instalación y actualización según la versión de MySQL/MariaDB detectada.
 * **Asistentes Interactivos (/install y /upgrade)**:
    - Interfaz fluida y moderna con barra de progreso en tiempo real controlada por colas de tareas AJAX.
 * **Seguridad de Contraseñas de Última Generación**:
    - Migración automática y transparente de hashes MD5 antiguos a algoritmos modernos durante el inicio de sesión, sin forzar reseteos de claves.
 * **Infraestructura Core por Dominios**:
    - Organización modular del código en capas dedicadas para Inyección de Dependencias, HTTP, Paginación avanzada (Keyset/Cursor), Peticiones y Seguridad.
 * **Capa de Abstracción de Datos Alternativa**:
    - Soporte opcional para controladores PDO/MySQLi mediante una fachada multi-driver unificada.
 * **Abstracción Avanzada de IPs**:
    - Conversión e almacenamiento nativo de direcciones IPv4 e IPv6 a formato binario para optimizar espacio y rendimiento.
 * **Copias de Seguridad Automatizadas**:
    - Resguardo automático del estado previo de la base de datos antes de iniciar cualquier proceso de actualización.
 * **Integración de Correos**:
    - Incorporación nativa del motor PHPMailer para envíos estables del sistema.

---

### Cambiado (Changed)
 * **Modernización del Modelo de Datos**:
    - Migración de configuraciones complejas de texto plano o cadenas pesadas a tipos de datos estructurados JSON nativos.
 * **Migraciones Adaptativas No Destructivas**:
    - El actualizador es re-ejecutable; inspecciona la base de datos existente e inyecta índices, claves únicas o columnas faltantes sin alterar ni duplicar los datos actuales.
 * **Saneamiento del Flujo de Peticiones**:
    - El manejo de variables globales fue reescrito para evitar mutaciones directas en el entorno global.
 * **Estandarización de Directorios**:
    - Reestructuración de carpetas del sistema hacia un esquema de almacenamiento más limpio y nomenclatura de licencias estándar.

---

### Corregido (Fixed)
 * **Vulnerabilidad Crítica de Autenticación**:
    - Se corrigió un fallo de seguridad severo en el proceso de login que permitía accesos indebidos saltándose la verificación de contraseñas.
 * **Compatibilidad Estricta en Servidores Linux**:
    - Corrección masiva de rutas e inconsistencias de mayúsculas/minúsculas (case-sensitivity) que rompían la ejecución en entornos de producción basados en Linux.
 * **Integridad de Datos en Administración**:
    - Corrección del error que restablecía a cero el conteo de puntos de un usuario al guardar modificaciones desde el panel de control.
 * **Flujo de Activación Automatizado**:
    - Reparado el inicio de sesión automático que fallaba inmediatamente después de confirmar la activación de una cuenta nueva.

---

### Eliminado (Removed)
 * **Mapeos de Datos Heredados**:
    - Eliminación de métodos de consultas múltiples peligrosos o sin uso dentro del sistema.
 * **Redundancia de Código**:
    - Remoción de archivos de configuración inicial estáticos y scripts duplicados entre los instaladores.
