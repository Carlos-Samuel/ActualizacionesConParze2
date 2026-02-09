<?php

    require_once 'bootstrap.php';

    class Connection {
        private static ?self $instance = null;
        private \mysqli $connection;

        private function __construct() {
            $host    = env_required('DB1_HOST');
            $port    = (int)env('DB1_PORT', '3306');
            $dbname  = env_required('DB1_NAME');
            $user    = env_required('DB1_USER');
            $pass    = env('DB1_PASS', '');
            $charset = env('DB1_CHARSET', 'utf8mb4');

            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            $this->connection = new mysqli($host, $user, $pass, $dbname, $port);
            $this->connection->set_charset($charset);
        }

        public static function getInstance(): self {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        public function getConnection(): \mysqli {
            return $this->connection;
        }
    }