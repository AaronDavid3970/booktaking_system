<?php
require 'db.php';
require_login(); // Add this line to protect the page
?>

<?php
// return.php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$borrower = trim($_POST['borrower'] ?? '');

if ($id <= 0 || $borrower === '') {
    header('Location: read.php?id=' . $id . '&msg=' . urlencode('❌ Please provide your name.'));
    exit;
}

try {
    $pdo->beginTransaction();

    // Increase stock (no upper limit here — you could enforce a maxStock if desired)
    $stmt = $pdo->prepare("UPDATE books SET stock = stock + 1 WHERE id = ?");
    $stmt->execute([$id]);

    // Insert return transaction
    $stmt = $pdo->prepare("INSERT INTO transactions (book_id, borrower_name, action) VALUES (:book_id, :borrower, 'return')");
    $stmt->execute([':book_id' => $id, ':borrower' => $borrower]);

    $pdo->commit();

    header('Location: read.php?id=' . $id . '&msg=' . urlencode('✅ Book returned successfully! Thank you.'));
    exit;
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    header('Location: read.php?id=' . $id . '&msg=' . urlencode('❌ Error processing return.'));
    exit;
}
?>