<?php
require_once '../includes/header.php';

$auth = new Auth();
$auth->redirectIfNotLoggedIn();

if (!$auth->hasPermission('user')) {
    header('Location: ../index.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];

// Get tasks by status
$statuses = ['pending', 'in_progress', 'completed'];
$tasks_by_status = [];

foreach ($statuses as $status) {
    $query = "SELECT * FROM tasks WHERE user_id = :user_id AND status = :status ORDER BY due_date";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':status', $status);
    $stmt->execute();
    $tasks_by_status[$status] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="card">
    <h2>My Task Board</h2>
    
    <div class="kanban-board">
        <?php foreach ($statuses as $status): ?>
            <div class="kanban-column">
                <h3><?php echo ucfirst(str_replace('_', ' ', $status)); ?></h3>
                <?php foreach ($tasks_by_status[$status] as $task): ?>
                    <div class="kanban-task" draggable="true" data-task-id="<?php echo $task['id']; ?>">
                        <h4><?php echo $task['title']; ?></h4>
                        <p><?php echo strlen($task['description']) > 100 ? substr($task['description'], 0, 100) . '...' : $task['description']; ?></p>
                        <div class="task-meta">
                            <span class="priority-badge priority-<?php echo $task['priority']; ?>">
                                <?php echo ucfirst($task['priority']); ?>
                            </span>
                            <span>Due: <?php echo formatDate($task['due_date']); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
// Add drag and drop functionality here
</script>

<?php
require_once '../includes/footer.php';
?>