<?php
require_once '../includes/header.php';

$auth = new Auth();
$auth->redirectIfNotLoggedIn();

// Only allow users to access this page
if (!$auth->hasPermission('user')) {
    header('Location: ../index.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Get user's tasks
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM tasks WHERE user_id = :user_id ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count tasks by status
$query = "SELECT status, COUNT(*) as count FROM tasks WHERE user_id = :user_id GROUP BY status";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$task_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pending_count = 0;
$completed_count = 0;

foreach ($task_counts as $count) {
    if ($count['status'] === 'pending') {
        $pending_count = $count['count'];
    } elseif ($count['status'] === 'completed') {
        $completed_count = $count['count'];
    }
}

$total_tasks = $pending_count + $completed_count;
?>

<div class="stats">
    <div class="stat-card">
        <h3><?php echo $total_tasks; ?></h3>
        <p>Total Tasks</p>
    </div>
    <div class="stat-card">
        <h3><?php echo $pending_count; ?></h3>
        <p>Pending Tasks</p>
    </div>
    <div class="stat-card">
        <h3><?php echo $completed_count; ?></h3>
        <p>Completed Tasks</p>
    </div>
</div>

<div class="card">
    <h2>Recent Tasks</h2>
    
    <?php if (count($tasks) > 0): ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tasks as $task): ?>
                    <tr>
                        <td><?php echo $task['title']; ?></td>
                        <td><?php echo formatDate($task['due_date']); ?></td>
                        <td><?php echo getStatusBadge($task['status']); ?></td>
                        <td>
                            <a href="tasks.php?action=view&id=<?php echo $task['id']; ?>" class="btn">View</a>
                            <a href="tasks.php?action=edit&id=<?php echo $task['id']; ?>" class="btn btn-warning">Edit</a>
                            <a href="tasks.php?action=delete&id=<?php echo $task['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>You don't have any tasks yet. <a href="tasks.php?action=create">Create your first task</a></p>
    <?php endif; ?>
    
    <a href="tasks.php?action=create" class="btn">Add New Task</a>
</div>

<?php
require_once '../includes/footer.php';
?>