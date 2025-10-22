<?php
/**
 * Temporary script to fix deleted users with multiple "deleted_" prefixes
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

// Sadece admin erişebilir
if (!$auth->check() || $auth->user()->getUserType() !== 'admin') {
    die('Bu sayfaya erişim yetkiniz yok.');
}

$conn = $db->getConnection();

// Find users with multiple deleted_ prefixes
$stmt = $conn->query("
    SELECT id, email, is_active, created_at
    FROM users
    WHERE email LIKE 'deleted_%'
    ORDER BY email
");
$deletedUsers = $stmt->fetchAll();

echo "<!DOCTYPE html>";
echo "<html><head><title>Deleted Users Fix</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "</head><body class='p-4'>";
echo "<div class='container'>";
echo "<h2 class='mb-4'>🔧 Deleted Users - Debug & Fix</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'fix_all' && isset($_POST['confirm'])) {
        // Fix all users with multiple deleted_ prefixes
        // Strategy: Keep first timestamp, remove all other deleted_ prefixes
        $stmt = $conn->query("
            SELECT id, email
            FROM users
            WHERE email LIKE 'deleted_%deleted_%'
        ");
        $usersToFix = $stmt->fetchAll();

        $fixed = 0;
        foreach ($usersToFix as $user) {
            // Remove all 'deleted_TIMESTAMP_' patterns, extract original email
            $originalEmail = preg_replace('/^(deleted_\d+_)+/', '', $user['email']);

            // Get first timestamp from the email
            preg_match('/^deleted_(\d+)_/', $user['email'], $matches);
            $firstTimestamp = $matches[1] ?? time();

            // Create new email with single deleted_ prefix
            $newEmail = "deleted_{$firstTimestamp}_{$originalEmail}";

            $updateStmt = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
            $updateStmt->execute([$newEmail, $user['id']]);
            $fixed++;

            error_log("Fixed user ID {$user['id']}: '{$user['email']}' -> '{$newEmail}'");
        }

        echo "<div class='alert alert-success'>";
        echo "✅ {$fixed} kullanıcının email'i temizlendi. <a href='fix-deleted-users.php'>Sayfayı yenile</a>";
        echo "</div>";
    }

    if ($_POST['action'] === 'permanently_delete' && isset($_POST['user_id']) && isset($_POST['confirm'])) {
        $userId = (int)$_POST['user_id'];

        // Delete related records first
        $conn->prepare("DELETE FROM client_dietitian_assignments WHERE client_id = ? OR dietitian_id = ?")->execute([$userId, $userId]);
        $conn->prepare("DELETE FROM appointments WHERE client_id = ? OR dietitian_id = ?")->execute([$userId, $userId]);
        $conn->prepare("DELETE FROM dietitian_profiles WHERE user_id = ?")->execute([$userId]);
        $conn->prepare("DELETE FROM client_profiles WHERE user_id = ?")->execute([$userId]);

        // Delete user
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);

        echo "<div class='alert alert-success'>";
        echo "✅ Kullanıcı kalıcı olarak silindi. <a href='fix-deleted-users.php'>Sayfayı yenile</a>";
        echo "</div>";
    }
}

echo "<div class='card mb-4'>";
echo "<div class='card-body'>";
echo "<h5>📊 Statistics</h5>";
echo "<p><strong>Total deleted users:</strong> " . count($deletedUsers) . "</p>";

$multipleDeleted = array_filter($deletedUsers, function($u) {
    return substr_count($u['email'], 'deleted_') > 1;
});
echo "<p><strong>Users with multiple deleted_ prefixes:</strong> " . count($multipleDeleted) . "</p>";
echo "</div></div>";

if (count($multipleDeleted) > 0) {
    echo "<div class='card mb-4'>";
    echo "<div class='card-body'>";
    echo "<h5>🔧 Fix All Users</h5>";
    echo "<form method='POST'>";
    echo "<input type='hidden' name='action' value='fix_all'>";
    echo "<div class='form-check mb-3'>";
    echo "<input type='checkbox' name='confirm' class='form-check-input' id='confirmFix' required>";
    echo "<label class='form-check-label' for='confirmFix'>";
    echo "Tüm kullanıcıların email'ini temizle (deleted_ prefix'lerini kaldır)";
    echo "</label>";
    echo "</div>";
    echo "<button type='submit' class='btn btn-primary'>Fix All</button>";
    echo "</form>";
    echo "</div></div>";
}

echo "<div class='card'>";
echo "<div class='card-body'>";
echo "<h5>📋 Deleted Users List</h5>";

if (count($deletedUsers) === 0) {
    echo "<p class='text-muted'>No deleted users found.</p>";
} else {
    echo "<div class='table-responsive'>";
    echo "<table class='table table-sm'>";
    echo "<thead><tr><th>ID</th><th>Email</th><th>Active</th><th>Created</th><th>Prefix Count</th><th>Action</th></tr></thead>";
    echo "<tbody>";

    foreach ($deletedUsers as $user) {
        $prefixCount = substr_count($user['email'], 'deleted_');
        $rowClass = $prefixCount > 1 ? 'table-warning' : '';

        echo "<tr class='{$rowClass}'>";
        echo "<td>{$user['id']}</td>";
        echo "<td><small>" . htmlspecialchars($user['email']) . "</small></td>";
        echo "<td>" . ($user['is_active'] ? '✅' : '❌') . "</td>";
        echo "<td>" . date('Y-m-d H:i', strtotime($user['created_at'])) . "</td>";
        echo "<td><span class='badge bg-" . ($prefixCount > 1 ? 'danger' : 'secondary') . "'>{$prefixCount}</span></td>";
        echo "<td>";
        echo "<form method='POST' class='d-inline' onsubmit='return confirm(\"Permanently delete user {$user['id']}?\")'>";
        echo "<input type='hidden' name='action' value='permanently_delete'>";
        echo "<input type='hidden' name='user_id' value='{$user['id']}'>";
        echo "<input type='hidden' name='confirm' value='1'>";
        echo "<button type='submit' class='btn btn-sm btn-danger'>Permanently Delete</button>";
        echo "</form>";
        echo "</td>";
        echo "</tr>";
    }

    echo "</tbody></table>";
    echo "</div>";
}

echo "</div></div>";

echo "<div class='mt-4'>";
echo "<a href='/admin/users.php' class='btn btn-secondary'>← Back to Users</a>";
echo "</div>";

echo "</div></body></html>";
