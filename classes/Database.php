<?php
/**
 * Diyetlenio - Database Sınıfı
 *
 * PDO için wrapper sınıfı. Veritabanı işlemlerini kolaylaştırır.
 * Singleton pattern kullanır.
 */

class Database
{
    /**
     * @var Database|null Singleton instance
     */
    private static ?Database $instance = null;

    /**
     * @var PDO|null PDO bağlantısı
     */
    private ?PDO $connection = null;

    /**
     * @var array Veritabanı yapılandırması
     */
    private array $config;

    /**
     * @var PDOStatement|null Son çalıştırılan statement
     */
    private ?PDOStatement $statement = null;

    /**
     * @var int Etkilenen satır sayısı
     */
    private int $affectedRows = 0;

    /**
     * Constructor - Private (Singleton pattern)
     *
     * @throws Exception
     */
    private function __construct()
    {
        $this->config = require CONFIG_DIR . '/database.php';
        $this->connect();
    }

    /**
     * Singleton instance'ı döndürür
     *
     * @return Database
     * @throws Exception
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Veritabanına bağlanır
     *
     * @return void
     * @throws Exception
     */
    private function connect(): void
    {
        try {
            $dsn = sprintf(
                '%s:host=%s;port=%s;dbname=%s;charset=%s',
                $this->config['driver'],
                $this->config['host'],
                $this->config['port'],
                $this->config['database'],
                $this->config['charset']
            );

            $this->connection = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                $this->config['options']
            );

        } catch (PDOException $e) {
            error_log('Database Connection Error: ' . $e->getMessage());
            throw new Exception(ERROR_DATABASE);
        }
    }

    /**
     * PDO bağlantısını döndürür
     *
     * @return PDO
     */
    public function getConnection(): PDO
    {
        return $this->connection;
    }

    /**
     * SQL sorgusu hazırlar
     *
     * @param string $sql
     * @return Database
     */
    public function query(string $sql): Database
    {
        try {
            $this->statement = $this->connection->prepare($sql);
        } catch (PDOException $e) {
            error_log('Query Preparation Error: ' . $e->getMessage());
            throw new Exception('Sorgu hazırlama hatası: ' . $e->getMessage());
        }

        return $this;
    }

    /**
     * Parametreleri bind eder
     *
     * @param string|int $param
     * @param mixed $value
     * @param int|null $type
     * @return Database
     */
    public function bind($param, $value, ?int $type = null): Database
    {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }

        $this->statement->bindValue($param, $value, $type);

        return $this;
    }

    /**
     * Sorguyu çalıştırır
     *
     * @param array|null $params
     * @return bool
     */
    public function execute(?array $params = null): bool
    {
        try {
            $result = $this->statement->execute($params);
            $this->affectedRows = $this->statement->rowCount();
            return $result;
        } catch (PDOException $e) {
            error_log('Query Execution Error: ' . $e->getMessage());
            error_log('SQL: ' . $this->statement->queryString);
            throw new Exception('Sorgu çalıştırma hatası: ' . $e->getMessage());
        }
    }

    /**
     * Tüm sonuçları döndürür
     *
     * @return array
     */
    public function fetchAll(): array
    {
        $this->execute();
        return $this->statement->fetchAll();
    }

    /**
     * Tek bir sonuç döndürür
     *
     * @return mixed
     */
    public function fetch()
    {
        $this->execute();
        return $this->statement->fetch();
    }

    /**
     * Tek bir değer döndürür (scalar)
     *
     * @return mixed
     */
    public function fetchColumn()
    {
        $this->execute();
        return $this->statement->fetchColumn();
    }

    /**
     * Kayıt sayısını döndürür
     *
     * @return int
     */
    public function rowCount(): int
    {
        return $this->affectedRows;
    }

    /**
     * Son eklenen ID'yi döndürür
     *
     * @return string
     */
    public function lastInsertId(): string
    {
        return $this->connection->lastInsertId();
    }

    /**
     * Transaction başlatır
     *
     * @return bool
     */
    public function beginTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }

    /**
     * Transaction'ı commit eder
     *
     * @return bool
     */
    public function commit(): bool
    {
        return $this->connection->commit();
    }

    /**
     * Transaction'ı geri alır
     *
     * @return bool
     */
    public function rollback(): bool
    {
        return $this->connection->rollBack();
    }

    /**
     * Transaction içinde mi kontrol eder
     *
     * @return bool
     */
    public function inTransaction(): bool
    {
        return $this->connection->inTransaction();
    }

    /**
     * SELECT sorgusu için yardımcı method
     *
     * @param string $table
     * @param array $columns
     * @param array $where
     * @param array $options
     * @return array
     */
    public function select(string $table, array $columns = ['*'], array $where = [], array $options = []): array
    {
        $columnsStr = implode(', ', $columns);
        $sql = "SELECT {$columnsStr} FROM {$table}";

        if (!empty($where)) {
            $conditions = [];
            foreach (array_keys($where) as $key) {
                $conditions[] = "{$key} = :{$key}";
            }
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        if (isset($options['order_by'])) {
            $sql .= " ORDER BY {$options['order_by']}";
        }

        if (isset($options['limit'])) {
            $sql .= " LIMIT {$options['limit']}";
            if (isset($options['offset'])) {
                $sql .= " OFFSET {$options['offset']}";
            }
        }

        $this->query($sql);

        foreach ($where as $key => $value) {
            $this->bind(":{$key}", $value);
        }

        return $this->fetchAll();
    }

    /**
     * INSERT sorgusu için yardımcı method
     *
     * @param string $table
     * @param array $data
     * @return bool
     */
    public function insert(string $table, array $data): bool
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));

        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";

        $this->query($sql);

        foreach ($data as $key => $value) {
            $this->bind(":{$key}", $value);
        }

        return $this->execute();
    }

    /**
     * UPDATE sorgusu için yardımcı method
     *
     * @param string $table
     * @param array $data
     * @param array $where
     * @return bool
     */
    public function update(string $table, array $data, array $where): bool
    {
        $set = [];
        foreach (array_keys($data) as $key) {
            $set[] = "{$key} = :{$key}";
        }
        $setStr = implode(', ', $set);

        $conditions = [];
        foreach (array_keys($where) as $key) {
            $conditions[] = "{$key} = :where_{$key}";
        }
        $whereStr = implode(' AND ', $conditions);

        $sql = "UPDATE {$table} SET {$setStr} WHERE {$whereStr}";

        $this->query($sql);

        foreach ($data as $key => $value) {
            $this->bind(":{$key}", $value);
        }

        foreach ($where as $key => $value) {
            $this->bind(":where_{$key}", $value);
        }

        return $this->execute();
    }

    /**
     * DELETE sorgusu için yardımcı method
     *
     * @param string $table
     * @param array $where
     * @return bool
     */
    public function delete(string $table, array $where): bool
    {
        $conditions = [];
        foreach (array_keys($where) as $key) {
            $conditions[] = "{$key} = :{$key}";
        }
        $whereStr = implode(' AND ', $conditions);

        $sql = "DELETE FROM {$table} WHERE {$whereStr}";

        $this->query($sql);

        foreach ($where as $key => $value) {
            $this->bind(":{$key}", $value);
        }

        return $this->execute();
    }

    /**
     * Tablo var mı kontrol eder
     *
     * @param string $table
     * @return bool
     */
    public function tableExists(string $table): bool
    {
        $sql = "SHOW TABLES LIKE :table";
        $this->query($sql);
        $this->bind(':table', $table);

        return $this->fetch() !== false;
    }

    /**
     * Clone'u engeller (Singleton pattern)
     */
    private function __clone() {}

    /**
     * Unserialize'ı engeller (Singleton pattern)
     */
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }
}
