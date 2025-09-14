<?php
require_once 'BaseModel.php';

class Task extends BaseModel {
    public function __construct() {
        parent::__construct('tasks');
    }

    public function getUserTasks($user_id) {
        $query = "SELECT * FROM tasks WHERE user_id = :user_id ORDER BY created_at DESC";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOrganizationTasks($org_id) {
        $query = "SELECT t.*, u.name as user_name FROM tasks t 
                  LEFT JOIN users u ON t.user_id = u.id 
                  WHERE t.org_id = :org_id ORDER BY t.created_at DESC";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':org_id', $org_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTasksByStatus($status) {
        $query = "SELECT * FROM tasks WHERE status = :status ORDER BY created_at DESC";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatus($id, $status) {
        $query = "UPDATE tasks SET status = :status, updated_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function create($data) {
        $query = "INSERT INTO tasks SET 
                  user_id = :user_id, 
                  org_id = :org_id, 
                  title = :title, 
                  description = :description, 
                  status = :status, 
                  priority = :priority, 
                  due_date = :due_date, 
                  assigned_by = :assigned_by";
        
        $stmt = $this->db->prepare($query);
        return $stmt->execute($data);
    }
}
?>