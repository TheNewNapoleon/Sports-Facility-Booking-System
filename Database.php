<?php
/**
 * Database.php
 * Combined MySQLi and PDO database connection handler
 * Supports both legacy MySQLi code and modern PDO features
 */

// ============================================
// DATABASE CONFIGURATION
// ============================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'campus_facility_booking');
define('DB_USER', 'root');
define('DB_PASS', ''); // Set your MySQL password if needed

// ============================================
// GLOBAL MYSQLI CONNECTION (for legacy code)
// ============================================
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check global connection
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// Set charset for global connection
$conn->set_charset("utf8mb4");

// ============================================
// DATABASE CLASS (Supports both MySQLi & PDO)
// ============================================
class Database {
    private $host = DB_HOST;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $dbname = DB_NAME;
    
    private $mysqli_conn = null;  // MySQLi connection instance
    private $pdo_conn = null;     // PDO connection instance

    /**
     * Constructor
     */
    public function __construct() {
        // Empty constructor - connections are lazy-loaded
    }

    /**
     * Get MySQLi connection (for existing/legacy code)
     * 
     * @return mysqli MySQLi database connection
     * @throws Exception if connection fails
     */
    public function getConnection() {
        if ($this->mysqli_conn === null) {
            $this->mysqli_conn = new mysqli(
                $this->host,
                $this->username,
                $this->password,
                $this->dbname
            );

            // Check for connection errors
            if ($this->mysqli_conn->connect_error) {
                throw new Exception("MySQLi Connection failed: " . $this->mysqli_conn->connect_error);
            }

            // Set charset
            $this->mysqli_conn->set_charset("utf8mb4");
        }

        return $this->mysqli_conn;
    }

    /**
     * Get PDO connection (for modern code like AI chatbot)
     * 
     * @return PDO PDO database connection
     * @throws Exception if connection fails
     */
    public function getPDOConnection() {
        if ($this->pdo_conn === null) {
            try {
                $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4";
                
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // Throw exceptions on errors
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // Fetch as associative array
                    PDO::ATTR_EMULATE_PREPARES   => false,                   // Use real prepared statements
                    PDO::ATTR_PERSISTENT         => false,                   // Don't use persistent connections
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"     // Set charset
                ];

                $this->pdo_conn = new PDO($dsn, $this->username, $this->password, $options);
                
            } catch (PDOException $e) {
                throw new Exception("PDO Connection failed: " . $e->getMessage());
            }
        }

        return $this->pdo_conn;
    }

    /**
     * Close MySQLi connection
     */
    public function closeMySQLi() {
        if ($this->mysqli_conn !== null) {
            $this->mysqli_conn->close();
            $this->mysqli_conn = null;
        }
    }

    /**
     * Close PDO connection
     */
    public function closePDO() {
        $this->pdo_conn = null;
    }

    /**
     * Close all connections
     */
    public function closeAll() {
        $this->closeMySQLi();
        $this->closePDO();
    }

    /**
     * Destructor - clean up connections
     */
    public function __destruct() {
        $this->closeAll();
    }
}

// ============================================
// HELPER FUNCTIONS (Optional)
// ============================================

/**
 * Get a global Database instance
 * 
 * @return Database
 */
function getDatabase() {
    static $db = null;
    if ($db === null) {
        $db = new Database();
    }
    return $db;
}

/**
 * Quick access to PDO connection
 * 
 * @return PDO
 */
function getPDO() {
    return getDatabase()->getPDOConnection();
}

/**
 * Quick access to MySQLi connection
 * 
 * @return mysqli
 */
function getMySQLi() {
    return getDatabase()->getConnection();
}
