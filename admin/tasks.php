<?php
require_once '../includes/header.php';

$auth = new Auth();
$auth->redirectIfNotLoggedIn();

// Only allow admins to access this page
if (!$auth->hasPermission('admin')) {
    header('Location: ../index.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$task_id = isset($_GET['id']) ? $_GET['id'] : null;

// Handle different actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_task'])) {
        $task_id = sanitizeInput($_POST['task_id']);
        $title = sanitizeInput($_POST['title']);
        $description = sanitizeInput($_POST['description']);
        $due_date = sanitizeInput($_POST['due_date']);
        $status = sanitizeInput($_POST['status']);
        $user_id = sanitizeInput($_POST['user_id']);
        $org_id = sanitizeInput($_POST['org_id']);
        
        $query = "UPDATE tasks SET title=:title, description=:description, due_date=:due_date, status=:status, user_id=:user_id, org_id=:org_id WHERE id=:id";
        $stmt = $conn->prepare($query);
        
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':due_date', $due_date);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':org_id', $org_id);
        $stmt->bindParam(':id', $task_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = 'Task updated successfully.';
            header('Location: tasks.php');
            exit();
        } else {
            $error = 'Failed to update task.';
        }
    }
} elseif ($action == 'delete' && $task_id) {
    $query = "DELETE FROM tasks WHERE id=:id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $task_id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = 'Task deleted successfully.';
    } else {
        $_SESSION['message'] = 'Failed to delete task.';
    }
    
    header('Location: tasks.php');
    exit();
}

// Display appropriate view based on action
if ($action == 'edit' && $task_id) {
    // Get task details
    $query = "SELECT * FROM tasks WHERE id=:id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $task_id);
    $stmt->execute();
    
    if ($stmt->rowCount() == 1) {
        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get all users
        $query = "SELECT * FROM users ORDER BY name";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get all organizations
        $query = "SELECT * FROM organizations ORDER BY name";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $organizations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        
        <div class="card">
            <h2>Edit Task</h2>
            
            <?php if (isset($error)): ?>
                <div class="alert"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="task_id" value="<?php echo $task_id; ?>">
                
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" value="<?php echo $task['title']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4"><?php echo $task['description']; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="due_date">Due Date</label>
                    <input type="date" id="due_date" name="due_date" value="<?php echo $task['due_date']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" required>
                        <option value="pending" <?php echo $task['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="completed" <?php echo $task['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="user_id">Assigned User</label>
                    <select id="user_id" name="user_id" required>
                        <option value="">Select User</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['id']; ?>" <?php echo $task['user_id'] == $user['id'] ? 'selected' : ''; ?>>
                                <?php echo $user['name']; ?> (<?php echo $user['email']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="org_id">Organization</label>
                    <select id="org_id" name="org_id">
                        <option value="">None</option>
                        <?php foreach ($organizations as $org): ?>
                            <option value="<?php echo $org['id']; ?>" <?php echo $task['org_id'] == $org['id'] ? 'selected' : ''; ?>>
                                <?php echo $org['name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" name="update_task" class="btn">Update Task</button>
                <a href="tasks.php" class="btn btn-danger">Cancel</a>
            </form>
        </div>
        
        <?php
    } else {
        $_SESSION['message'] = 'Task not found.';
        header('Location: tasks.php');
        exit();
    }
} elseif ($action == 'view' && $task_id) {
    // Get task details
    $query = "SELECT t.*, u.name as user_name, o.name as org_name FROM tasks t LEFT JOIN users u ON t.user_id = u.id LEFT JOIN organizations o ON t.org_id = o.id WHERE t.id=:id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $task_id);
    $stmt->execute();
    
    if ($stmt->rowCount() == 1) {
        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        ?>
        
        <div class="card">
            <h2>Task Details</h2>
            
            <div class="task-details">
                <h3><?php echo $task['title']; ?></h3>
                <p><strong>Description:</strong> <?php echo $task['description']; ?></p>
                <p><strong>Assigned to:</strong> <?php echo $task['user_name']; ?></p>
                <p><strong>Organization:</strong> <?php echo $task['org_name'] ? $task['org_name'] : 'None'; ?></p>
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
        $_SESSION['message'] = 'Task not found.';
        header('Location: tasks.php');
        exit();
    }
} else {
    // List all tasks with filters
    $filter_user = isset($_GET['user']) ? $_GET['user'] : '';
    $filter_org = isset($_GET['org']) ? $_GET['org'] : '';
    $filter_status = isset($_GET['status']) ? $_GET['status'] : '';
    
    $query = "SELECT t.*, u.name as user_name, o.name as org_name FROM tasks t LEFT JOIN users u ON t.user_id = u.id LEFT JOIN organizations o ON t.org_id = o.id WHERE 1=1";
    $params = [];
    
    if (!empty($filter_user)) {
        $query .= " AND t.user_id = :user_id";
        $params[':user_id'] = $filter_user;
    }
    
    if (!empty($filter_org)) {
        $query .= " AND t.org_id = :org_id";
        $params[':org_id'] = $filter_org;
    }
    
    if (!empty($filter_status)) {
        $query .= " AND t.status = :status";
        $params[':status'] = $filter_status;
    }
    
    $query .= " ORDER BY t.created_at DESC";
    
    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all users for filter
    $query = "SELECT * FROM users ORDER BY name";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all organizations for filter
    $query = "SELECT * FROM organizations ORDER BY name";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $organizations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>
    
    <div class="card">
        <h2>Manage Tasks</h2>
        
        <div class="filters" style="margin-bottom: 20px;">
            <form method="GET" action="">
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <div>
                        <label for="user">Filter by User:</label>
                        <select id="user" name="user">
                            <option value="">All Users</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>" <?php echo $filter_user == $user['id'] ? 'selected' : ''; ?>>
                                    <?php echo $user['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="org">Filter by Organization:</label>
                        <select id="org" name="org">
                            <option value="">All Organizations</option>
                            <?php foreach ($organizations as $org): ?>
                                <option value="<?php echo $org['id']; ?>" <?php echo $filter_org == $org['id'] ? 'selected' : ''; ?>>
                                    <?php echo $org['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="status">Filter by Status:</label>
                        <select id="status" name="status">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="completed" <?php echo $filter_status == 'completed' ? 'selected' : ''; ?>>Completed</option>
                        </select>
                    </div>
                    
                    <div style="align-self: end;">
                        <button type="submit" class="btn">Apply Filters</button>
                        <a href="tasks.php" class="btn btn-danger">Clear</a>
                    </div>
                </div>
            </form>
        </div>
        
        <?php if (count($tasks) > 0): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Assigned To</th>
                        <th>Organization</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tasks as $task): ?>
                        <tr>
                            <td><?php echo strlen($task['title']) > 30 ? substr($task['title'], 0, 30) . '...' : $task['title']; ?></td>
                            <td><?php echo $task['user_name']; ?></td>
                            <td><?php echo $task['org_name'] ? $task['org_name'] : 'None'; ?></td>
                            <td><?php echo formatDate($task['due_date']); ?></td>
                            <td><?php echo getStatusBadge($task['status']); ?></td>
                            <td><?php echo formatDate($task['created_at']); ?></td>
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
            <p>No tasks found.</p>
        <?php endif; ?>
    </div>
    
    <?php
}

require_once '../includes/footer.php';
?>