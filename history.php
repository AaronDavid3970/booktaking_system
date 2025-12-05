<?php
require 'db.php';
require_login(); // Add this line to protect the page
?>

<?php
// history.php
require 'db.php';

// Handle delete transaction
if (isset($_GET['delete_transaction'])) {
    $transaction_id = (int)$_GET['delete_transaction'];
    try {
        $stmt = $pdo->prepare("DELETE FROM transactions WHERE id = ?");
        $stmt->execute([$transaction_id]);
        header('Location: history.php?success=' . urlencode('✅ Transaction deleted successfully!'));
        exit;
    } catch (Exception $e) {
        header('Location: history.php?error=' . urlencode('❌ Error deleting transaction.'));
        exit;
    }
}

$book_id = isset($_GET['book_id']) ? (int)$_GET['book_id'] : 0;
$q = trim($_GET['q'] ?? '');

$params = [];
$sql = "SELECT t.*, b.title AS book_title
FROM transactions t
LEFT JOIN books b ON b.id = t.book_id";

$where = [];
if ($book_id) {
  $where[] = "t.book_id = :book_id";
  $params[':book_id'] = $book_id;
}
if ($q !== '') {
  $where[] = "(t.borrower_name LIKE :q OR b.title LIKE :q)";
  $params[':q'] = "%$q%";
}
if ($where) $sql .= " WHERE " . implode(' AND ', $where);
$sql .= " ORDER BY t.created_at DESC LIMIT 200";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Get stats
$total_transactions = count($rows);
$borrow_count = 0;
$return_count = 0;

foreach($rows as $row) {
  if ($row['action'] === 'borrow') $borrow_count++;
  if ($row['action'] === 'return') $return_count++;
}

// Check for success/error messages
$success_msg = $_GET['success'] ?? '';
$error_msg = $_GET['error'] ?? '';
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Transactions - BookSystem</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  
  <!-- Bootstrap 5 -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top shadow-sm">
    <div class="container">
      <a class="navbar-brand fw-bold" href="index.php">📚 BookSystem</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navmenu" aria-controls="navmenu" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navmenu">
        <ul class="navbar-nav me-auto">
          <li class="nav-item">
            <a class="nav-link" href="index.php">🏠 HOME</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="books.php">📖 BOOKS</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="borrowers.php">👥 BORROWERS</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="history.php">📊 TRANSACTIONS</a>
          </li>
        </ul>

        <form class="d-flex me-2 mt-2 mt-lg-0" method="get" style="width:100%; max-width:400px;">
          <input class="form-control me-2" type="search" name="q" placeholder="🔍 Search books..." value="<?=htmlspecialchars($q ?? '')?>">
          <button class="btn btn-primary" type="submit">Search</button>
        </form>
      </li>
    </ul>
  </div>
</div>
</nav>

<div class="container mt-4">
  <div class="main-card">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-1">📊 Transaction History</h2>
        <p class="text-muted mb-0">View all book borrowing and return activities</p>
      </div>
      <a href="index.php" class="btn btn-outline-secondary btn-sm">← Back to Books</a>
    </div>

    <!-- Alerts -->
    <?php if ($success_msg): ?>
      <div class="alert alert-success mb-4">
        <i class="me-2">✅</i> <?= htmlspecialchars($success_msg) ?>
      </div>
    <?php endif; ?>

    <?php if ($error_msg): ?>
      <div class="alert alert-danger mb-4">
        <i class="me-2">❌</i> <?= htmlspecialchars($error_msg) ?>
      </div>
    <?php endif; ?>

    <!-- Search Card -->
    <div class="card border-0 bg-light mb-4">
      <div class="card-body">
        <h5 class="card-title mb-3">🔍 Search Transactions</h5>
        <form method="get" class="row g-2">
          <input type="hidden" name="book_id" value="<?=intval($book_id)?>">
          <div class="col-12 col-md-8">
            <input class="form-control form-control-lg" type="text" name="q" placeholder="Search by borrower name or book title..." value="<?=htmlspecialchars($q)?>">
          </div>
          <div class="col-12 col-md-4">
            <button class="btn btn-primary w-100" type="submit">
              <i class="me-1">🔍</i> Search
            </button>
          </div>
          <div class="col-12">
            <a class="btn btn-outline-secondary btn-sm" href="history.php">
              <i class="me-1">🔄</i> Reset
            </a>
          </div>
        </form>
      </div>
    </div>

    <!-- Stats Card -->
    <?php if($total_transactions > 0): ?>
      <div class="card border-0 bg-info text-white mb-4">
        <div class="card-body">
          <div class="row text-center">
            <div class="col-12 col-md-4">
              <div class="stat-item">
                <h3 class="stat-number"><?=$total_transactions?></h3>
                <p class="stat-label mb-0">Total Transactions</p>
              </div>
            </div>
            <div class="col-12 col-md-4">
              <div class="stat-item">
                <h3 class="stat-number"><?=$borrow_count?></h3>
                <p class="stat-label mb-0">Books Borrowed</p>
              </div>
            </div>
            <div class="col-12 col-md-4">
              <div class="stat-item">
                <h3 class="stat-number"><?=$return_count?></h3>
                <p class="stat-label mb-0">Books Returned</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- Transactions Table -->
    <?php if (count($rows) === 0): ?>
      <div class="card border-0 bg-light">
        <div class="card-body text-center py-5">
          <i class="fs-1 text-muted">📝</i>
          <h4 class="text-muted mt-3">No transactions found</h4>
          <p class="text-muted">No transactions match your search criteria.</p>
          <a href="history.php" class="btn btn-primary">View All Transactions</a>
        </div>
      </div>
    <?php else: ?>
      <div class="card border-0">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th scope="col" class="ps-4">Date & Time</th>
                  <th scope="col">Book</th>
                  <th scope="col">Borrower</th>
                  <th scope="col" class="text-center">Action</th>
                  <th scope="col" class="text-center pe-4">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($rows as $r): ?>
                  <tr>
                    <td class="ps-4">
                      <div class="d-flex align-items-center">
                        <div class="transaction-icon me-3">
                          <?php if ($r['action'] === 'borrow'): ?>
                            <i class="fs-4 text-primary">📥</i>
                          <?php else: ?>
                            <i class="fs-4 text-success">📤</i>
                          <?php endif; ?>
                        </div>
                        <div>
                          <div class="fw-semibold"><?=date('M j, Y', strtotime($r['created_at']))?></div>
                          <div class="small text-muted"><?=date('g:i A', strtotime($r['created_at']))?></div>
                        </div>
                      </div>
                    </td>
                    <td>
                      <div class="fw-semibold"><?=htmlspecialchars($r['book_title'] ?? '—')?></div>
                      <?php if($r['book_title']): ?>
                        <div class="small text-muted">Book</div>
                      <?php endif; ?>
                    </td>
                    <td>
                      <div class="fw-semibold"><?=htmlspecialchars($r['borrower_name'])?></div>
                      <div class="small text-muted">Borrower</div>
                    </td>
                    <td class="text-center">
                      <?php if ($r['action'] === 'borrow'): ?>
                        <span class="badge bg-primary rounded-pill px-3 py-2 fs-6">
                          <i class="me-1">📥</i> Borrow
                        </span>
                      <?php else: ?>
                        <span class="badge bg-success rounded-pill px-3 py-2 fs-6">
                          <i class="me-1">📤</i> Return
                        </span>
                      <?php endif; ?>
                    </td>
                    <td class="text-center pe-4">
                      <a class="btn btn-outline-danger btn-sm" 
                         href="history.php?delete_transaction=<?=$r['id']?>" 
                         onclick="return confirm('Are you sure you want to delete this transaction? This action cannot be undone.')"
                         title="Delete Transaction">
                        <i class="me-1">🗑️</i> Delete
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Results Info -->
      <div class="mt-3 text-center">
        <p class="text-muted small">
          Showing <?=count($rows)?> most recent transactions
          <?php if($book_id): ?> for this book<?php endif; ?>
        </p>
      </div>
    <?php endif; ?>
  </div>
</div>

<style>
  .transaction-icon {
    width: 40px;
    text-align: center;
  }
  
  .table > :not(caption) > * > * {
    padding: 1rem 0.5rem;
  }
  
  .badge.fs-6 {
    font-size: 0.9rem !important;
  }
</style>
</body>
</html>