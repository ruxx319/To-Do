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

$user_id = $_SESSION['user_id'];
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$task_id = isset($_GET['id']) ? $_GET['id'] : null;

// Handle different actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    CSRF::validateRequest(); // CSRF validation
    
    if (isset($_POST['create_task'])) {
        $title = sanitizeInput($_POST['title']);
        $description = sanitizeInput($_POST['description']);
        $due_date = sanitizeInput($_POST['due_date']);
        $status = sanitizeInput($_POST['status']);
        
        $query = "INSERT INTO tasks SET user_id=:user_id, title=:title, description=:description, due_date=:due_date, status=:status";
        $stmt = $conn->prepare($query);
        
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':due_date', $due_date);
        $stmt->bindParam(':status', $status);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = 'Task created successfully.';
            header('Location: tasks.php');
            exit();
        } else {
            $error = 'Failed to create task.';
        }
    } elseif (isset($_POST['update_task'])) {
        $task_id = sanitizeInput($_POST['task_id']);
        $title = sanitizeInput($_POST['title']);
        $description = sanitizeInput($_POST['description']);
        $due_date = sanitizeInput($_POST['due_date']);
        $status = sanitizeInput($_POST['status']);
        
        // Verify task belongs to user
        $query = "SELECT id FROM tasks WHERE id=:id AND user_id=:user_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $task_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $query = "UPDATE tasks SET title=:title, description=:description, due_date=:due_date, status=:status WHERE id=:id";
            $stmt = $conn->prepare($query);
            
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':due_date', $due_date);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':id', $task_id);
            
            if ($stmt->execute()) {
                $_SESSION['message'] = 'Task updated successfully.';
                header('Location: tasks.php');
                exit();
            } else {
                $error = 'Failed to update task.';
            }
        } else {
            $error = 'Task not found or access denied.';
        }
    }
} elseif ($action == 'delete' && $task_id) {
    // Verify task belongs to user
    $query = "SELECT id FROM tasks WHERE id=:id AND user_id=:user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $task_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    if ($stmt->rowCount() == 1) {
        $query = "DELETE FROM tasks WHERE id=:id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $task_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = 'Task deleted successfully.';
        } else {
            $_SESSION['message'] = 'Failed to delete task.';
        }
    } else {
        $_SESSION['message'] = 'Task not found or access denied.';
    }
    
    header('Location: tasks.php');
    exit();
}

// Display appropriate view based on action
if ($action == 'create' || $action == 'edit') {
    $title = $description = $due_date = '';
    $status = 'pending';
    
    if ($action == 'edit' && $task_id) {
        // Get task details
        $query = "SELECT * FROM tasks WHERE id=:id AND user_id=:user_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $task_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            $title = $task['title'];
            $description = $task['description'];
            $due_date = $task['due_date'];
            $status = $task['status'];
        } else {
            $_SESSION['message'] = 'Task not found or access denied.';
            header('Location: tasks.php');
            exit();
        }
    }
    ?>
    
    <div class="card">
        <h2><?php echo $action == 'create' ? 'Create New Task' : 'Edit Task'; ?></h2>
        
        <?php if (isset($error)): ?>
            <div class="alert"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <?php echo CSRF::getTokenField(); ?>
            
            <?php if ($action == 'edit'): ?>
                <input type="hidden" name="task_id" value="<?php echo $task_id; ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" value="<?php echo $title; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4"><?php echo $description; ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="due_date">Due Date</label>
                <input type="date" id="due_date" name="due_date" value="<?php echo $due_date; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status" required>
                    <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="completed" <?php echo $status == 'completed' ? 'selected' : ''; ?>>Completed</option>
                </select>
            </div>
            
            <button type="submit" name="<?php echo $action == 'create' ? 'create_task' : 'update_task'; ?>" class="btn">
                <?php echo $action == 'create' ? 'Create Task' : 'Update Task'; ?>
            </button>
            <a href="tasks.php" class="btn btn-danger">Cancel</a>
        </form>
    </div>
    
    <?php
} elseif ($action == 'view' && $task_id) {
    // Get task details
    $query = "SELECT * FROM tasks WHERE id=:id AND user_id=:user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $task_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    if ($stmt->rowCount() == 1) {
        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        ?>
        
        <div class="card">
            <h2>Task Details</h2>
            
            <div class="task-details">
                <h3><?php echo $task['title']; ?></h3>
                <p><strong>Description:</strong> <?php echo $task['description']; ?></p>
                <p><strong>Due Date:</strong> <?php echo formatDate($task['due_date']); ?></p>
                <p><strong>Status:</strong> <?php echo getStatusBadge($task['status']); ?></p>
                <p><strong>Created:</strong> <?php echo formatDate($task['created_at']); ?></p>
                <p><strong>Last Updated:</strong> <?php echo formatDate($task['updated_at']); ?></p>
            </div>
            
            <div class="actions">
                <a href="tasks.php?action=edit&id=<?php echo $task['id']; ?>" class="btn btn-warning">Edit</a>
                <a href="tasks.php?action=delete&id=<?php echo $task['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                <a href="tasks.php" class="btn">Back to Tasks</a>
            </div>
        </div>
        
        <?php
    } else {
        $_SESSION['message'] = 'Task not found or access denied.';
        header('Location: tasks.php');
        exit();
    }
} else {
    // List all tasks
    $query = "SELECT * FROM tasks WHERE user_id = :user_id ORDER BY created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>
    
    <div class="card">
        <h2>My Tasks</h2>
        
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
            <p>You don't have any tasks yet.</p>
        <?php endif; ?>
        
        <a href="tasks.php?action=create" class="btn">Add New Task</a>
    </div>
    
    <?php
}

require_once '../includes/footer.php';
?>