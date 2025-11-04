<?php
// Database configuration using PDO for PostgreSQL
function getDatabaseConnection() {
    static $conn = null;
    
    if ($conn === null) {
        $host = getenv('PGHOST') ?: 'helium';
        $port = getenv('PGPORT') ?: '5432';
        $dbname = getenv('PGDATABASE') ?: 'heliumdb';
        $user = getenv('PGUSER') ?: 'postgres';
        $password = getenv('PGPASSWORD') ?: 'password';
        
        try {
            $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
            $pdo = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
            
            // Create a mysqli-compatible wrapper
            $conn = new class($pdo) {
                private $pdo;
                public $connect_error = null;
                
                public function __construct($pdo) {
                    $this->pdo = $pdo;
                }
                
                public function query($sql) {
                    try {
                        $stmt = $this->pdo->query($sql);
                        return new class($stmt) {
                            private $stmt;
                            public $num_rows = 0;
                            
                            public function __construct($stmt) {
                                $this->stmt = $stmt;
                                if ($stmt) {
                                    $this->num_rows = $stmt->rowCount();
                                }
                            }
                            
                            public function fetch_assoc() {
                                if ($this->stmt) {
                                    return $this->stmt->fetch(PDO::FETCH_ASSOC);
                                }
                                return null;
                            }
                        };
                    } catch (PDOException $e) {
                        error_log("Query error: " . $e->getMessage());
                        return false;
                    }
                }
                
                public function prepare($sql) {
                    try {
                        $stmt = $this->pdo->prepare($sql);
                        return new class($stmt) {
                            private $stmt;
                            private $params = [];
                            
                            public function __construct($stmt) {
                                $this->stmt = $stmt;
                            }
                            
                            public function bind_param($types, ...$params) {
                                $this->params = $params;
                                return true;
                            }
                            
                            public function execute() {
                                try {
                                    return $this->stmt->execute($this->params);
                                } catch (PDOException $e) {
                                    error_log("Execute error: " . $e->getMessage());
                                    return false;
                                }
                            }
                            
                            public function get_result() {
                                return new class($this->stmt) {
                                    private $stmt;
                                    public $num_rows = 0;
                                    
                                    public function __construct($stmt) {
                                        $this->stmt = $stmt;
                                        $this->num_rows = $stmt->rowCount();
                                    }
                                    
                                    public function fetch_assoc() {
                                        return $this->stmt->fetch(PDO::FETCH_ASSOC);
                                    }
                                };
                            }
                            
                            public function close() {
                                return true;
                            }
                        };
                    } catch (PDOException $e) {
                        error_log("Prepare error: " . $e->getMessage());
                        return false;
                    }
                }
                
                public function real_escape_string($string) {
                    return str_replace("'", "''", $string);
                }
                
                public function set_charset($charset) {
                    return true;
                }
                
                public function close() {
                    $this->pdo = null;
                    return true;
                }
                
                public function __get($name) {
                    if ($name === 'insert_id') {
                        return $this->pdo->lastInsertId();
                    }
                    return null;
                }
            };
            
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            return null;
        }
    }
    
    return $conn;
}

// Initialize connection
$conn = getDatabaseConnection();
?>
