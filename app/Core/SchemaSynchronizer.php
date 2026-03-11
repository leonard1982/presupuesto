<?php
/**
 * Proyecto PRESUPUESTO - Sincronizador automatico de esquema minimo requerido.
 */

namespace App\Core;

use mysqli;

class SchemaSynchronizer
{
    private $connection;
    private $logger;
    private $databaseName;
    private $cacheFilePath;
    private $syncCheckSeconds;

    public function __construct(mysqli $connection, Logger $logger, array $pathsConfig, $syncCheckSeconds)
    {
        $this->connection = $connection;
        $this->logger = $logger;
        $this->databaseName = $this->resolveDatabaseName();
        $this->syncCheckSeconds = (int) $syncCheckSeconds;
        if ($this->syncCheckSeconds < 0) {
            $this->syncCheckSeconds = 0;
        }

        $cacheRoot = isset($pathsConfig['cache_root']) ? (string) $pathsConfig['cache_root'] : '';
        $databaseSafe = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->databaseName);
        if (!is_string($databaseSafe) || trim($databaseSafe) === '') {
            $databaseSafe = 'default';
        }
        $this->cacheFilePath = $cacheRoot !== ''
            ? $cacheRoot . DIRECTORY_SEPARATOR . 'schema_sync_' . $databaseSafe . '.json'
            : '';
    }

    public function synchronize()
    {
        if ($this->databaseName === '') {
            $this->logger->warning('app', 'No se pudo resolver base de datos activa para sincronizacion de esquema.');
            return;
        }

        if ($this->isRecentSync()) {
            return;
        }

        $summary = array(
            'tables_created' => array(),
            'columns_added' => array(),
            'indexes_added' => array(),
            'updates_applied' => array(),
        );

        $this->ensureCoreTables($summary);
        $this->ensureCoreColumns($summary);
        $this->ensureCoreIndexes($summary);
        $this->ensureCompatibilityAdjustments($summary);
        $this->writeSyncCache($summary);
    }

    private function ensureCoreTables(array &$summary)
    {
        $this->ensureTable(
            'sec_users',
            "CREATE TABLE `sec_users` (
                `login` varchar(190) NOT NULL,
                `pswd` varchar(255) NOT NULL COMMENT 'contrasena legacy',
                `name` varchar(255) DEFAULT NULL,
                `email` varchar(255) DEFAULT NULL,
                `active` varchar(1) DEFAULT 'Y',
                `activation_code` varchar(32) DEFAULT NULL,
                `priv_admin` varchar(1) DEFAULT NULL,
                `mfa` varchar(255) DEFAULT NULL,
                `picture` longblob,
                `role` varchar(128) DEFAULT NULL,
                `phone` varchar(64) DEFAULT NULL,
                `pswd_last_updated` timestamp NULL DEFAULT NULL,
                `mfa_last_updated` timestamp NULL DEFAULT NULL,
                `password_hash` varchar(255) DEFAULT NULL,
                `password_algorithm` varchar(40) DEFAULT NULL,
                `password_migrated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`login`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            $summary
        );

        $this->ensureTable(
            'clasificaciones',
            "CREATE TABLE `clasificaciones` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `descripcion` varchar(80) NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            $summary
        );

        $this->ensureTable(
            'medios',
            "CREATE TABLE `medios` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `medio` varchar(80) DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            $summary
        );

        $this->ensureTable(
            'presupuesto',
            "CREATE TABLE `presupuesto` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `fecha_creacion` datetime NOT NULL,
                `descripcion` mediumtext NOT NULL,
                `estado` set('ACTIVO','INACTIVO') NOT NULL,
                `tipo` set('ANUAL','MENSUAL') NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            $summary
        );

        $this->ensureTable(
            'detalle_presupuesto',
            "CREATE TABLE `detalle_presupuesto` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `id_presupuesto` int(11) NOT NULL,
                `detalle` mediumtext NOT NULL,
                `valor` decimal(15,2) NOT NULL,
                `id_clasificacion` int(11) NOT NULL,
                `costo_gasto` set('Costo','Gasto') NOT NULL,
                `creado` datetime NOT NULL,
                `editado` datetime NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            $summary
        );

        $this->ensureTable(
            'gastos_costos',
            "CREATE TABLE `gastos_costos` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `fecha` datetime NOT NULL,
                `id_clasificacion` int(11) NOT NULL,
                `detalle` mediumtext NOT NULL,
                `valor` decimal(15,2) NOT NULL DEFAULT '0.00',
                `fecha_periodo` date NOT NULL,
                `id_presupuesto` int(11) NOT NULL DEFAULT '0',
                `soporte` mediumtext NOT NULL,
                `gasto_costo` set('Gasto','Costo','Ingreso') NOT NULL DEFAULT 'Gasto',
                `tipo` varchar(60) NOT NULL DEFAULT 'EFECTIVO',
                `por_pagar_cobrar` set('COBRAR','PAGAR','NINGUNO') NOT NULL DEFAULT 'NINGUNO',
                `valor_neto` decimal(15,2) NOT NULL DEFAULT '0.00',
                `saldo` decimal(15,2) NOT NULL DEFAULT '0.00',
                `id_costo` int(11) NOT NULL DEFAULT '0',
                `usuario` varchar(255) NOT NULL,
                `estado_operativo` set('ABIERTO','CERRADO','ASENTADO') NOT NULL DEFAULT 'ABIERTO',
                `justificacion_reversa` text DEFAULT NULL,
                `fecha_estado` datetime DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            $summary
        );

        $this->ensureTable(
            'ingresos',
            "CREATE TABLE `ingresos` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `fecha` datetime NOT NULL,
                `id_clasificacion` int(11) NOT NULL,
                `detalle` mediumtext NOT NULL,
                `valor` decimal(15,2) NOT NULL DEFAULT '0.00',
                `fecha_periodo` date NOT NULL,
                `id_presupuesto` int(11) NOT NULL DEFAULT '0',
                `soporte` mediumtext NOT NULL,
                `tipo` varchar(60) NOT NULL DEFAULT 'EFECTIVO',
                `por_cobrar_pagar` set('COBRAR','PAGAR','NINGUNO') NOT NULL DEFAULT 'NINGUNO',
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            $summary
        );

        $this->ensureTable(
            'ingresos_detalle',
            "CREATE TABLE `ingresos_detalle` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `id_ingreso` int(11) NOT NULL DEFAULT '0',
                `fechayhora` datetime DEFAULT NULL,
                `imagen` mediumtext,
                `usuario` varchar(255) DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            $summary
        );

        $this->ensureTable(
            'correo_importaciones_log',
            "CREATE TABLE `correo_importaciones_log` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `correo_uid` varchar(120) NOT NULL,
                `remitente` varchar(255) NOT NULL,
                `asunto` varchar(255) NOT NULL,
                `fecha_correo` datetime DEFAULT NULL,
                `contenido_hash` char(40) NOT NULL,
                `sugerencia_json` longtext,
                `movimiento_id` int(11) DEFAULT NULL,
                `estado` varchar(30) NOT NULL DEFAULT 'PENDIENTE',
                `confianza` decimal(5,4) NOT NULL DEFAULT '0.0000',
                `usuario` varchar(120) NOT NULL,
                `observaciones` text,
                `fecha_registro` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            $summary
        );

        $this->ensureTable(
            'correo_inbox_hidden',
            "CREATE TABLE `correo_inbox_hidden` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `usuario` varchar(120) NOT NULL,
                `correo_uid` varchar(120) NOT NULL DEFAULT '',
                `correo_hash` char(40) NOT NULL DEFAULT '',
                `remitente` varchar(255) NOT NULL DEFAULT '',
                `asunto` varchar(255) NOT NULL DEFAULT '',
                `fecha_correo` datetime DEFAULT NULL,
                `ocultado_en` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_correo_inbox_hidden_usuario_uid_hash` (`usuario`,`correo_uid`,`correo_hash`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            $summary
        );

        $this->ensureTable(
            'sc_log',
            "CREATE TABLE `sc_log` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `inserted_date` datetime DEFAULT NULL,
                `username` varchar(90) NOT NULL,
                `application` varchar(255) NOT NULL,
                `creator` varchar(30) NOT NULL,
                `ip_user` varchar(255) NOT NULL,
                `action` varchar(30) NOT NULL,
                `description` text,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            $summary
        );
    }

    private function ensureCoreColumns(array &$summary)
    {
        $this->ensureColumn(
            'gastos_costos',
            'estado_operativo',
            "ALTER TABLE `gastos_costos` ADD COLUMN `estado_operativo` set('ABIERTO','CERRADO','ASENTADO') NOT NULL DEFAULT 'ABIERTO' AFTER `usuario`",
            $summary
        );
        $this->ensureColumn(
            'gastos_costos',
            'justificacion_reversa',
            "ALTER TABLE `gastos_costos` ADD COLUMN `justificacion_reversa` text DEFAULT NULL AFTER `estado_operativo`",
            $summary
        );
        $this->ensureColumn(
            'gastos_costos',
            'fecha_estado',
            "ALTER TABLE `gastos_costos` ADD COLUMN `fecha_estado` datetime DEFAULT NULL AFTER `justificacion_reversa`",
            $summary
        );
        $this->ensureColumn(
            'sec_users',
            'password_hash',
            "ALTER TABLE `sec_users` ADD COLUMN `password_hash` varchar(255) DEFAULT NULL AFTER `pswd`",
            $summary
        );
        $this->ensureColumn(
            'sec_users',
            'password_algorithm',
            "ALTER TABLE `sec_users` ADD COLUMN `password_algorithm` varchar(40) DEFAULT NULL AFTER `password_hash`",
            $summary
        );
        $this->ensureColumn(
            'sec_users',
            'password_migrated_at',
            "ALTER TABLE `sec_users` ADD COLUMN `password_migrated_at` datetime DEFAULT NULL AFTER `password_algorithm`",
            $summary
        );
    }

    private function ensureCoreIndexes(array &$summary)
    {
        $this->ensureIndex(
            'clasificaciones',
            'idx_clasificaciones_descripcion',
            "ALTER TABLE `clasificaciones` ADD INDEX `idx_clasificaciones_descripcion` (`descripcion`)",
            $summary
        );
        $this->ensureIndex(
            'medios',
            'idx_medios_medio',
            "ALTER TABLE `medios` ADD INDEX `idx_medios_medio` (`medio`)",
            $summary
        );
        $this->ensureIndex(
            'presupuesto',
            'idx_presupuesto_estado_fecha',
            "ALTER TABLE `presupuesto` ADD INDEX `idx_presupuesto_estado_fecha` (`estado`, `fecha_creacion`)",
            $summary
        );
        $this->ensureIndex(
            'detalle_presupuesto',
            'idx_detalle_presupuesto_relaciones',
            "ALTER TABLE `detalle_presupuesto` ADD INDEX `idx_detalle_presupuesto_relaciones` (`id_presupuesto`, `id_clasificacion`)",
            $summary
        );
        $this->ensureIndex(
            'ingresos_detalle',
            'idx_ingresos_detalle_id_ingreso',
            "ALTER TABLE `ingresos_detalle` ADD INDEX `idx_ingresos_detalle_id_ingreso` (`id_ingreso`)",
            $summary
        );
        $this->ensureIndex(
            'ingresos_detalle',
            'idx_ingresos_detalle_id_ingreso_id',
            "ALTER TABLE `ingresos_detalle` ADD INDEX `idx_ingresos_detalle_id_ingreso_id` (`id_ingreso`, `id`)",
            $summary
        );
        $this->ensureIndex(
            'gastos_costos',
            'idx_gastos_periodo_clasificacion_presupuesto',
            "ALTER TABLE `gastos_costos` ADD INDEX `idx_gastos_periodo_clasificacion_presupuesto` (`fecha_periodo`, `id_clasificacion`, `id_presupuesto`)",
            $summary
        );
        $this->ensureIndex(
            'gastos_costos',
            'idx_gastos_usuario_periodo',
            "ALTER TABLE `gastos_costos` ADD INDEX `idx_gastos_usuario_periodo` (`usuario`, `fecha_periodo`)",
            $summary
        );
        $this->ensureIndex(
            'gastos_costos',
            'idx_gastos_estado_cobro',
            "ALTER TABLE `gastos_costos` ADD INDEX `idx_gastos_estado_cobro` (`por_pagar_cobrar`, `saldo`)",
            $summary
        );
        $this->ensureIndex(
            'gastos_costos',
            'idx_gc_periodo_categoria',
            "ALTER TABLE `gastos_costos` ADD INDEX `idx_gc_periodo_categoria` (`fecha_periodo`, `gasto_costo`)",
            $summary
        );
        $this->ensureIndex(
            'gastos_costos',
            'idx_gc_clasificacion_periodo',
            "ALTER TABLE `gastos_costos` ADD INDEX `idx_gc_clasificacion_periodo` (`id_clasificacion`, `fecha_periodo`)",
            $summary
        );
        $this->ensureIndex(
            'gastos_costos',
            'idx_gc_tipo_periodo',
            "ALTER TABLE `gastos_costos` ADD INDEX `idx_gc_tipo_periodo` (`tipo`, `fecha_periodo`)",
            $summary
        );
        $this->ensureIndex(
            'gastos_costos',
            'idx_gc_fecha',
            "ALTER TABLE `gastos_costos` ADD INDEX `idx_gc_fecha` (`fecha`)",
            $summary
        );
        $this->ensureIndex(
            'gastos_costos',
            'idx_gc_estado_operativo',
            "ALTER TABLE `gastos_costos` ADD INDEX `idx_gc_estado_operativo` (`estado_operativo`, `fecha_estado`)",
            $summary
        );
        $this->ensureIndex(
            'ingresos',
            'idx_ingresos_periodo_clasificacion_presupuesto',
            "ALTER TABLE `ingresos` ADD INDEX `idx_ingresos_periodo_clasificacion_presupuesto` (`fecha_periodo`, `id_clasificacion`, `id_presupuesto`)",
            $summary
        );
        $this->ensureIndex(
            'ingresos',
            'idx_ingresos_tipo_estado',
            "ALTER TABLE `ingresos` ADD INDEX `idx_ingresos_tipo_estado` (`tipo`, `por_cobrar_pagar`)",
            $summary
        );
        $this->ensureIndex(
            'ingresos',
            'idx_ingresos_periodo',
            "ALTER TABLE `ingresos` ADD INDEX `idx_ingresos_periodo` (`fecha_periodo`)",
            $summary
        );
        $this->ensureIndex(
            'ingresos',
            'idx_ingresos_clasificacion_periodo',
            "ALTER TABLE `ingresos` ADD INDEX `idx_ingresos_clasificacion_periodo` (`id_clasificacion`, `fecha_periodo`)",
            $summary
        );
        $this->ensureIndex(
            'ingresos',
            'idx_ingresos_tipo_periodo',
            "ALTER TABLE `ingresos` ADD INDEX `idx_ingresos_tipo_periodo` (`tipo`, `fecha_periodo`)",
            $summary
        );
        $this->ensureIndex(
            'ingresos',
            'idx_ingresos_fecha',
            "ALTER TABLE `ingresos` ADD INDEX `idx_ingresos_fecha` (`fecha`)",
            $summary
        );
        $this->ensureIndex(
            'correo_importaciones_log',
            'idx_correo_importaciones_uid',
            "ALTER TABLE `correo_importaciones_log` ADD INDEX `idx_correo_importaciones_uid` (`correo_uid`)",
            $summary
        );
        $this->ensureIndex(
            'correo_importaciones_log',
            'idx_correo_importaciones_movimiento',
            "ALTER TABLE `correo_importaciones_log` ADD INDEX `idx_correo_importaciones_movimiento` (`movimiento_id`)",
            $summary
        );
        $this->ensureIndex(
            'correo_importaciones_log',
            'idx_correo_importaciones_estado_fecha',
            "ALTER TABLE `correo_importaciones_log` ADD INDEX `idx_correo_importaciones_estado_fecha` (`estado`, `fecha_registro`)",
            $summary
        );
        $this->ensureIndex(
            'correo_inbox_hidden',
            'idx_correo_inbox_hidden_usuario_ocultado',
            "ALTER TABLE `correo_inbox_hidden` ADD INDEX `idx_correo_inbox_hidden_usuario_ocultado` (`usuario`, `ocultado_en`)",
            $summary
        );
        $this->ensureIndex(
            'correo_inbox_hidden',
            'idx_correo_inbox_hidden_usuario_hash',
            "ALTER TABLE `correo_inbox_hidden` ADD INDEX `idx_correo_inbox_hidden_usuario_hash` (`usuario`, `correo_hash`)",
            $summary
        );
        $this->ensureIndex(
            'sc_log',
            'idx_sc_log_inserted_date_application',
            "ALTER TABLE `sc_log` ADD INDEX `idx_sc_log_inserted_date_application` (`inserted_date`, `application`)",
            $summary
        );
    }

    private function ensureCompatibilityAdjustments(array &$summary)
    {
        if (!$this->tableExists('gastos_costos') || !$this->columnExists('gastos_costos', 'gasto_costo')) {
            return;
        }

        $columnType = $this->getColumnType('gastos_costos', 'gasto_costo');
        if ($columnType !== '' && stripos($columnType, "'Ingreso'") !== false) {
            return;
        }

        $sql = "ALTER TABLE `gastos_costos`
                MODIFY `gasto_costo` SET('Gasto','Costo','Ingreso') NOT NULL COMMENT 'Si fue un Gasto, Costo o Ingreso de la operacion'";
        if ($this->execute($sql, 'habilitar categoria Ingreso en gastos_costos.gasto_costo')) {
            $summary['updates_applied'][] = 'gastos_costos.gasto_costo';
        }
    }

    private function ensureTable($tableName, $createSql, array &$summary)
    {
        if ($this->tableExists($tableName)) {
            return;
        }

        if ($this->execute($createSql, 'crear tabla ' . $tableName) && $this->tableExists($tableName)) {
            $summary['tables_created'][] = $tableName;
        }
    }

    private function ensureColumn($tableName, $columnName, $alterSql, array &$summary)
    {
        if (!$this->tableExists($tableName)) {
            return;
        }

        if ($this->columnExists($tableName, $columnName)) {
            return;
        }

        if ($this->execute($alterSql, 'crear columna ' . $tableName . '.' . $columnName) && $this->columnExists($tableName, $columnName)) {
            $summary['columns_added'][] = $tableName . '.' . $columnName;
        }
    }

    private function ensureIndex($tableName, $indexName, $alterSql, array &$summary)
    {
        if (!$this->tableExists($tableName)) {
            return;
        }

        if ($this->indexExists($tableName, $indexName)) {
            return;
        }

        if ($this->execute($alterSql, 'crear indice ' . $tableName . '.' . $indexName) && $this->indexExists($tableName, $indexName)) {
            $summary['indexes_added'][] = $tableName . '.' . $indexName;
        }
    }

    private function resolveDatabaseName()
    {
        $result = $this->connection->query('SELECT DATABASE() AS db_name');
        if ($result === false) {
            return '';
        }

        $row = $result->fetch_assoc();
        return isset($row['db_name']) ? (string) $row['db_name'] : '';
    }

    private function tableExists($tableName)
    {
        $sql = "SELECT COUNT(*) AS total
                FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?";
        $statement = $this->connection->prepare($sql);
        if ($statement === false) {
            return false;
        }

        $database = $this->databaseName;
        $table = (string) $tableName;
        $statement->bind_param('ss', $database, $table);
        $statement->execute();
        $result = $statement->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $statement->close();

        return $row && isset($row['total']) && (int) $row['total'] > 0;
    }

    private function columnExists($tableName, $columnName)
    {
        $sql = "SELECT COUNT(*) AS total
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?";
        $statement = $this->connection->prepare($sql);
        if ($statement === false) {
            return false;
        }

        $database = $this->databaseName;
        $table = (string) $tableName;
        $column = (string) $columnName;
        $statement->bind_param('sss', $database, $table, $column);
        $statement->execute();
        $result = $statement->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $statement->close();

        return $row && isset($row['total']) && (int) $row['total'] > 0;
    }

    private function indexExists($tableName, $indexName)
    {
        $sql = "SELECT COUNT(*) AS total
                FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?";
        $statement = $this->connection->prepare($sql);
        if ($statement === false) {
            return false;
        }

        $database = $this->databaseName;
        $table = (string) $tableName;
        $index = (string) $indexName;
        $statement->bind_param('sss', $database, $table, $index);
        $statement->execute();
        $result = $statement->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $statement->close();

        return $row && isset($row['total']) && (int) $row['total'] > 0;
    }

    private function getColumnType($tableName, $columnName)
    {
        $sql = "SELECT COLUMN_TYPE
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?
                LIMIT 1";
        $statement = $this->connection->prepare($sql);
        if ($statement === false) {
            return '';
        }

        $database = $this->databaseName;
        $table = (string) $tableName;
        $column = (string) $columnName;
        $statement->bind_param('sss', $database, $table, $column);
        $statement->execute();
        $result = $statement->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $statement->close();

        return $row && isset($row['COLUMN_TYPE']) ? (string) $row['COLUMN_TYPE'] : '';
    }

    private function execute($sql, $description)
    {
        $ok = $this->connection->query($sql);
        if ($ok === false) {
            $this->logger->warning('app', 'No fue posible sincronizar esquema.', array(
                'database' => $this->databaseName,
                'description' => (string) $description,
                'error' => $this->connection->error,
            ));
            return false;
        }

        return true;
    }

    private function isRecentSync()
    {
        if ($this->syncCheckSeconds < 1 || $this->cacheFilePath === '' || !is_file($this->cacheFilePath)) {
            return false;
        }

        $raw = @file_get_contents($this->cacheFilePath);
        if (!is_string($raw) || trim($raw) === '') {
            return false;
        }

        $payload = json_decode($raw, true);
        if (!is_array($payload) || !isset($payload['timestamp'])) {
            return false;
        }

        $timestamp = (int) $payload['timestamp'];
        if ($timestamp <= 0) {
            return false;
        }

        return (time() - $timestamp) < $this->syncCheckSeconds;
    }

    private function writeSyncCache(array $summary)
    {
        if ($this->cacheFilePath === '') {
            return;
        }

        $directory = dirname($this->cacheFilePath);
        if (!is_dir($directory)) {
            @mkdir($directory, 0775, true);
        }

        $payload = array(
            'database' => $this->databaseName,
            'timestamp' => time(),
            'datetime' => date('Y-m-d H:i:s'),
            'summary' => $summary,
        );

        @file_put_contents($this->cacheFilePath, json_encode($payload, JSON_UNESCAPED_SLASHES));

        $changesCount = count($summary['tables_created']) + count($summary['columns_added']) + count($summary['indexes_added']) + count($summary['updates_applied']);
        if ($changesCount > 0) {
            $this->logger->info('app', 'Sincronizacion de esquema aplicada.', array(
                'database' => $this->databaseName,
                'summary' => $summary,
            ));
        }
    }
}
