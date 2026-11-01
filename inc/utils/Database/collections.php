<?php

declare(strict_types=1);

/**
 * @package   PHPost_Risus_LTS
 * @author    PHPost Risus LTS 2026
 * @copyright 2027
 */

// Adaptación multi-versión del esquema (MySQL/MariaDB)
require_once __DIR__ . '/schema.php';

/**
 * Columnas JSON registradas con sus dos formas: por expresión (MySQL >= 8.0.13)
 * y como texto (VARCHAR, para el resto de servidores). Dentro de los CREATE
 * aparecen como {{JSON:nombre}} y se resuelven al ejecutar con
 * phpostSchemaBuild($sql, phpostSchemaCaps($pdo)).
 *
 * El ancho (2º argumento) solo se usa en el modo compatible: es el espacio
 * máximo para el valor por defecto + lo que la aplicación escriba después.
 */
phpostJsonRegister('p_configs', [
    'muro'             => 'registered',
    'muro_firma'       => 'followers',
    'mensaje_privado'  => 'registered',
], 4096);

phpostJsonRegister('last_posts_visited', [], 2048);
phpostJsonRegister('last_posts_shared', [], 2048);
phpostJsonRegister('last_posts_cats', [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15], 2048);

phpostJsonRegister('c_monitor', [
    // Mis Posts
    'post_favs'          => true,
    'post_comments'      => true,
    'post_points'        => true,
    // Usuarios que sigo
    'follow_new'         => true,
    'follow_posts'       => true,
    'follow_shares'      => true,
    'follow_photos'      => true,
    // Posts que sigo
    'bookmark_comments'  => true,
    // Mis Comentarios
    'comment_votes'      => true,
    'comment_replies'    => true,
    // Mis Fotos
    'photo_comments'     => true,
    // Mi Perfil (Muro)
    'wall_posts'         => true,
    'wall_comments'      => true,
    'wall_likes'         => true,
], 4096);

phpostJsonRegister('r_allows', (object) [], 4096);

// ##### FOTOS ##### //
$collections['Fotos'] = "CREATE TABLE IF NOT EXISTS `f_fotos` (
    `foto_id`       BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `f_user`        BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `f_title`       VARCHAR(140) NOT NULL DEFAULT '',
    `f_description` TEXT NULL,
    `f_url`         VARCHAR(255) NOT NULL DEFAULT '',
    `f_closed`      TINYINT(1) NOT NULL DEFAULT 0,
    `f_visitas`     BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `f_votos_pos`   BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `f_votos_neg`   BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `f_status`      TINYINT(1) NOT NULL DEFAULT 1,
    `f_last`        TINYINT(1) NOT NULL DEFAULT 0,
    `f_hits`        INT UNSIGNED NOT NULL DEFAULT 0,
    `f_date`        INT UNSIGNED NOT NULL DEFAULT 0,
    `f_ip`          VARBINARY(16) NOT NULL,
    -- INDICE
    INDEX indice_usuario (f_user),
    INDEX indice_estado_fecha (f_status, f_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$collections['Fotos comentarios'] = "CREATE TABLE IF NOT EXISTS `f_comentarios` (
    `cid`       BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `c_foto_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `c_user`    BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `c_date`    INT UNSIGNED NOT NULL DEFAULT 0,
    `c_body`    TEXT NOT NULL,
    `c_ip`      VARBINARY(16) NOT NULL,
    -- INDICE
    INDEX indice_foto_fecha (c_foto_id, c_date),
    INDEX indice_usuario (c_user)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$collections['Fotos votos'] = "CREATE TABLE IF NOT EXISTS `f_votos` (
    `vid`       BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `v_foto_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `v_user`    BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `v_type`    TINYINT(1) NOT NULL DEFAULT 0,
    `v_date`    INT UNSIGNED NOT NULL DEFAULT 0,
    -- INDICE
    INDEX indice_foto_tipo (v_foto_id, v_type),
    -- CLAVE UNICA
    UNIQUE KEY uq_usuario_foto_tipo (v_user, v_foto_id, v_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

// ##### POSTS ##### //
$collections['Posts'] = "CREATE TABLE IF NOT EXISTS `p_posts` (
    `post_id`               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `post_user`             BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `post_category`         INT UNSIGNED NOT NULL DEFAULT 0,
    `post_portada`          VARCHAR(255) NOT NULL DEFAULT '',
    `post_title`            VARCHAR(255) NOT NULL DEFAULT '',
    `post_slug`             VARCHAR(255) NOT NULL DEFAULT '',
    `post_body`             TEXT NOT NULL,
    `post_fuentes`          VARCHAR(255) NOT NULL DEFAULT '',
    `post_tags`             VARCHAR(180) NOT NULL DEFAULT '',
    `post_puntos`           BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `post_comments`         BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `post_seguidores`       BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `post_shared`           BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `post_favoritos`        BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `post_hits`             BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `post_cache`            INT UNSIGNED NOT NULL DEFAULT 0,
    `post_private`          TINYINT(1) NOT NULL DEFAULT 0,
    `post_block_comments`   TINYINT(1) NOT NULL DEFAULT 0,
    `post_sponsored`        TINYINT(1) NOT NULL DEFAULT 0,
    `post_sticky`           TINYINT(1) NOT NULL DEFAULT 0,
    `post_smileys`          TINYINT(1) NOT NULL DEFAULT 0,
    `post_visitantes`       TINYINT(1) NOT NULL DEFAULT 0,
    `post_status`           TINYINT(1) NOT NULL DEFAULT 1,
    `post_date`             INT UNSIGNED NOT NULL DEFAULT 0,
    `post_ip`               VARBINARY(16) NOT NULL,
    FULLTEXT buscar_texto_en (post_title, post_body, post_tags),
    FULLTEXT INDEX solo_tags (post_tags)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$collections['Posts borradores'] = "CREATE TABLE IF NOT EXISTS `p_borradores` (
    `bid`               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `b_user`            BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `b_post_id`         BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `b_category`        INT UNSIGNED NOT NULL DEFAULT 0,
    `b_portada`         VARCHAR(255) NOT NULL DEFAULT '',
    `b_title`           VARCHAR(255) NOT NULL DEFAULT '',
    `b_slug`            VARCHAR(255) NOT NULL DEFAULT '',
    `b_body`            TEXT NOT NULL,
    `b_fuentes`         VARCHAR(255) NOT NULL DEFAULT '',
    `b_tags`            VARCHAR(180) NOT NULL DEFAULT '',
    `b_puntos`          BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `b_comments`        BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `b_seguidores`      BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `b_shared`          BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `b_favoritos`       BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `b_hits`            BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `b_cache`           INT UNSIGNED NOT NULL DEFAULT 0,
    `b_private`         TINYINT(1) NOT NULL DEFAULT 0,
    `b_block_comments`  TINYINT(1) NOT NULL DEFAULT 0,
    `b_sponsored`       TINYINT(1) NOT NULL DEFAULT 0,
    `b_sticky`          TINYINT(1) NOT NULL DEFAULT 0,
    `b_smileys`         TINYINT(1) NOT NULL DEFAULT 0,
    `b_visitantes`      TINYINT(1) NOT NULL DEFAULT 0,
    `b_status`          TINYINT(1) NOT NULL DEFAULT 0,
    `b_date`            INT UNSIGNED NOT NULL DEFAULT 0,
    `b_ip`              VARBINARY(16) NULL DEFAULT NULL,
    `b_causa`           VARCHAR(255) NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$collections['Posts categorias'] = "CREATE TABLE IF NOT EXISTS `p_categorias` (
    `cid`       BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `c_orden`   INT NOT NULL DEFAULT 0,
    `c_nombre`  VARCHAR(50) NOT NULL,
    `c_seo`     VARCHAR(50) NOT NULL DEFAULT '',
    `c_img`     VARCHAR(100) NOT NULL DEFAULT 'comments.png',
    -- CLAVE UNICA
    UNIQUE KEY uq_seo (c_seo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$collections['Posts comentarios'] = "CREATE TABLE IF NOT EXISTS `p_comentarios` (
    `cid`       BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `c_post_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `c_user`    BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `c_date`    INT UNSIGNED NOT NULL DEFAULT 0,
    `c_body`    TEXT NOT NULL,
    `c_votos`   INT UNSIGNED NOT NULL DEFAULT 0,
    `c_status`  TINYINT(1) NOT NULL DEFAULT 1,
    `c_ip`      VARBINARY(16) NOT NULL,
    -- INDICES
    INDEX indice_post_id (c_post_id),
    INDEX indice_usuario_id (c_user)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$collections['Posts favoritos'] = "CREATE TABLE IF NOT EXISTS `p_favoritos` (
    `fav_id`        BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `fav_user`      BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `fav_post_id`   BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `fav_date`      INT UNSIGNED NOT NULL DEFAULT 0,
    -- INDICES
    INDEX indice_post_id (fav_post_id),
    INDEX indice_usuario_id (fav_user),
    -- CLAVE UNICA
    UNIQUE KEY uq_usuario_post (fav_user, fav_post_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$collections['Posts votos'] = "CREATE TABLE IF NOT EXISTS `p_votos` (
    `voto_id`   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tid`       BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `tuser`     BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `cant`      BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `type`      TINYINT(1) NOT NULL DEFAULT '1',
    `date`      INT UNSIGNED NOT NULL DEFAULT 0,
    -- INDICES
    INDEX indice_post_id (tid),
    INDEX indice_usuario_id (tuser),
    -- CLAVE UNICA
    UNIQUE KEY uq_usuario_voto (tuser, tid, type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

// ##### USUARIOS ##### //

$collections['Miembros'] = "CREATE TABLE IF NOT EXISTS `u_miembros` (
    `user_id`           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_name`         VARCHAR(32) NOT NULL DEFAULT 'Unknown',
    `user_password`     VARCHAR(255) NOT NULL DEFAULT '', -- Legacy: md5(md5(pass).strtolower(nick)); vacío en cuentas nuevas
    `user_password_hash` VARCHAR(255) NOT NULL DEFAULT '', -- Vigente: password_hash() (bcrypt)
    `user_email`        VARCHAR(255) NOT NULL DEFAULT '',
    `user_rango`        INT NOT NULL DEFAULT 3,
    `user_puntos`       BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `user_posts`        BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `user_comentarios`  BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `user_seguidores`   BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `user_cache`        INT UNSIGNED NOT NULL DEFAULT 0,
    `user_puntosxdar`   BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `user_bad_hits`     BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `user_nextpuntos`   INT UNSIGNED NOT NULL DEFAULT 0,
    `user_registro`     INT UNSIGNED NOT NULL DEFAULT 0,
    `user_lastlogin`    INT UNSIGNED NOT NULL DEFAULT 0,
    `user_lastactive`   INT UNSIGNED NOT NULL DEFAULT 0,
    `user_lastpost`     INT UNSIGNED NOT NULL DEFAULT 0,
    `user_last_ip`      VARBINARY(16) NULL DEFAULT NULL,
    `user_name_changes` TINYINT(1) NOT NULL DEFAULT 3,
    `user_activo`       TINYINT(1) NOT NULL DEFAULT 1,
    `user_vip`          TINYINT(1) NOT NULL DEFAULT 0,
    `user_baneado`      TINYINT(1) NOT NULL DEFAULT 0,
    -- INDICES
    INDEX indice_ultima_actividad (user_lastactive),
    INDEX indice_fecha_registro (user_registro),
    -- CLAVES UNICAS
    UNIQUE KEY uq_usuario_nickname (user_name),
    UNIQUE KEY uq_usuario_email (user_email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$collections['Usuarios actividad'] = "CREATE TABLE IF NOT EXISTS `u_actividad` (
    `ac_id`     BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`   BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `obj_uno`   BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `obj_dos`   BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `ac_type`   TINYINT(2) NOT NULL DEFAULT 0,
    `ac_date`   INT UNSIGNED NOT NULL DEFAULT 0,
    -- INDICES
    INDEX indice_usuario_fecha (user_id, ac_date),
    INDEX indice_tipo_target (ac_type, obj_uno),
    INDEX indice_fecha_reciente (ac_date DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$collections['Usuarios avisos'] = "CREATE TABLE IF NOT EXISTS `u_avisos` (
    `av_id`         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`       BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `av_subject`    VARCHAR(24) NOT NULL DEFAULT 'Sin asunto!',
    `av_body`       TEXT NOT NULL,
    `av_date`       INT UNSIGNED NOT NULL DEFAULT 0,
    `av_read`       TINYINT(1) NOT NULL DEFAULT 0,
    `av_type`       TINYINT(1) NOT NULL DEFAULT 0,
    -- INDICES
    INDEX indice_usuario_fecha (user_id, av_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$collections['Usuarios bloqueos'] = "CREATE TABLE IF NOT EXISTS `u_bloqueos` (
    `bid`       BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `b_user`    BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `b_auser`   BIGINT UNSIGNED NOT NULL DEFAULT 0,
    -- INDICES
    INDEX indice_usuario (b_user),
    INDEX indice_bloqueado (b_auser),
    -- CLAVE UNICA
    UNIQUE KEY uq_usuario_bloqueado (b_user, b_auser)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$collections['Usuarios follows'] = "CREATE TABLE IF NOT EXISTS `u_follows` (
    `follow_id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `f_user`    BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `f_id`      BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `f_type`    TINYINT(1) NOT NULL DEFAULT 0,
    `f_date`    INT UNSIGNED NOT NULL DEFAULT 0,
    -- INDICES
    INDEX indice_usuario_tipo (f_user, f_type),
    INDEX indice_target_tipo (f_id, f_type),
    -- CLAVE UNICA
    UNIQUE KEY uq_usuario_target_tipo (f_user, f_id, f_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$collections['Usuarios mensajes'] = "CREATE TABLE IF NOT EXISTS `u_mensajes` (
    `mp_id`             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `mp_to`             BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `mp_from`           BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `mp_answer`         TINYINT(1) NOT NULL DEFAULT 0,
    `mp_read_to`        TINYINT(1) NOT NULL DEFAULT 0,
    `mp_read_from`      TINYINT(1) NOT NULL DEFAULT 1,
    `mp_read_mon_to`    TINYINT(1) NOT NULL DEFAULT 0,
    `mp_read_mon_from`  TINYINT(1) NOT NULL DEFAULT 1,
    `mp_del_to`         TINYINT(1) NOT NULL DEFAULT 0,
    `mp_del_from`       TINYINT(1) NOT NULL DEFAULT 0,
    `mp_subject`        VARCHAR(60) NOT NULL DEFAULT '',
    `mp_preview`        VARCHAR(100) NOT NULL DEFAULT '',
    `mp_date`           INT UNSIGNED NOT NULL DEFAULT 0,
    -- INDICES
    INDEX idx_para_fecha (mp_to, mp_date DESC),
    INDEX idx_de_fecha (mp_from, mp_date DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$collections['Usuarios respuestas'] = "CREATE TABLE IF NOT EXISTS `u_respuestas` (
    `mr_id`     BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `mp_id`     BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `mr_from`   BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `mr_body`   TEXT NOT NULL,
    `mr_ip`     VARBINARY(16) NOT NULL,
    `mr_date`   INT UNSIGNED NOT NULL DEFAULT 0,
    -- INDICES
    INDEX idx_mensaje_fecha (mp_id, mr_date ASC),
    INDEX idx_autor_respuesta (mr_from)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$collections['Usuarios nicks'] = "CREATE TABLE IF NOT EXISTS `u_nicks` (
    `id`            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`       BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `user_email`    VARCHAR(255) NOT NULL,
    `name_1`        VARCHAR(32) NOT NULL,
    `name_2`        VARCHAR(32) NOT NULL,
    `hash`          VARCHAR(72) NOT NULL, -- Antiguamente para reemplazar password
    `time`          BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `ip`            VARBINARY(16) NOT NULL,
    `estado`        TINYINT(1) NOT NULL DEFAULT 0,
    -- INDICES
    INDEX indice_usuario_fecha (user_id, `time` DESC),
    INDEX indice_estado_pendiente (estado, `time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$collections['Usuarios monitor'] = "CREATE TABLE IF NOT EXISTS `u_monitor` (
    `not_id`        BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`       BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `obj_user`      BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `obj_uno`       BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `obj_dos`       BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `obj_tres`      BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `not_type`      TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    `not_date`      INT UNSIGNED NOT NULL DEFAULT 0,
    `not_total`     TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
    `not_menubar`   TINYINT(1) UNSIGNED NOT NULL DEFAULT 2,
    `not_monitor`   TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
    INDEX indices_usuario_leida_fecha (user_id, not_menubar, not_date DESC),
    INDEX indices_fecha_limpieza (not_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$collections['Usuarios muro'] = "CREATE TABLE IF NOT EXISTS `u_muro` (
    `pub_id`        BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `p_user`        BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `p_user_pub`    BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `p_date`        INT UNSIGNED NOT NULL DEFAULT 0,
    `p_comments`    BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `p_body`        TEXT NOT NULL,
    `p_likes`       BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `p_type`        TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
    `p_ip`          VARBINARY(16) NOT NULL,
    -- INDICES
    INDEX indice_muro_fecha (p_user, p_date DESC),
    INDEX indice_autor_fecha (p_user_pub, p_date DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$collections['Usuarios muro_adjuntos'] = "CREATE TABLE IF NOT EXISTS `u_muro_adjuntos` (
    `adj_id`    BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `pub_id`    BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `a_title`   VARCHAR(100) NOT NULL,
    `a_url`     TEXT NOT NULL,
    `a_img`     TEXT NOT NULL,
    `a_desc`    TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$collections['Usuarios muro_comentarios'] = "CREATE TABLE IF NOT EXISTS `u_muro_comentarios` (
    `cid`       BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `pub_id`    BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `c_user`    BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `c_date`    INT UNSIGNED NOT NULL DEFAULT 0,
    `c_body`    TEXT NOT NULL,
    `c_likes`   BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `c_ip`      VARBINARY(16) NOT NULL,
    -- INDICES
    INDEX indice_publicacion_fecha (pub_id, c_date ASC),
    INDEX indice_autor (c_user)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$collections['Usuarios muro_likes'] = "CREATE TABLE IF NOT EXISTS `u_muro_likes` (
    `like_id`   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`   BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `obj_id`    BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `obj_type`  TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    -- INDICES Y PROTECCIÓN CONTRA DUPLICADOS
    INDEX indice_objeto_tipo (obj_id, obj_type),
    UNIQUE KEY uq_usuario_objeto_tipo (user_id, obj_id, obj_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

// m: muro | mf: muro firmar | rmp: recibir mensajes privado
$collections['Usuarios perfil'] = "CREATE TABLE IF NOT EXISTS `u_perfil` (
    `user_id`       BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `user_dia`      TINYINT(2) NOT NULL DEFAULT 0,
    `user_mes`      TINYINT(2) NOT NULL DEFAULT 0,
    `user_ano`      TINYINT(4) NOT NULL DEFAULT 0,
    `user_pais`     CHAR(2) NOT NULL DEFAULT 'XX', -- Código ISO de 2 letras (ej: AR, MX, ES)
    `user_estado`   TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
    `user_sexo`     TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    `user_firma`    TEXT NULL DEFAULT NULL,
    `user_avatar`   VARCHAR(255) NOT NULL DEFAULT 'avatar.png', -- ¡Nuevo!: Para ruta completa del avatar
    `user_portada`  VARCHAR(255) NOT NULL DEFAULT 'cover.png', -- ¡Nuevo!: Portada del perfil
    `user_modo`     ENUM('light', 'dark', 'dimmer') NOT NULL DEFAULT 'light', -- ¡Nuevo!: Dark/light/dimmer
    `p_nombre`      VARCHAR(50) NOT NULL DEFAULT '',
    `p_avatar`      TINYINT(1) NOT NULL DEFAULT 0, -- Innecesario actualmente
    `p_mensaje`     VARCHAR(160) NOT NULL DEFAULT '',
    `p_sitio`       VARCHAR(60) NOT NULL DEFAULT '',
    `p_socials`     TEXT NULL DEFAULT NULL,
    `p_configs`     {{JSON:p_configs}},
    PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$collections['Usuarios portal'] = "CREATE TABLE IF NOT EXISTS `u_portal` (
    `user_id`               BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `last_posts_visited`    {{JSON:last_posts_visited}}, -- Inicializa vacío []
    `last_posts_shared`     {{JSON:last_posts_shared}}, -- Inicializa vacío []
    `last_posts_cats`       {{JSON:last_posts_cats}},
    `c_monitor`             {{JSON:c_monitor}},
    PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$collections['Sistema rangos'] = "CREATE TABLE IF NOT EXISTS `u_rangos` (
    `rango_id`  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `r_name`    VARCHAR(32) NOT NULL DEFAULT '',
    `r_slug`    VARCHAR(32) NOT NULL DEFAULT '', -- ¡Nuevo!
    `r_color`   VARCHAR(9) NOT NULL DEFAULT '171717',
    `r_image`   VARCHAR(32) NOT NULL DEFAULT 'new.png',
    `r_cant`    INT NOT NULL DEFAULT 0,
    `r_allows`  {{JSON:r_allows}},
    `r_type`    TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$collections['Sistema sessions'] = "CREATE TABLE IF NOT EXISTS `u_sessions` (
    `session_id`        VARCHAR(72) NOT NULL,
    `session_user_id`   BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `session_ip`        VARBINARY(16) NOT NULL,
    `session_time`      INT NOT NULL DEFAULT 0,
    `session_autologin` TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`session_id`),
    KEY sesion_usuario (`session_user_id`),
    KEY sesion_actualizada (`session_time`),
    -- INDICES
    INDEX indice_usuario_actividad (session_user_id, session_time DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$collections['Sistema suspension'] = "CREATE TABLE IF NOT EXISTS `u_suspension` (
    `susp_id`       BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`       BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `susp_causa`    TEXT NOT NULL,
    `susp_date`     INT NOT NULL DEFAULT 0,
    `susp_termina`  INT NOT NULL DEFAULT 0,
    `susp_mod`      BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `susp_ip`       VARBINARY(16) NOT NULL,
    KEY indice_usuario_suspension (user_id), -- ¡Crítico para el Login!
    KEY indice_fecha_termina (susp_termina)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$collections['Sistema afiliados'] = "CREATE TABLE IF NOT EXISTS `w_afiliados` (
    `aid`           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `a_titulo`      VARCHAR(120) NOT NULL DEFAULT '',
    `a_url`         VARCHAR(255) NOT NULL DEFAULT '',
    `a_banner`      VARCHAR(255) NOT NULL DEFAULT '',
    `a_descripcion` VARCHAR(255) NOT NULL DEFAULT '',
    `a_sid`         BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `a_hits_in`     BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `a_hits_out`    BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `a_date`        INT UNSIGNED NOT NULL DEFAULT 0,
    `a_active`      TINYINT(1) NOT NULL DEFAULT 0,
    -- INDICES
    INDEX indice_afiliado (a_titulo, a_date DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

// Se debería dividir
$collections['Sistema configuracion'] = "CREATE TABLE IF NOT EXISTS `w_configuracion` (
    `tscript_id`        TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `titulo`            VARCHAR(60) NOT NULL DEFAULT '',
    `slogan`            VARCHAR(150) NOT NULL DEFAULT '',
    `url`               VARCHAR(255) NOT NULL DEFAULT '',
    `email`             VARCHAR(255) NOT NULL DEFAULT '',
    `banner`            VARCHAR(255) NOT NULL DEFAULT '',
    `tema`              VARCHAR(32) NOT NULL DEFAULT 'default',
    `ads_300`           TEXT NULL,
    `ads_468`           TEXT NULL,
    `ads_160`           TEXT NULL,
    `ads_728`           TEXT NULL,
    `ads_search`        VARCHAR(50) NOT NULL DEFAULT 'partner-pub-5535725517227860:7900040286',
    `c_type_paginator`  ENUM('paginas','ver_mas') NOT NULL DEFAULT 'paginas',
    `c_last_active`     TINYINT(1) NOT NULL DEFAULT 0,
    `c_allow_sess_ip`   TINYINT NOT NULL DEFAULT 15,
    `c_count_guests`    TINYINT(1) NOT NULL DEFAULT 1,
    `c_reg_active`      TINYINT(1) NOT NULL DEFAULT 0,
    `c_reg_activate`    TINYINT(1) NOT NULL DEFAULT 1,
    `c_reg_rango`       TINYINT NOT NULL DEFAULT 3,
    `c_met_welcome`     TINYINT(1) NOT NULL DEFAULT 0,
    `c_message_welcome` VARCHAR(500) NOT NULL DEFAULT 'Hola [usuario], [welcome] a [b][web][/b].',
    `c_fotos_private`   BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `c_hits_guest`      TINYINT(1) NOT NULL DEFAULT 0,
    `c_keep_points`     TINYINT(1) NOT NULL DEFAULT 0,
    `c_allow_points`    BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `c_allow_edad`      TINYINT UNSIGNED NOT NULL DEFAULT 13,
    `c_max_posts`       TINYINT NOT NULL DEFAULT 20,
    `c_max_com`         TINYINT NOT NULL DEFAULT 30,
    `c_max_nots`        TINYINT NOT NULL DEFAULT 50,
    `c_max_acts`        TINYINT NOT NULL DEFAULT 25,
    `c_newr_type`       BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `c_allow_sump`      BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `c_allow_firma`     TINYINT(1) NOT NULL DEFAULT 1,
    `c_allow_upload`    TINYINT(1) NOT NULL DEFAULT 0,
    `c_allow_portal`    TINYINT(1) NOT NULL DEFAULT 1,
    `c_allow_live`      TINYINT(1) NOT NULL DEFAULT 1,
    `c_see_mod`         TINYINT(1) NOT NULL DEFAULT 0,
    `c_stats_cache`     TINYINT NOT NULL DEFAULT 15,
    `c_desapprove_post` TINYINT(1) NOT NULL DEFAULT 0,
    `offline`           TINYINT(1) NOT NULL DEFAULT 0,
    `offline_message`   VARCHAR(255) NOT NULL DEFAULT 'Estamos en mantenimiento',
    `pkey`              VARCHAR(72) NOT NULL DEFAULT '',
    `skey`              VARCHAR(72) NOT NULL DEFAULT '',
    `version`           VARCHAR(32) NOT NULL DEFAULT 'PHPost Risus LTS',
    `version_code`      VARCHAR(32) NOT NULL DEFAULT 'phpost_risus_lts',
    PRIMARY KEY (`tscript_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$collections['Sistema denuncias'] = "CREATE TABLE IF NOT EXISTS `w_denuncias` (
    `did`       BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `obj_id`    BIGINT UNSIGNED NOT NULL,
    `d_user`    BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `d_razon`   TINYINT UNSIGNED NOT NULL,
    `d_extra`   TEXT NOT NULL,
    `d_total`   TINYINT(1) NOT NULL DEFAULT 1,
    `d_type`    TINYINT UNSIGNED NOT NULL,
    `d_date`    INT UNSIGNED NOT NULL DEFAULT 0,
    -- INDICES
    KEY indice_usuario_denuncia (d_user),
    INDEX indice_objeto_tipo (obj_id, d_type),
    INDEX indice_fecha (d_date DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$collections['Sistema contacts'] = "CREATE TABLE IF NOT EXISTS `w_contacts` (
    `id`            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`       BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `user_email`    VARCHAR(255) NOT NULL DEFAULT '',
    `time`          INT UNSIGNED NOT NULL DEFAULT 0,
    `type`          TINYINT(1) NOT NULL DEFAULT 0,
    `hash`          VARCHAR(72) NOT NULL DEFAULT '',
    -- ÍNDICES DE RENDIMIENTO
    UNIQUE KEY indice_token (`hash`),
    KEY indice_usuario_tipo (user_id, type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$collections['Sistema medallas'] = "CREATE TABLE IF NOT EXISTS `w_medallas` (
    `medal_id`          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `m_autor`           BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `m_title`           VARCHAR(125) NOT NULL,
    `m_description`     VARCHAR(255) NOT NULL DEFAULT '',
    `m_image`           VARCHAR(120) NOT NULL,
    `m_cant`            BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `m_type`            TINYINT(1) NOT NULL DEFAULT 0,
    `m_cond_user`       BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `m_cond_user_rango` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `m_cond_post`       BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `m_cond_foto`       BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `m_date`            BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `m_total`           BIGINT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$collections['Sistema medallas asiganada'] = "CREATE TABLE IF NOT EXISTS `w_medallas_assign` (
    `id`         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `medal_id`   BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `medal_for`  BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `medal_date` INT UNSIGNED NOT NULL DEFAULT 0,
    `medal_ip`   VARBINARY(16) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$collections['Sistema historial'] = "CREATE TABLE IF NOT EXISTS `w_historial` (
    `id`        BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `pofid`     BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `mod`       BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `type`      TINYINT(1) NOT NULL DEFAULT 0,
    `action`    TINYINT(1) NOT NULL DEFAULT 0,
    `reason`    TEXT NOT NULL,
    `date`      BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `mod_ip`    VARBINARY(16) NOT NULL,
    -- INDICES
    INDEX indice_objeto_tipo (pofid, type),
    INDEX indice_mod_fecha (`mod`, `date` DESC),
    INDEX indice_fecha_global (`date` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$collections['Sistema noticias'] = "CREATE TABLE IF NOT EXISTS `w_noticias` (
    `not_id`     BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `not_autor`  BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `not_body`   TEXT NOT NULL,
    `not_date`   INT UNSIGNED NOT NULL DEFAULT 0,
    `not_active` TINYINT(1) NOT NULL DEFAULT 0,
    `not_color`  VARCHAR(9) NOT NULL DEFAULT '171717', -- ¡Nuevo! Asignar color a la noticia
    -- INDICES
    INDEX indice_noticias_activas (not_active, not_date DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$collections['Sistema lista negra'] = "CREATE TABLE IF NOT EXISTS `w_blacklist` (
    `id`        BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `type`      TINYINT(1) NOT NULL DEFAULT 0,
    `value`     VARCHAR(255) NOT NULL,
    `reason`    VARCHAR(255) NOT NULL,
    `author`    BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `date`      INT UNSIGNED NOT NULL DEFAULT 0,
    -- ÍNDICE DE RENDIMIENTO Y SEGURIDAD
    UNIQUE KEY indice_bloqueo_unico (`type`, `value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$collections['Sistema censuras'] = "CREATE TABLE IF NOT EXISTS `w_badwords` (
    `wid`       BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `word`      VARCHAR(255) NOT NULL,
    `swop`      VARCHAR(255) NOT NULL,
    `method`    TINYINT(1) NOT NULL DEFAULT 0,
    `type`      TINYINT(1) NOT NULL DEFAULT 0,
    `author`    BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `reason`    VARCHAR(255) NOT NULL DEFAULT '',
    `date`      INT UNSIGNED NOT NULL DEFAULT 0,
    -- ÍNDICE DE RENDIMIENTO
    KEY indice_palabra (word)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$collections['Sistema estadisticas'] = "CREATE TABLE IF NOT EXISTS `w_stats` (
    `stats_no`              TINYINT(1) NOT NULL DEFAULT 0,
    `stats_max_online`      BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `stats_max_time`        INT UNSIGNED NOT NULL DEFAULT 0,
    `stats_time`            INT UNSIGNED NOT NULL DEFAULT 0,
    `stats_time_cache`      INT UNSIGNED NOT NULL DEFAULT 0,
    `stats_time_foundation` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `stats_time_upgrade`    BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `stats_miembros`        BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `stats_posts`           BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `stats_fotos`           BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `stats_comments`        BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `stats_foto_comments`   BIGINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`stats_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$collections['Sistema temas'] = "CREATE TABLE IF NOT EXISTS `w_temas` (
    `tid`       BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `t_name`    VARCHAR(255) NOT NULL DEFAULT 'Default',
    `t_url`     VARCHAR(255) NOT NULL DEFAULT '/themes/default',
    `t_path`    VARCHAR(255) NOT NULL DEFAULT 'default',
    `t_copy`    VARCHAR(255) NOT NULL DEFAULT 'PHPost Risus LST'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$collections['Sistema visitas'] = "CREATE TABLE IF NOT EXISTS `w_visitas` (
    `id`    BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user`  BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `for`   BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `type`  TINYINT(1) NOT NULL DEFAULT 0,
    `date`  INT UNSIGNED NOT NULL DEFAULT 0,
    `ip`    VARBINARY(16) NOT NULL,
    -- INDICES
    INDEX indice_objeto_tipo_fecha (`for`, `type`, `user` DESC),
    INDEX indice_usuario_fecha (`user`, `date` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

// ##### INSERCIONES ##### //
$collections['categorias'] = "INSERT INTO `p_categorias` (`cid`, `c_orden`, `c_nombre`, `c_seo`, `c_img`) VALUES
(1,  1,  'Arte y Diseño', 'arte_y_diseno', 'palette.png'),
(2,  2,  'Ciencia y Educación', 'ciencia_y_educacion', 'lab.png'),
(3,  3,  'Cultura Pop y Anime', 'cultura_pop_y_anime', 'manga.png'),
(4,  4,  'Deportes', 'deportes', 'sport.png'),
(5,  5,  'Entretenimiento y Humor', 'entretenimiento', 'humor.png'),
(6,  6,  'Hazlo tú mismo', 'hazlo_tu_mismo', 'escuadra.png'),
(7,  7,  'Juegos', 'juegos', 'controller.png'),
(8,  8,  'Música', 'musica', 'music.png'),
(9,  9,  'Noticias y Actualidad', 'noticias', 'newspaper.png'),
(10, 10, 'Off Topic', 'off_topic', 'comments.png'),
(11, 11, 'Salud y Estilo de Vida', 'salud_y_bienestar', 'heart.png'),
(12, 12, 'Tecnología y Motores', 'tecnologia', 'phone.png'),
(13, 13, 'Turismo y Viajes', 'turismo', 'brujula.png'),
(14, 14, 'Economía y Negocios', 'economia_y_negocios', 'economy.png'),
(15, 15, 'Unknown', 'unknown', 'tscript.png');";

$permiso_base = [
    'suad'    => false, 'sumo'  => false, 'godp'   => false, 'gopp' => false,
    'gopcp'   => false, 'govpp' => false, 'govpn'  => false, 'goepc' => false,
    'godpc'   => false, 'gopf'  => false, 'gopcf'  => false, 'gorpap' => false,
    'govwm'   => false, 'moacp' => false, 'mocdu'  => false, 'mocdf' => false,
    'mocdp'   => false, 'moadm' => false, 'mocdm'  => false, 'movub' => false,
    'moub'    => false, 'morp'  => false, 'morf'   => false, 'mocp' => false,
    'mocc'    => false, 'most'  => false, 'moayca' => false, 'movcud' => false,
    'movcus'  => false, 'mosu'  => false, 'modu'   => false, 'moep' => false,
    'moedpo'  => false, 'moop'  => false, 'mocepc' => false, 'moedcopo' => false,
    'moaydcp' => false, 'moecp' => false, 'moef'   => false, 'moedfo' => false,
    'moecf'   => false, 'moepm' => false, 'moecm'  => false,
    'goaf'    => 20,    'gopfp' => 0,     'gopfd'  => 0
];
$permiso_comun = [
    'godp'  => true, 'gopp'  => true, 'gopcp' => true, 'govpp' => true, 'govpn' => true,
    'goepc' => true, 'godpc' => true, 'gopf'  => true, 'gopcf' => true
];

$rango_admin = json_encode(array_merge($permiso_base, [
    'suad'    => true, 'govwm'  => true, 'moacp'  => true, 'mocdu'  => true, 'mocdf'    => true,
    'mocdp'   => true, 'moadm'  => true, 'mocdm'  => true, 'movub'  => true, 'moub'     => true,
    'morp'    => true, 'morf'   => true, 'mocp'   => true, 'mocc'   => true, 'most'     => true,
    'moayca'  => true, 'movcud' => true, 'movcus' => true, 'mosu'   => true, 'modu'     => true,
    'moep'    => true, 'moedpo' => true, 'moop'   => true, 'mocepc' => true, 'moedcopo' => true,
    'moaydcp' => true, 'moecp'  => true, 'moef'   => true, 'moedfo' => true, 'moecf'    => true,
    'moepm'   => true, 'moecm'  => true,
    'goaf'    => 5,    'gopfp'  => 20,   'gopfd'  => 50
]));
$rango_mod = json_encode(array_merge($permiso_base, [
    'sumo'    => true, 'govwm'  => true, 'moacp'  => true, 'mocdu'  => true, 'mocdf'    => true,
    'mocdp'   => true, 'moadm'  => true, 'mocdm'  => true, 'movub'  => true, 'moub'     => true,
    'morp'    => true, 'morf'   => true, 'mocp'   => true, 'mocc'   => true, 'most'     => true,
    'moayca'  => true, 'movcud' => true, 'movcus' => true, 'mosu'   => true, 'modu'     => true,
    'moep'    => true, 'moedpo' => true, 'moop'   => true, 'mocepc' => true, 'moedcopo' => true,
    'moaydcp' => true, 'moecp'  => true, 'moef'   => true, 'moedfo' => true, 'moecf'    => true,
    'moepm'   => true, 'moecm'  => true,
    'goaf'    => 15,   'gopfp'  => 18,   'gopfd'  => 30
]));
$rango_novato    = json_encode(array_merge($permiso_base, $permiso_comun, ['gopfp' => 5,  'gopfd' => 5]));
$rango_nfu       = json_encode(array_merge($permiso_base, $permiso_comun, ['gopfp' => 10, 'gopfd' => 10]));
$rango_fulluser  = json_encode(array_merge($permiso_base, $permiso_comun, ['gopfp' => 12, 'gopfd' => 20]));
$rango_greatuser = json_encode(array_merge($permiso_base, $permiso_comun, ['gopfp' => 12, 'gopfd' => 15]));
$rango_golduser  = json_encode(array_merge($permiso_base, $permiso_comun, ['gopfp' => 12, 'gopfd' => 25]));

// 4. Armamos la colección convirtiendo los arrays directamente a JSON texto para MySQL
$collections['Rangos'] = "INSERT INTO `u_rangos` (`rango_id`, `r_name`, `r_slug`, `r_color`, `r_image`, `r_cant`, `r_type`, `r_allows`) VALUES
(1, 'Administrador', 'admin', 'D6030B', 'rosette.png', 0, 0, '{$rango_admin}'),
(2, 'Moderador', 'mod', 'ff9900', 'shield.png', 0, 0, '{$rango_mod}'),
(3, 'Novato', 'novato', '171717', 'new.png', 0, 0, '{$rango_novato}'),
(4, 'New Full User', 'nfu', '0198E7', 'star_bronze_3.png', 50, 1, '{$rango_nfu}'),
(5, 'Full User', 'fulluser', '00ccff', 'star_silver_3.png', 70, 1, '{$rango_fulluser}'),
(6, 'Great User', 'greatuser', '01A021', 'star_gold_3.png', 0, 0, '{$rango_greatuser}'),
(7, 'Gold User', 'golduser', 'cc6600', 'asterisk_yellow.png', 120, 1, '{$rango_golduser}');";

$collections['Temas'] = "INSERT INTO `w_temas` (`tid`) VALUES (1);";
$collections['Configuracion'] = "INSERT INTO `w_configuracion` (`tscript_id`) VALUES (1);";
$collections['Estadisticas'] = "INSERT INTO `w_stats` (`stats_no`, `stats_max_online`) VALUES (1, 0);";