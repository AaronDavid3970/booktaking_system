<?php
// delete.php - Delete book with cover image cleanup
require 'db.php';

$id = $_GET['id'] ?? null;
if ($id) {
    // Fetch book for cover filename
    $stmt = $pdo->prepare("SELECT cover_image FROM books WHERE id = ?");
    $stmt->execute([$id]);
    $b = $stmt->fetch();
    
    if ($b) {
        // Delete cover image if exists
        if (!empty($b['cover_image']) && file_exists(__DIR__.'/uploads/'.$b['cover_image'])) {
            @unlink(__DIR__.'/uploads/'.$b['cover_image']);
        }
    }
    
    // Delete book from database
    $stmt = $pdo->prepare("DELETE FROM books WHERE id = ?");
    $stmt->execute([$id]);
    
    // Redirect with success message
    header('Location: index.php?success=' . urlencode('✅ Book deleted successfully!'));
    exit;
}

header('Location: index.php');
exit;
?>