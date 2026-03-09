<?php
/**
 * Proyecto PRESUPUESTO - Repositorio de catalogos operativos.
 */

namespace App\Modules\Catalogos\Repositories;

use App\Core\Logger;
use mysqli;
use mysqli_stmt;

class CatalogoRepository
{
    private $connection;
    private $logger;

    public function __construct(mysqli $connection, Logger $logger)
    {
        $this->connection = $connection;
        $this->logger = $logger;
    }

    public function listClasificaciones($searchTerm)
    {
        $search = trim((string) $searchTerm);

        if ($search === '') {
            $query = 'SELECT id, descripcion FROM clasificaciones ORDER BY descripcion ASC LIMIT 200';
            $result = $this->connection->query($query);
            if (!$result) {
                $this->logger->error('app', 'No fue posible consultar clasificaciones.', array('error' => $this->connection->error));
                return array();
            }

            $rows = array();
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }

            return $rows;
        }

        $query = 'SELECT id, descripcion FROM clasificaciones WHERE descripcion LIKE ? ORDER BY descripcion ASC LIMIT 200';
        $statement = $this->connection->prepare($query);
        if (!$statement instanceof mysqli_stmt) {
            $this->logger->error('app', 'No fue posible preparar filtro de clasificaciones.', array('error' => $this->connection->error));
            return array();
        }

        $likeTerm = $search . '%';
        $statement->bind_param('s', $likeTerm);
        $statement->execute();
        $result = $statement->get_result();
        $rows = array();
        while ($result && ($row = $result->fetch_assoc())) {
            $rows[] = $row;
        }

        $statement->close();
        return $rows;
    }

    public function listMediosPago($searchTerm)
    {
        $search = trim((string) $searchTerm);

        if ($search === '') {
            $query = 'SELECT id, medio FROM medios ORDER BY medio ASC LIMIT 200';
            $result = $this->connection->query($query);
            if (!$result) {
                $this->logger->error('app', 'No fue posible consultar medios de pago.', array('error' => $this->connection->error));
                return array();
            }

            $rows = array();
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }

            return $rows;
        }

        $query = 'SELECT id, medio FROM medios WHERE medio LIKE ? ORDER BY medio ASC LIMIT 200';
        $statement = $this->connection->prepare($query);
        if (!$statement instanceof mysqli_stmt) {
            $this->logger->error('app', 'No fue posible preparar filtro de medios.', array('error' => $this->connection->error));
            return array();
        }

        $likeTerm = $search . '%';
        $statement->bind_param('s', $likeTerm);
        $statement->execute();
        $result = $statement->get_result();
        $rows = array();
        while ($result && ($row = $result->fetch_assoc())) {
            $rows[] = $row;
        }

        $statement->close();
        return $rows;
    }

    public function existsClasificacionByName($description)
    {
        $query = 'SELECT id FROM clasificaciones WHERE UPPER(descripcion) = UPPER(?) LIMIT 1';
        $statement = $this->connection->prepare($query);
        if (!$statement instanceof mysqli_stmt) {
            $this->logger->error('app', 'No fue posible validar clasificacion existente.', array('error' => $this->connection->error));
            return false;
        }

        $statement->bind_param('s', $description);
        $statement->execute();
        $result = $statement->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $statement->close();

        return !empty($row);
    }

    public function existsMedioByName($medio)
    {
        $query = 'SELECT id FROM medios WHERE UPPER(medio) = UPPER(?) LIMIT 1';
        $statement = $this->connection->prepare($query);
        if (!$statement instanceof mysqli_stmt) {
            $this->logger->error('app', 'No fue posible validar medio existente.', array('error' => $this->connection->error));
            return false;
        }

        $statement->bind_param('s', $medio);
        $statement->execute();
        $result = $statement->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $statement->close();

        return !empty($row);
    }

    public function createClasificacion($description)
    {
        $query = 'INSERT INTO clasificaciones (descripcion) VALUES (?)';
        $statement = $this->connection->prepare($query);
        if (!$statement instanceof mysqli_stmt) {
            $this->logger->error('app', 'No fue posible preparar insercion de clasificacion.', array('error' => $this->connection->error));
            return false;
        }

        $statement->bind_param('s', $description);
        $ok = $statement->execute();
        if (!$ok) {
            $this->logger->error('app', 'No fue posible guardar clasificacion.', array('error' => $statement->error));
        }

        $statement->close();
        return $ok;
    }

    public function createMedioPago($medio)
    {
        $query = 'INSERT INTO medios (medio) VALUES (?)';
        $statement = $this->connection->prepare($query);
        if (!$statement instanceof mysqli_stmt) {
            $this->logger->error('app', 'No fue posible preparar insercion de medio de pago.', array('error' => $this->connection->error));
            return false;
        }

        $statement->bind_param('s', $medio);
        $ok = $statement->execute();
        if (!$ok) {
            $this->logger->error('app', 'No fue posible guardar medio de pago.', array('error' => $statement->error));
        }

        $statement->close();
        return $ok;
    }
}
