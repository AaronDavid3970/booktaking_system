<?php
require 'db.php';
require_login(); // Add this line to protect the page
?>

<?php
// borrowers.php
require 'db.php';

// Handle delete borrower (delete all their transactions)
if (isset($_GET['delete_borrower'])) {
    $borrower_name = trim($_GET['delete_borrower']);
    try {
        $stmt = $pdo->prepare("DELETE FROM transactions WHERE borrower_name = ?");
        $stmt->execute([$borrower_name]);
        header('Location: borrowers.php?success=' . urlencode('✅ Borrower and all their transactions deleted successfully!'));
        exit;
    } catch (Exception $e) {
        header('Location: borrowers.php?error=' . urlencode('❌ Error deleting borrower.'));
        exit;
    }
}

// Get unique borrowers from transactions
$q = trim($_GET['q'] ?? '');
$params = [];
$sql = "SELECT DISTINCT borrower_name, COUNT(*) as transaction_count, 
SUM(CASE WHEN action = 'borrow' THEN 1 ELSE 0 END) as borrowed_count,
SUM(CASE WHEN action = 'return' THEN 1 ELSE 0 END) as returned_count
FROM transactions";

if ($q !== '') {
  $sql .= " WHERE borrower_name LIKE :q";
  $params[':q'] = "%$q%";
}

$sql .= " GROUP BY borrower_name ORDER BY borrower_name";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$borrowers = $stmt->fetchAll();

// Check for success/error messages
$success_msg = $_GET['success'] ?? '';
$error_msg = $_GET['error'] ?? '';
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Borrowers - BookSystem</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
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
      </div>
    </div>
  </nav>

  <div class="container mt-4">
    <div class="main-card">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 class="mb-1">📚 Borrowers</h2>
          <p class="text-muted mb-0">Manage and view all library borrowers</p>
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
          <h5 class="card-title mb-3">🔍 Search Borrowers</h5>
          <form method="get" class="row g-2">
            <div class="col-12 col-md-8">
              <input class="form-control form-control-lg" type="search" name="q" placeholder="Search by borrower name..." value="<?=htmlspecialchars($q)?>">
            </div>
            <div class="col-12 col-md-4">
              <button class="btn btn-primary w-100" type="submit">
                <i class="me-1">🔍</i> Search
              </button>
            </div>
            <div class="col-12">
              <a class="btn btn-outline-secondary btn-sm" href="borrowers.php">
                <i class="me-1">🔄</i> Reset
              </a>
            </div>
          </form>
        </div>
      </div>

      <!-- Stats Card -->
      <?php if(count($borrowers) > 0): ?>
        <div class="card border-0 bg-primary text-white mb-4">
          <div class="card-body">
            <div class="row text-center">
              <div class="col-12 col-md-4">
                <div class="stat-item">
                  <h3 class="stat-number"><?=count($borrowers)?></h3>
                  <p class="stat-label mb-0">Total Borrowers</p>
                </div>
              </div>
              <div class="col-12 col-md-4">
                <div class="stat-item">
                  <h3 class="stat-number"><?=array_sum(array_column($borrowers, 'borrowed_count'))?></h3>
                  <p class="stat-label mb-0">Total Borrows</p>
                </div>
              </div>
              <div class="col-12 col-md-4">
                <div class="stat-item">
                  <h3 class="stat-number"><?=array_sum(array_column($borrowers, 'returned_count'))?></h3>
                  <p class="stat-label mb-0">Total Returns</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <!-- Borrowers Table -->
      <?php if(count($borrowers) === 0): ?>
        <div class="card border-0 bg-light">
          <div class="card-body text-center py-5">
            <i class="fs-1 text-muted">👥</i>
            <h4 class="text-muted mt-3">No borrowers found</h4>
            <p class="text-muted">No borrowers match your search criteria.</p>
            <a href="borrowers.php" class="btn btn-primary">View All Borrowers</a>
          </div>
        </div>
      <?php else: ?>
        <div class="card border-0">
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th scope="col" class="ps-4">Borrower</th>
                    <th scope="col" class="text-center">Total Transactions</th>
                    <th scope="col" class="text-center">Books Borrowed</th>
                    <th scope="col" class="text-center">Books Returned</th>
                    <th scope="col" class="text-center pe-4">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach($borrowers as $borrower): ?>
                    <tr>
                      <td class="ps-4">
                        <div class="d-flex align-items-center">
                          <div class="borrower-avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3">
                            <?= strtoupper(substr($borrower['borrower_name'], 0, 1)) ?>
                          </div>
                          <div>
                            <div class="fw-semibold"><?=htmlspecialchars($borrower['borrower_name'])?></div>
                            <div class="small text-muted">Library Member</div>
                          </div>
                        </div>
                      </td>
                      <td class="text-center">
                        <span class="badge bg-primary rounded-pill fs-6"><?=intval($borrower['transaction_count'])?></span>
                      </td>
                      <td class="text-center">
                        <span class="badge bg-success rounded-pill fs-6"><?=intval($borrower['borrowed_count'])?></span>
                      </td>
                      <td class="text-center">
                        <span class="badge bg-info rounded-pill fs-6"><?=intval($borrower['returned_count'])?></span>
                      </td>
                      <td class="text-center pe-4">
                        <div class="btn-group btn-group-sm" role="group">
                          <a class="btn btn-outline-primary" href="history.php?q=<?=urlencode($borrower['borrower_name'])?>">
                            <i class="me-1">📊</i> History
                          </a>
                          <a class="btn btn-outline-danger" 
                             href="borrowers.php?delete_borrower=<?=urlencode($borrower['borrower_name'])?>" 
                             onclick="return confirm('Are you sure you want to delete ALL transactions for <?=htmlspecialchars($borrower['borrower_name'])?>? This action cannot be undone.')"
                             title="Delete Borrower">
                            <i class="me-1">🗑️</i> Delete
                          </a>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <style>
    .borrower-avatar {
      width: 40px;
      height: 40px;
      font-weight: 600;
      font-size: 1.1rem;
    }
    
    .stat-item {
      padding: 10px;
    }
    
    .stat-number {
      font-size: 2.5rem;
      font-weight: 700;
      margin-bottom: 0.5rem;
    }
    
    .stat-label {
      font-size: 0.9rem;
      opacity: 0.9;
    }
    
    .table > :not(caption) > * > * {
      padding: 1rem 0.5rem;
    }
  </style>
</body>
</html>