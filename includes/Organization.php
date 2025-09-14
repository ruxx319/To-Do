<?php
require_once 'BaseModel.php';

class Organization extends BaseModel {
    public function __construct() {
        parent::__construct('organizations');
    }

    public function create($name, $description) {
        $query = "INSERT INTO organizations SET name = :name, description = :description";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':description', $description);
        return $stmt->execute();
    }

    public function getWithMemberCount() {
        $query = "SELECT o.*, COUNT(u.id) as member_count 
                  FROM organizations o 
                  LEFT JOIN users u ON o.id = u.org_id 
                  GROUP BY o.id 
                  ORDER BY o.name";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>