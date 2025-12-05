<?php
require 'db.php';
require_login(); // Add this line to protect the page
?>

<?php
// take.php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$borrower = trim($_POST['borrower'] ?? '');

if ($id <= 0 || $borrower === '') {
    // invalid input
    header('Location: read.php?id=' . $id . '&msg=' . urlencode('❌ Please provide your name.'));
    exit;
}

try {
    // Begin transaction to avoid race conditions
    $pdo->beginTransaction();

    // Lock the row for update to prevent race conditions
    $stmt = $pdo->prepare("SELECT stock FROM books WHERE id = ? FOR UPDATE");
    $stmt->execute([$id]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$book) {
        $pdo->rollBack();
        header('Location: index.php?error=' . urlencode('❌ Book not found.'));
        exit;
    }

    $stock = (int)$book['stock'];
    if ($stock <= 0) {
        $pdo->rollBack();
        header('Location: read.php?id=' . $id . '&msg=' . urlencode('❌ No stock available to borrow.'));
        exit;
    }

    // decrement stock
    $stmt = $pdo->prepare("UPDATE books SET stock = stock - 1 WHERE id = ?");
    $stmt->execute([$id]);

    // insert transaction log
    $stmt = $pdo->prepare("INSERT INTO transactions (book_id, borrower_name, action) VALUES (:book_id, :borrower, 'borrow')");
    $stmt->execute([':book_id' => $id, ':borrower' => $borrower]);

    $pdo->commit();

    header('Location: read.php?id=' . $id . '&msg=' . urlencode('✅ Book borrowed successfully!'));
    exit;
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    // Log error in real app; show friendly message for now
    header('Location: read.php?id=' . $id . '&msg=' . urlencode('❌ Error processing request.'));
    exit;
}
?>