#!/usr/bin/env php
<?php
/**
 * Capitalize User Names
 * Makes the first letter of each word in full_name uppercase
 */

require_once __DIR__ . '/../includes/bootstrap.php';

try {
    $conn = $db->getConnection();

    echo "Starting name capitalization...\n\n";

    // Get all users
    $stmt = $conn->query("SELECT id, full_name FROM users WHERE full_name IS NOT NULL AND full_name != ''");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Found " . count($users) . " users to process.\n\n";

    $updateStmt = $conn->prepare("UPDATE users SET full_name = ? WHERE id = ?");
    $updated = 0;

    foreach ($users as $user) {
        $oldName = $user['full_name'];

        // Capitalize first letter of each word
        // mb_convert_case kullanarak Türkçe karakterleri de düzgün işle
        $newName = mb_convert_case($oldName, MB_CASE_TITLE, 'UTF-8');

        if ($oldName !== $newName) {
            $updateStmt->execute([$newName, $user['id']]);
            echo "Updated: '{$oldName}' → '{$newName}'\n";
            $updated++;
        }
    }

    echo "\n✅ Completed!\n";
    echo "Total users: " . count($users) . "\n";
    echo "Updated: {$updated}\n";
    echo "Unchanged: " . (count($users) - $updated) . "\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
