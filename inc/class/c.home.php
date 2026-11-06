<?php

declare(strict_types=1);

/**
 * @author PHPost Risus LTS
 * @copyright 2027
 */

defined('TS_HEADER') OR exit('No se permite el acceso directo al script');

class tsHome {

    protected tsCore $tsCore;
    protected tsUser $tsUser;

    public string $category;

    /**
     * POSTS ESTADOS
     * Oculto = 0
     * Publicado = 1
     * Eliminado = 2 (soft siguen en la base de datos)
     * Revision = 3
     *
     * COMENTARIOS ESTADOS:
     * Inactivo = 0
     * Publicado = 1
    */

    public function __construct(tsCore $tsCore, tsUser $tsUser)
    {
        $this->tsCore = $tsCore;
        $this->tsUser = $tsUser;
    }

    /*
        getCatData()
        :: OBTENER DATOS DE UNA CATEGORIA
    */
    public function getCategoryData(string $category = '') {
        $category = htmlentities(trim($this->category ?? $category));
        $data = db_exec('fetch_assoc', db_exec([__FILE__, __LINE__], 'query', "SELECT cid, c_orden, c_nombre, c_seo, c_img FROM p_categorias WHERE c_seo = '{$category}' LIMIT 1"));
        return $data;
    }

    private function canAccessModMode(string $add = ''): string
    {
        if(!$this->tsUser->canAccessModMode()) {
            $isActive = Constants::useConst('usuario');
            return $add . " AND u.user_activo = {$isActive} AND u.user_baneado = 0";
        }
        return '';
    }

    private function canAccessModModePost(): string
    {
        if ($this->tsUser->canAccessModMode()) {
            return 'AND p.post_id > 0';
        }
        $isActive = Constants::useConst('post');
        return 'AND p.post_status = ' . $isActive . $this->canAccessModMode();
    }

    private function getLastPostsCategory(): string
    {
        if (empty($this->category)) {
            return '';
        }
        $categoria = $this->getCategoryData();
        $cat_id = (int)($categoria['cid'] ?? 0);
        return $cat_id > 0 ? "AND p.post_category = {$cat_id}" : '';
    }

    /*
    getTotalPosts() :: CUENTA EL TOTAL DE POSTS ACTIVOS SEGÚN FILTROS Y CATEGORÍA
    */
    protected function getTotalPosts(): int
    {
        $conditions = [];
        // Filtrado por Categoría (Reutiliza tu lógica actual)
        $conditions[] = $this->getLastPostsCategory();
        // Solo contamos posts normales (no stickies/fijados) para la paginación principal
        $conditions[] = "AND p.post_sticky = 0";
        // Permisos de moderación y estado público (Tus funciones de control)
        $conditions[] = $this->canAccessModModePost();
        // Unificar las condiciones, es 1 = 1 porque comienza con AND
        $whereSql = "WHERE 1 = 1 " . implode(' ', array_filter($conditions));
        // Ejecutar un conteo directo y ligero
        $result = db_exec('fetch_assoc', db_exec([__FILE__, __LINE__], 'query', "SELECT COUNT(p.post_id) AS total FROM p_posts AS p LEFT JOIN u_miembros AS u ON p.post_user = u.user_id {$whereSql}"));
        return (int)($result['total'] ?? 0);
    }

    /*
        getLastPosts($category, $sticky)
    */
    public function getLastPosts(bool $sticky = false, ?string $cursorRaw = null): array
    {
        // Post por página
        $perPage = (int)($this->tsCore->settings['c_max_posts'] ?? 20);
        // Detectar la preferencia del administrador ('paginas' o 'ver_mas')
        $tipoPaginacion = $this->tsCore->settings['c_type_paginator'] ?? 'paginas';
        //
        $conditions = [];
        // Filtrado por Categoría
        $conditions[] = $this->getLastPostsCategory();
        // Filtrado por Sticky / Destacados
        $conditions[] = "AND p.post_sticky = " . ($sticky ? '1' : '0');
        // Permisos de moderación y estado público
        $conditions[] = $this->canAccessModModePost();
        // Otras variables
        $orderField = $sticky ? 'p.post_sponsored' : 'p.post_id';
        $legacyMeta = [];
        // Lógica de Paginación por Cursor (Solo si no es Sticky)
        if ($sticky) {
            $limitQuery = "LIMIT 10";
        } else {
            // DIVISION DE LOGICA SQL
            if ($tipoPaginacion === 'ver_mas') {
                // Lógica de Cursor (Pide N+1 elementos)
                $cursorId = Paginador::decodeCursor($cursorRaw) ?? $cursorRaw;
                $cursorId = $cursorId !== null ? (int)$cursorId : null;
                $paginadorData = Paginador::cursor($cursorId, 'p.post_id', $perPage, 'desc');
                if (!empty($paginadorData['where'])) {
                    $whereQuery = str_replace(':cursor', (string)$paginadorData['params'][':cursor'], $paginadorData['where']);
                    $conditions[] = "AND " . $whereQuery;
                }
                $limitQuery = "LIMIT " . $paginadorData['limit'];
            } else {
                // Lógica Tradicional
                $totalPosts = $this->getTotalPosts();
                $legacyMeta = Paginador::fromRequest($totalPosts, $perPage);
                $limitQuery = "LIMIT " . $legacyMeta['limit'];
            }
        }

        // Unificar las condiciones de la consulta
        $whereSql = "WHERE 1 = 1 " . implode(' ', array_filter($conditions));

        // Ejecutar la Consulta Principal
        $rows = result_array(db_exec([__FILE__, __LINE__], 'query', "SELECT p.post_id, p.post_user, p.post_category, p.post_title, p.post_slug, p.post_date, p.post_comments, p.post_puntos, p.post_private, p.post_sponsored, p.post_status, p.post_sticky, u.user_id, u.user_name, u.user_activo, u.user_baneado, c.c_nombre, c.c_seo, c.c_img FROM p_posts AS p LEFT JOIN u_miembros AS u ON p.post_user = u.user_id LEFT JOIN p_categorias AS c ON c.cid = p.post_category {$whereSql} GROUP BY p.post_id ORDER BY {$orderField} DESC {$limitQuery}"));
        if ($sticky) {
            return ['data' => $rows];
        }
        // Retornamos usando el empaquetador dinámico
        return Paginador::empaquetar($rows, $perPage, 'post_id', $tipoPaginacion, $legacyMeta);
    }

    /*
        getLastComentarios()
        : PARA EL PORTAL
    */
    public function getLastComentarios() {
        $postStatus = Constants::useConst('post');
        $commentStatus = Constants::useConst('comentario');
        $isAdmod = $this->canAccessModMode("WHERE p.post_status = {$postStatus} AND cm.c_status = {$commentStatus}");

        $query = db_exec([__FILE__, __LINE__], 'query', "SELECT cm.cid, cm.c_status, u.user_name, u.user_activo, u.user_baneado, p.post_id, p.post_title, p.post_status, c.c_seo FROM p_comentarios AS cm LEFT JOIN u_miembros AS u ON cm.c_user = u.user_id LEFT JOIN p_posts AS p ON p.post_id = cm.c_post_id LEFT JOIN p_categorias AS c ON c.cid = p.post_category $isAdmod ORDER BY cid DESC LIMIT 10");
        if(!$query) exit( show_error('Error al ejecutar la consulta de la l&iacute;nea '.__LINE__.' de '.__FILE__.'.', 'db') );
        $result = result_array($query);
        //
        return $result;
    }

}