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
$org_id = isset($_GET['id']) ? $_GET['id'] : null;

// Handle different actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['create_org'])) {
        $name = sanitizeInput($_POST['name']);
        $description = sanitizeInput($_POST['description']);
        
        $query = "INSERT INTO organizations SET name=:name, description=:description";
        $stmt = $conn->prepare($query);
        
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':description', $description);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = 'Organization created successfully.';
            header('Location: organizations.php');
            exit();
        } else {
            $error = 'Failed to create organization.';
        }
    } elseif (isset($_POST['update_org'])) {
        $org_id = sanitizeInput($_POST['org_id']);
        $name = sanitizeInput($_POST['name']);
        $description = sanitizeInput($_POST['description']);
        
        $query = "UPDATE organizations SET name=:name, description=:description WHERE id=:id";
        $stmt = $conn->prepare($query);
        
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':id', $org_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = 'Organization updated successfully.';
            header('Location: organizations.php');
            exit();
        } else {
            $error = 'Failed to update organization.';
        }
    }
} elseif ($action == 'delete' && $org_id) {
    // Check if organization has users
    $query = "SELECT COUNT(*) as count FROM users WHERE org_id = :org_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':org_id', $org_id);
    $stmt->execute();
    $user_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($user_count > 0) {
        $_SESSION['message'] = 'Cannot delete organization that has users. Please reassign users first.';
    } else {
        $query = "DELETE FROM organizations WHERE id=:id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $org_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = 'Organization deleted successfully.';
        } else {
            $_SESSION['message'] = 'Failed to delete organization.';
        }
    }
    
    header('Location: organizations.php');
    exit();
}

// Display appropriate view based on action
if ($action == 'create' || $action == 'edit') {
    $name = $description = '';
    
    if ($action == 'edit' && $org_id) {
        // Get organization details
        $query = "SELECT * FROM organizations WHERE id=:id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $org_id);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $org = $stmt->fetch(PDO::FETCH_ASSOC);
            $name = $org['name'];
            $description = $org['description'];
        } else {
            $_SESSION['message'] = 'Organization not found.';
            header('Location: organizations.php');
            exit();
        }
    }
    ?>
    
    <div class="card">
        <h2><?php echo $action == 'create' ? 'Create New Organization' : 'Edit Organization'; ?></h2>
        
        <?php if (isset($error)): ?>
            <div class="alert"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <?php if ($action == 'edit'): ?>
                <input type="hidden" name="org_id" value="<?php echo $org_id; ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label for="name">Organization Name</label>
                <input type="text" id="name" name="name" value="<?php echo $name; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4"><?php echo $description; ?></textarea>
            </div>
            
            <button type="submit" name="<?php echo $action == 'create' ? 'create_org' : 'update_org'; ?>" class="btn">
                <?php echo $action == 'create' ? 'Create Organization' : 'Update Organization'; ?>
            </button>
            <a href="organizations.php" class="btn btn-danger">Cancel</a>
        </form>
    </div>
    
    <?php
} elseif ($action == 'view' && $org_id) {
    // Get organization details
    $query = "SELECT * FROM organizations WHERE id=:id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $org_id);
    $stmt->execute();
    
    if ($stmt->rowCount() == 1) {
        $org = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get organization members
        $query = "SELECT * FROM users WHERE org_id = :org_id ORDER BY name";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':org_id', $org_id);
        $stmt->execute();
        $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        
        <div class="card">
            <h2>Organization Details</h2>
            
            <div class="org-details">
                <h3><?php echo $org['name']; ?></h3>
                <p><strong>Description:</strong> <?php echo $org['description']; ?></p>
                <p><strong>Created:</strong> <?php echo formatDate($org['created_at']); ?></p>
            </div>
            
            <div class="actions">
                <a href="organizations.php?action=edit&id=<?php echo $org['id']; ?>" class="btn btn-warning">Edit</a>
                <a href="organizations.php?action=delete&id=<?php echo $org['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure? This will remove all organization associations from users.')">Delete</a>
                <a href="organizations.php" class="btn">Back to Organizations</a>
            </div>
        </div>
        
        <div class="card">
            <h2>Organization Members (<?php echo count($members); ?>)</h2>
            
            <?php if (count($members) > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($members as $member): ?>
                            <tr>
                                <td><?php echo $member['name']; ?></td>
                                <td><?php echo $member['email']; ?></td>
                                <td><?php echo ucfirst($member['role']); ?></td>
                                <td><?php echo formatDate($member['created_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>This organization has no members.</p>
            <?php endif; ?>
        </div>
        
        <?php
    } else {
        $_SESSION['message'] = 'Organization not found.';
        header('Location: organizations.php');
        exit();
    }
} else {
    // List all organizations
    $query = "SELECT o.*, COUNT(u.id) as member_count FROM organizations o LEFT JOIN users u ON o.id = u.org_id GROUP BY o.id ORDER BY o.name";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $organizations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>
    
    <div class="card">
        <h2>Manage Organizations</h2>
        
        <a href="organizations.php?action=create" class="btn" style="margin-bottom: 20px;">Create New Organization</a>
        
        <?php if (count($organizations) > 0): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Members</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($organizations as $org): ?>
                        <tr>
                            <td><?php echo $org['name']; ?></td>
                            <td><?php echo strlen($org['description']) > 50 ? substr($org['description'], 0, 50) . '...' : $org['description']; ?></td>
                            <td><?php echo $org['member_count']; ?></td>
                            <td><?php echo formatDate($org['created_at']); ?></td>
                            <td>
                                <a href="organizations.php?action=view&id=<?php echo $org['id']; ?>" class="btn">View</a>
                                <a href="organizations.php?action=edit&id=<?php echo $org['id']; ?>" class="btn btn-warning">Edit</a>
                                <a href="organizations.php?action=delete&id=<?php echo $org['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure? This will remove all organization associations from users.')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No organizations found.</p>
        <?php endif; ?>
    </div>
    
    <?php
}

require_once '../includes/footer.php';
?>