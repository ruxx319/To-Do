<?php
require_once 'BaseModel.php';

class User extends BaseModel {
    public function __construct() {
        parent::__construct('users');
    }

    public function getByEmail($email) {
        $query = "SELECT * FROM users WHERE email = :email";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getByRole($role) {
        $query = "SELECT * FROM users WHERE role = :role ORDER BY name";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':role', $role);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOrganizationMembers($org_id) {
        $query = "SELECT * FROM users WHERE org_id = :org_id ORDER BY name";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':org_id', $org_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>