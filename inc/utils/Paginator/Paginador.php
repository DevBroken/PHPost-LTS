<?php

declare(strict_types=1);

/**
 * Paginador — PHPost Risus LTS
 *
 * Lógica portada de tsCore (setPageLimit, setMaximos, getPages,
 * getPagination, pageIndex) a clase estática sin estado, más dos
 * modos nuevos:
 * - Offset/limit clásico con página saneada (sin offsets negativos).
 * - Cursor (keyset) para listados grandes: sin COUNT(*) ni OFFSET
 *   que se degrada, ideal para "ver más" infinito.
 *
 * @example Offset clásico
 * $pg = Paginador::fromRequest($total, 20);   // lee $_GET['page']
 * $rows = db(... "LIMIT {$pg['limit']}");
 *
 * @example Cursor (keyset sobre columna única ordenada)
 * $c = Paginador::cursor($_GET['cursor'] ?? null, 'post_id', 20);
 * // SELECT ... WHERE post_id < :cursor ORDER BY post_id DESC LIMIT 21
 * $page = Paginador::sliceCursor($rows, 20, 'post_id');
 * // $page = ['items'=>..., 'nextCursor'=>123, 'hasMore'=>true]
 */

if (!defined('TS_HEADER')) {
    exit('No se permite el acceso directo al script');
}

final class Paginador
{
    /* ---------- Offset/limit ---------- */

    /** Página actual saneada (>= 1). */
    public static function page(?string $key = null): int
    {
        $page = (int) ($_GET[$key ?? 'page'] ?? 1);
        return max(1, $page);
    }

    /**
     * Clásico "offset,limit" para SQL. Sujeta la página al rango válido
     * para no generar offsets negativos ni más allá del total.
     */
    public static function limit(int $perPage, ?int $total = null, ?string $key = null): string
    {
        $perPage = max(1, $perPage);
        $page = self::page($key);
        if ($total !== null && $total >= 0) {
            $pages = max(1, (int) ceil($total / $perPage));
            $page = min($page, $pages);
        }
        return (($page - 1) * $perPage) . ',' . $perPage;
    }

    /** Solo el offset (útil con query builders). */
    public static function offset(int $perPage, ?int $total = null, ?string $key = null): int
    {
        [$off] = explode(',', self::limit($perPage, $total, $key));
        return (int) $off;
    }

    /**
     * Datos de paginación clásica (compatible con getPages/getPagination).
     * @return array{current:int,pages:int,prev:int,next:int,limit:string,total:int,overflow:bool}
     */
    public static function fromRequest(int $total, int $perPage, ?string $key = null): array
    {
        $perPage = max(1, $perPage);
        $total = max(0, $total);
        $pages = max(1, (int) ceil($total / $perPage));
        $current = min(self::page($key), $pages);
        return [
            'current'  => $current,
            'pages'    => $pages,
            'section'  => $pages + 1,
            'prev'     => $current - 1,
            'next'     => ($current + 1) <= $pages ? $current + 1 : 0,
            'limit'    => (($current - 1) * $perPage) . ',' . $perPage,
            'total'    => $total,
            'overflow' => self::page($key) > $pages,
        ];
    }

    /* ---------- Cursor / keyset ---------- */

    /**
     * Condición WHERE para keyset sobre columna única (id/fecha).
     * Primera página: sin cursor, sin WHERE.
     * @return array{where:string,params:array<string,mixed>,limit:int}
     */
    public static function cursor(
        int|string|null $cursor,
        string $column = 'post_id',
        int $perPage = 20,
        string $dir = 'desc'
    ): array {
        $perPage = max(1, $perPage);
        $dir = strtolower($dir) === 'asc' ? 'ASC' : 'DESC';
        if ($cursor === null || $cursor === '') {
            return ['where' => '', 'params' => [], 'limit' => $perPage + 1, 'dir' => $dir];
        }
        $op = $dir === 'ASC' ? '>' : '<';
        return [
            'where'  => "{$column} {$op} :cursor",
            'params' => [':cursor' => $cursor],
            'limit'  => $perPage + 1,
            'dir'    => $dir,
        ];
    }

    /**
     * Corta la fila extra (n+1) y calcula el siguiente cursor.
     * @return array{items:array,nextCursor:int|string|null,hasMore:bool}
     */
    public static function sliceCursor(array $rows, int $perPage, string $idKey = 'post_id'): array
    {
        $hasMore = count($rows) > $perPage;
        $items = array_slice($rows, 0, $perPage);
        $last = end($items);
        return [
            'items'      => $items,
            'nextCursor' => ($hasMore && $last !== false) ? ($last[$idKey] ?? null) : null,
            'hasMore'    => $hasMore,
        ];
    }

    /** Cursor opaco (no expone el id real en la URL). */
    public static function encodeCursor(int|string $value): string
    {
        return rtrim(strtr(base64_encode(json_encode(['c' => $value])), '+/', '-_'), '=');
    }

    public static function decodeCursor(?string $cursor): int|string|null
    {
        if ($cursor === null || $cursor === '') {
            return null;
        }
        $json = base64_decode(strtr($cursor, '-_', '+/'));
        $data = is_string($json) ? json_decode($json, true) : null;
        return (is_array($data) && isset($data['c'])) ? $data['c'] : null;
    }

    /**
     * Procesa la paginación de forma dinámica según la configuración del sitio.
     *
     * @param array $rows Filas crudas devueltas por la base de datos (deben traer N+1 si es cursor).
     * @param int $perPage Elementos por página.
     * @param string $idKey Clave primaria para el cursor (ej. 'post_id').
     * @param string $tipo 'paginas' o 'ver_mas'.
     * @return array Estructura unificada para el sistema de plantillas (Smarty/Blade).
     */
    public static function empaquetar(array $rows, int $perPage, string $idKey = 'post_id', string $tipo = 'paginas', array $legacyMeta = []): array
    {
        if ($tipo === 'ver_mas') {
            $sliced = self::sliceCursor($rows, $perPage, $idKey);
            return [
                'tipo'       => 'ver_mas',
                'items'      => $sliced['items'],
                'hasMore'    => $sliced['hasMore'],
                'nextCursor' => $sliced['nextCursor'] ? self::encodeCursor($sliced['nextCursor']) : null,
                'pages'      => null,
                'current'    => null,
                'meta'       => null
            ];
        }

        // Modo Tradicional (Offset/Limit)
        // En el modo tradicional, las filas ya vienen con el límite exacto (no N+1)
        return [
            'tipo'       => 'paginas',
            'items'      => $rows,
            'hasMore'    => null,
            'nextCursor' => null,
            'pages'      => $legacyMeta['pages'] ?? 1,
            'current'    => $legacyMeta['current'] ?? 1,
            'meta'       => $legacyMeta // Contiene prev, next, total, etc.
        ];
    }

    /* ---------- Legacy tsCore (compatibilidad) ---------- */

    /**
     * Equivalente a tsCore::setPageLimit(). $start=false usa ?page=,
     * $start=true usa ?s= (estilo SMF) con tope $tsMax.
     */
    public static function setPageLimit(int $tsLimit, bool|string $start = false, int $tsMax = 0): string
    {
        if ($start == false) {
            $tsStart = (self::page() - 1) * $tsLimit;
        } else {
            $tsStart = (int) ($_GET['s'] ?? 0);
            if (self::setMaximos($tsLimit, $tsMax)) {
                $tsStart = 0;
            }
        }
        return max(0, $tsStart) . ',' . $tsLimit;
    }

    /** Equivalente a tsCore::setMaximos(): true si ?page= excede el total. */
    public static function setMaximos(int $tsLimit, int $tsMax): bool
    {
        $page = self::page();
        $ban1 = $page * $tsLimit;
        if ($tsMax < $ban1) {
            $ban2 = $ban1 - $tsLimit;
            if ($tsMax < $ban2) {
                return true;
            }
        }
        return false;
    }

    /** Equivalente a tsCore::getPages(). */
    public static function getPages(int $tsTotal, int $tsLimit): array
    {
        return self::fromRequest($tsTotal, $tsLimit);
    }

    /** Equivalente a tsCore::getPagination(). */
    public static function getPagination(int $total, int $per_page = 10): array
    {
        $page = self::page();
        $num_pages = max(1, (int) ceil(max(0, $total) / max(1, $per_page)));
        $prev = $page - 1;
        $next = $page + 1;
        return [
            'current' => min($page, $num_pages),
            'prev'    => $page > 1 ? $prev : 0,
            'next'    => $next <= $num_pages ? $next : 0,
            'limit'   => ((min($page, $num_pages) - 1) * $per_page) . ',' . $per_page,
            'total'   => $total,
            'pages'   => $num_pages,
        ];
    }

    /**
     * Equivalente a tsCore::pageIndex() (lista de enlaces SMF).
     * Corrige $pageindex sin inicializar y claves $_GET ausentes.
     */
    public static function pageIndex(string $base_url, int $start, int $max_value, int $num_per_page, bool $flexible_start = false): string
    {
        $base_url = explode('&s=', $base_url)[0];
        $start_invalid = $start < 0;

        if ($start_invalid) {
            $start = 0;
        } elseif ($start >= $max_value) {
            $start = max(0, (int) $max_value - (((int) $max_value % (int) $num_per_page) == 0 ? $num_per_page : ((int) $max_value % (int) $num_per_page)));
        } else {
            $start = max(0, (int) $start - ((int) $start % (int) $num_per_page));
        }

        $base_link = '<a class="navPages" href="' . ($flexible_start ? $base_url : strtr($base_url, ['%' => '%%']) . '&s=%d') . '">%s</a> ';

        $PageContiguous = (int) (5 - (5 % 2)) / 2;

        if ($start > $num_per_page * $PageContiguous) {
            $pageindex = sprintf($base_link, 0, '1');
        } else {
            $pageindex = '';
        }

        if ($start > $num_per_page * ($PageContiguous + 1)) {
            $pageindex .= '<b> ... </b>';
        }

        for ($nCont = $PageContiguous; $nCont >= 1; $nCont--) {
            if ($start >= $num_per_page * $nCont) {
                $tmpStart = $start - $num_per_page * $nCont;
                $pageindex .= sprintf($base_link, $tmpStart, $tmpStart / $num_per_page + 1);
            }
        }

        if (!$start_invalid) {
            $pageindex .= '[<b>' . ($start / $num_per_page + 1) . '</b>] ';
        } else {
            $pageindex .= sprintf($base_link, $start, $start / $num_per_page + 1);
        }

        $tmpMaxPages = (int) (($max_value - 1) / $num_per_page) * $num_per_page;
        for ($nCont = 1; $nCont <= $PageContiguous; $nCont++) {
            if ($start + $num_per_page * $nCont <= $tmpMaxPages) {
                $tmpStart = $start + $num_per_page * $nCont;
                $pageindex .= sprintf($base_link, $tmpStart, $tmpStart / $num_per_page + 1);
            }
        }

        if ($start + $num_per_page * ($PageContiguous + 1) < $tmpMaxPages) {
            $pageindex .= '<b> ... </b>';
        }

        if ($start + $num_per_page * $PageContiguous < $tmpMaxPages) {
            $pageindex .= sprintf($base_link, $tmpMaxPages, $tmpMaxPages / $num_per_page + 1);
        }

        return $pageindex;
    }
}
