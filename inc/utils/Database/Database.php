<?php

declare(strict_types=1);

/**
 * @author PHPost Risus LTS
 * @copyright 2027
 */

class Database {

    private ?mysqli $connection = null;

    /**
     * El constructor recibe un arreglo con las credenciales obligatorias de PHPost
     * @param array{hostname: string, username: string, password: string, database: string, port?: int} $config
     */
    public function __construct(array $config) {
        if (!isset($config['hostname'], $config['username'], $config['password'], $config['database'])) {
            throw new InvalidArgumentException("Faltan parámetros de configuración (hostname, username, password, database).");
        }

        // Desactivar el reporte de errores por defecto de mysqli para manejar excepciones limpias
        mysqli_report(MYSQLI_REPORT_OFF);

        $this->connection = new mysqli(
            $config['hostname'],
            $config['username'],
            $config['password'],
            $config['database'],
            $config['port'] ?? 3306
        );

        if ($this->connection->connect_error) {
            throw new RuntimeException("Error de conexión a la base de datos: " . $this->connection->connect_error);
        }

        $this->connection->set_charset("utf8mb4");
    }

    public function getConnection(): mysqli {
        return $this->connection;
    }

    /**
     * Helper privado optimizado para preparar y ejecutar consultas estructuradas
     */
    private function prepareAndExecute(string $sql, array $params = []): mysqli_stmt {
        // Si tienes PHP 8.2+, puedes reemplazar todo este método usando directamente $this->connection->execute_query()
        $stmt = $this->connection->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException("Error al preparar la consulta: " . $this->connection->error);
        }

        if (!empty($params)) {
            $types = '';
            foreach ($params as $param) {
                $types .= match (true) {
                    is_int($param)    => 'i',
                    is_double($param) => 'd',
                    default           => 's',
                };
            }
            $stmt->bind_param($types, ...$params);
        }

        if (!$stmt->execute()) {
            throw new RuntimeException("Error al ejecutar la consulta: " . $stmt->error);
        }

        return $stmt;
    }

    /**
     * Helper ejecutor base (Reemplaza a mysqli_query de forma segura)
     * Retorna mysqli_result para SELECT, bool para otras operaciones, o null si falla
     */
    public function query(string $sql): mysqli_result|bool {
        return $this->connection->query($sql);
    }

    /**
     * Helper: mysqli_num_rows
     */
    public function numRows(mysqli_result|bool|null $result): int {
        return ($result instanceof mysqli_result) ? $result->num_rows : 0;
    }

    /**
     * Helper: mysqli_fetch_assoc
     */
    public function fetchAssoc(mysqli_result|bool|null $result): ?array {
        return ($result instanceof mysqli_result) ? $result->fetch_assoc() : null;
    }

    /**
     * Helper: mysqli_fetch_array
     */
    public function fetchArray(mysqli_result|bool|null $result): ?array {
        return ($result instanceof mysqli_result) ? $result->fetch_array() : null;
    }

    /**
     * Helper: mysqli_fetch_row
     */
    public function fetchRow(mysqli_result|bool|null $result): ?array {
        return ($result instanceof mysqli_result) ? $result->fetch_row() : null;
    }

    /**
     * Helper: mysqli_insert_id
     */
    public function insertId(): int {
        return (int)$this->connection->insert_id;
    }

    /**
     * Helper: mysqli_error
     */
    public function error(): string {
        return $this->connection->error;
    }

    /**
     * Helper: mysqli_free_result
     */
    public function freeResult(mysqli_result|bool|null $result): void {
        if ($result instanceof mysqli_result) {
            $result->free();
        }
    }

    /**
     * Helper: mysqli_real_escape_string
     */
    public function escape(string $data): string {
        return $this->connection->real_escape_string($data);
    }

    /**
     * Helper: mysqli_multi_query
     */
    public function multiQuery(string $sql): bool {
        return $this->connection->multi_query($sql);
    }

    /**
     * Helper moderno: Obtener múltiples filas mediante Prepared Statements
     */
    public function getAll(string $sql, array $params = []): array {
        $stmt = $this->prepareAndExecute($sql, $params);
        $result = $stmt->get_result();

        if (!$result) {
            $stmt->close();
            return [];
        }

        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $data;
    }

    /**
     * Helper moderno: Obtener una sola fila mediante Prepared Statements
     */
    public function getRow(string $sql, array $params = []): ?array {
        $stmt = $this->prepareAndExecute($sql, $params);
        $result = $stmt->get_result();

        if (!$result) {
            $stmt->close();
            return null;
        }

        $data = $result->fetch_assoc();
        $stmt->close();
        return $data ?: null;
    }

    /**
     * Helper moderno: Ejecutar comandos de escritura seguros (INSERT, UPDATE, DELETE)
     */
    public function execute(string $sql, array $params = [], bool $returnInsertId = false): int {
        $stmt = $this->prepareAndExecute($sql, $params);
        $result = $returnInsertId ? (int)$this->connection->insert_id : $stmt->affected_rows;
        $stmt->close();
        return $result;
    }

    /**
     * Iniciar una transacción estructurada (Desactiva el autocommit temporalmente)
     */
    public function beginTransaction(): bool {
        return $this->connection->begin_transaction();
    }

    /**
     * Confirmar los cambios de la transacción actual
     */
    public function commit(): bool {
        return $this->connection->commit();
    }

    /**
     * Revertir todos los cambios realizados en la transacción actual si ocurre un error
     */
    public function rollback(): bool {
        return $this->connection->rollback();
    }

}
