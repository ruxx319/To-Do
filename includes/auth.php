<?php
require_once 'database.php';
require_once 'functions.php';

class Auth {
    private $db;
    private $conn;

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    public function register($name, $email, $password, $role = 'user') {
        // Check if user already exists
        $query = "SELECT id FROM users WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return false; // User already exists
        }
        
        // Hash password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user
        $query = "INSERT INTO users SET name=:name, email=:email, password_hash=:password_hash, role=:role";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password_hash', $password_hash);
        $stmt->bindParam(':role', $role);
        
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }

    public function login($email, $password) {
        $query = "SELECT id, name, email, password_hash, role, org_id, bio FROM users WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $id = $row['id'];
            $name = $row['name'];
            $email = $row['email'];
            $password_hash = $row['password_hash'];
            $role = $row['role'];
            $org_id = $row['org_id'];
            $bio = $row['bio'];
            
            if (password_verify($password, $password_hash)) {
                $_SESSION['user_id'] = $id;
                $_SESSION['name'] = $name;
                $_SESSION['email'] = $email;
                $_SESSION['role'] = $role;
                $_SESSION['org_id'] = $org_id;
                $_SESSION['bio'] = $bio;
                $_SESSION['logged_in'] = true;
                
                return true;
            }
        }
        
        return false;
    }

    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    public function redirectIfNotLoggedIn() {
        if (!$this->isLoggedIn()) {
            header("Location: ../auth/login.php");
            exit();
        }
    }

    public function redirectBasedOnRole() {
        if ($this->isLoggedIn()) {
            $role = $_SESSION['role'];
            
            switch ($role) {
                case 'admin':
                    header("Location: ../admin/dashboard.php");
                    break;
                case 'org':
                    header("Location: ../org/dashboard.php");
                    break;
                default:
                    header("Location: ../user/dashboard.php");
                    break;
            }
            exit();
        }
    }

    public function logout() {
        $_SESSION = array();
        session_destroy();
    }

    public function hasPermission($required_role) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $user_role = $_SESSION['role'];
        
        // Admin has all permissions
        if ($user_role === 'admin') {
            return true;
        }
        
        // Check if user has the required role
        return $user_role === $required_role;
    }
}
?>