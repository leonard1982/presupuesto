<?php
/**
 * Proyecto PRESUPUESTO - Repositorio de usuarios para autenticacion.
 */

namespace App\Modules\Auth\Repositories;

use App\Core\Logger;
use mysqli;
use mysqli_stmt;

class UserRepository
{
    private $connection;
    private $logger;
    private $columnsMap;

    public function __construct(mysqli $connection, Logger $logger)
    {
        $this->connection = $connection;
        $this->logger = $logger;
        $this->columnsMap = $this->loadColumnsMap();
    }

    public function findByLogin($login)
    {
        $fields = array('login', 'pswd', 'name', 'email', 'active', 'priv_admin', 'role');

        if ($this->hasColumn('password_hash')) {
            $fields[] = 'password_hash';
        }

        if ($this->hasColumn('password_algorithm')) {
            $fields[] = 'password_algorithm';
        }

        if ($this->hasColumn('password_migrated_at')) {
            $fields[] = 'password_migrated_at';
        }

        $query = 'SELECT ' . implode(', ', $fields) . ' FROM sec_users WHERE login = ? LIMIT 1';
        $statement = $this->connection->prepare($query);

        if (!$statement instanceof mysqli_stmt) {
            $this->logger->error('app', 'No fue posible preparar consulta de usuario.', array('error' => $this->connection->error));
            return null;
        }

        $statement->bind_param('s', $login);
        $statement->execute();
        $result = $statement->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $statement->close();

        return $row ?: null;
    }

    public function supportsModernHash()
    {
        return $this->hasColumn('password_hash');
    }

    public function updateModernHash($login, $hash)
    {
        if (!$this->supportsModernHash()) {
            return false;
        }

        $setSegments = array('password_hash = ?');
        $types = 's';
        $values = array($hash);

        if ($this->hasColumn('password_algorithm')) {
            $setSegments[] = 'password_algorithm = ?';
            $types .= 's';
            $values[] = 'PASSWORD_BCRYPT';
        }

        if ($this->hasColumn('password_migrated_at')) {
            $setSegments[] = 'password_migrated_at = NOW()';
        }

        $query = 'UPDATE sec_users SET ' . implode(', ', $setSegments) . ' WHERE login = ? LIMIT 1';
        $types .= 's';
        $values[] = $login;

        $statement = $this->connection->prepare($query);
        if (!$statement instanceof mysqli_stmt) {
            $this->logger->error('app', 'No fue posible preparar actualizacion de hash.', array('error' => $this->connection->error));
            return false;
        }

        $this->bindParameters($statement, $types, $values);
        $ok = $statement->execute();
        $statement->close();

        return $ok;
    }

    private function loadColumnsMap()
    {
        $columnsMap = array();
        $result = $this->connection->query('SHOW COLUMNS FROM sec_users');

        if (!$result) {
            $this->logger->warning('app', 'No fue posible leer columnas de sec_users.', array('error' => $this->connection->error));
            return $columnsMap;
        }

        while ($column = $result->fetch_assoc()) {
            if (isset($column['Field'])) {
                $columnsMap[$column['Field']] = true;
            }
        }

        return $columnsMap;
    }

    private function hasColumn($columnName)
    {
        return isset($this->columnsMap[$columnName]);
    }

    private function bindParameters(mysqli_stmt $statement, $types, array $values)
    {
        $parameters = array($types);
        foreach ($values as $index => $value) {
            $parameters[] = &$values[$index];
        }

        call_user_func_array(array($statement, 'bind_param'), $parameters);
    }
}
