<?php
require 'db.php';
require_login(); // Add this line to protect the page
?>

<?php
require 'db.php';

// handle search query
$q = trim($_GET['q'] ?? '');
if ($q !== '') {
  $sql = "SELECT * FROM books 
  WHERE title LIKE :q OR author LIKE :q OR description LIKE :q
  ORDER BY created_at DESC";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([':q' => "%$q%"]);
} else {
  $stmt = $pdo->query("SELECT * FROM books ORDER BY created_at DESC");
}
$books = $stmt->fetchAll();

// Check for success messages
$success_msg = $_GET['success'] ?? '';
$error_msg = $_GET['error'] ?? '';

// Get stats
$total_books = count($books);
$total_stock = array_sum(array_column($books, 'stock'));
$low_stock = array_filter($books, function($book) {
  return $book['stock'] > 0 && $book['stock'] <= 3;
});
$out_of_stock = array_filter($books, function($book) {
  return $book['stock'] <= 0;
});
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Book Taking System</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap 5 -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">

  <!-- Optional font -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="styles.css">
</head>
<body>
<!-- Navbar -->
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

      <ul class="navbar-nav mt-2 mt-lg-0 align-items-lg-center">
        <li class="nav-item me-lg-2 mb-2 mb-lg-0">
          <a class="nav-link" href="logout.php">
            <i class="me-1">🚪 Logout</i>
          </a>
        </li>
      </ul>
    </li>
  </ul>
</div>
</div>
</nav>

<div class="container mt-4">
  <div class="main-card">
    <!-- Welcome Alert -->
    <div class="alert alert-info mb-4">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <i class="me-2">👋</i> 
          Welcome back, <strong><?= htmlspecialchars(get_current_username()) ?></strong>! 
          You have <?=count($books)?> books in your library.
        </div>
        <small class="text-muted">
          Last login: <?= date('M j, Y g:i A', $_SESSION['user']['login_time'] ?? time()) ?>
        </small>
      </div>
    </div>

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-1">📚 Library Books</h2>
        <p class="text-muted mb-0">Manage your book collection and track borrowing activities</p>
      </div>
      <div class="text-end">
        <a href="add.php" class="btn btn-success">
          <i class="me-1">➕</i> Add New Book
        </a>
      </div>
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

    <!-- Stats Cards -->
    <div class="row mb-4">
      <div class="col-12 col-md-3 mb-3">
        <div class="card bg-primary text-white">
          <div class="card-body text-center">
            <i class="fs-1 mb-2">📚</i>
            <h3 class="stat-number"><?=$total_books?></h3>
            <p class="stat-label mb-0">Total Books</p>
          </div>
        </div>
      </div>
      <div class="col-12 col-md-3 mb-3">
        <div class="card bg-success text-white">
          <div class="card-body text-center">
            <i class="fs-1 mb-2">📦</i>
            <h3 class="stat-number"><?=$total_stock?></h3>
            <p class="stat-label mb-0">Total Stock</p>
          </div>
        </div>
      </div>
      <div class="col-12 col-md-3 mb-3">
        <div class="card bg-warning text-dark">
          <div class="card-body text-center">
            <i class="fs-1 mb-2">⚠️</i>
            <h3 class="stat-number"><?=count($low_stock)?></h3>
            <p class="stat-label mb-0">Low Stock</p>
          </div>
        </div>
      </div>
      <div class="col-12 col-md-3 mb-3">
        <div class="card bg-danger text-white">
          <div class="card-body text-center">
            <i class="fs-1 mb-2">🚫</i>
            <h3 class="stat-number"><?=count($out_of_stock)?></h3>
            <p class="stat-label mb-0">Out of Stock</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Search Info -->
    <?php if($q !== ''): ?>
      <div class="alert alert-info mb-4">
        <i class="me-2">🔍</i> 
        Showing results for: "<strong><?=htmlspecialchars($q)?></strong>"
        <a href="index.php" class="btn btn-sm btn-outline-info ms-2">Clear Search</a>
      </div>
    <?php endif; ?>

    <!-- Books Table -->
    <?php if(count($books) === 0): ?>
      <div class="card border-0 bg-light">
        <div class="card-body text-center py-5">
          <i class="fs-1 text-muted">📚</i>
          <h4 class="text-muted mt-3">No books found</h4>
          <p class="text-muted">
            <?php if($q !== ''): ?>
              No books match your search criteria.
            <?php else: ?>
              Your library is empty. Start by adding some books!
            <?php endif; ?>
          </p>
          <a href="add.php" class="btn btn-primary btn-lg">
            <i class="me-1">➕</i> Add Your First Book
          </a>
        </div>
      </div>
    <?php else: ?>
      <div class="card border-0">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th scope="col" class="ps-4">Book Details</th>
                  <th scope="col">Author</th>
                  <th scope="col" class="text-center">Price</th>
                  <th scope="col" class="text-center">Stock</th>
                  <th scope="col" class="text-center pe-4">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($books as $b): ?>
                  <tr>
                    <td class="ps-4">
                      <div class="d-flex align-items-center">
                        <?php if (!empty($b['cover_image']) && file_exists(__DIR__.'/uploads/'.$b['cover_image'])): ?>
                        <img src="uploads/<?=htmlspecialchars($b['cover_image'])?>" class="book-thumb me-3 rounded shadow" alt="cover">
                      <?php elseif(file_exists(__DIR__.'/uploads/default.png')): ?>
                        <img src="uploads/default.png" class="book-thumb me-3 rounded shadow" alt="default">
                      <?php else: ?>
                        <div class="book-thumb-placeholder me-3">
                          <i class="fs-4">📚</i>
                        </div>
                      <?php endif; ?>
                      <div>
                        <div class="fw-semibold book-title"><?=htmlspecialchars($b['title'])?></div>
                        <div class="small text-muted">
                          <?=strlen($b['description']) > 100 ? substr($b['description'], 0, 100) . '...' : $b['description']?>
                        </div>
                      </div>
                    </div>
                  </td>
                  <td>
                    <div class="fw-semibold"><?=htmlspecialchars($b['author'])?></div>
                    <div class="small text-muted">Author</div>
                  </td>
                  <td class="text-center">
                    <span class="fw-bold text-success">$<?=number_format($b['price'], 2)?></span>
                  </td>
                  <td class="text-center">
                    <?php 
                    $stock = (int)$b['stock'];
                    $stock_class = $stock <= 0 ? 'danger' : ($stock <= 3 ? 'warning' : 'success');
                    $stock_icon = $stock <= 0 ? '🚫' : ($stock <= 3 ? '⚠️' : '✅');
                    ?>
                    <span class="badge bg-<?=$stock_class?> rounded-pill px-3 py-2">
                      <i class="me-1"><?=$stock_icon?></i> <?=$stock?> available
                    </span>
                  </td>
                  <td class="text-center pe-4">
                    <div class="btn-group btn-group-sm" role="group">
                      <a class="btn btn-outline-primary" href="read.php?id=<?=$b['id']?>" title="View Details">
                        <i class="me-1">👁️</i> View
                      </a>
                      <a class="btn btn-outline-secondary" href="edit.php?id=<?=$b['id']?>" title="Edit Book">
                        <i class="me-1">✏️</i> Edit
                      </a>
                      <a class="btn btn-outline-danger" href="delete.php?id=<?=$b['id']?>" onclick="return confirm('Are you sure you want to delete this book?')" title="Delete Book">
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

    <!-- Results Info -->
    <div class="mt-3 text-center">
      <p class="text-muted small">
        Showing <?=count($books)?> book<?=count($books) !== 1 ? 's' : ''?> in your library
      </p>
    </div>
  <?php endif; ?>
</div>
</div>

<style>
  .book-thumb-placeholder {
    width: 60px;
    height: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    color: #6c757d;
  }

  .book-title {
    font-size: 1.1rem;
    font-weight: 600;
  }

  .stat-number {
    font-size: 2rem;
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

  .btn-group .btn {
    border-radius: 6px;
    margin: 0 2px;
  }
</style>
</body>
</html>