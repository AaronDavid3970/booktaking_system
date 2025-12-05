<?php
require 'db.php';
require_login(); // Add this line to protect the page
?>

<?php
require 'db.php';
$id = $_GET['id'] ?? null;
if (!$id) { header('Location: index.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
$stmt->execute([$id]);
$b = $stmt->fetch();
if (!$b) { echo "Not found"; exit; }

$msg = $_GET['msg'] ?? '';
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title><?=htmlspecialchars($b['title'])?> - BookSystem</title>
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
      </li>
    </ul>
  </div>
</div>
</nav>

<div class="container mt-4">
  <!-- Back Button -->
  <div class="mb-3">
    <a href="index.php" class="btn btn-outline-secondary btn-sm">
      ← Back to All Books
    </a>
  </div>

  <!-- Alert Messages -->
  <?php if ($msg): ?>
    <?php 
    $alert_class = 'alert-info';
    if (strpos($msg, '❌') !== false || strpos($msg, 'Error') !== false || strpos($msg, 'No stock') !== false) {
      $alert_class = 'alert-danger';
    } elseif (strpos($msg, '✅') !== false || strpos($msg, 'successfully') !== false || strpos($msg, 'Thank you') !== false) {
      $alert_class = 'alert-success';
    }
    ?>
    <div class="alert <?= $alert_class ?> mb-4"><?=htmlspecialchars($msg)?></div>
  <?php endif; ?>

  <!-- Book Details Card -->
  <div class="main-card">
    <div class="row">
      <!-- Book Cover Column -->
      <div class="col-12 col-md-4 col-lg-3">
        <div class="book-cover-container text-center">
          <?php if(!empty($b['cover_image']) && file_exists(__DIR__.'/uploads/'.$b['cover_image'])): ?>
          <img src="uploads/<?=htmlspecialchars($b['cover_image'])?>" class="book-cover-large img-fluid rounded shadow" alt="<?=htmlspecialchars($b['title'])?>">
        <?php elseif(file_exists(__DIR__.'/uploads/default.png')): ?>
          <img src="uploads/default.png" class="book-cover-large img-fluid rounded shadow" alt="Default cover">
        <?php else: ?>
          <div class="book-cover-placeholder-large">
            <i class="fs-1">📚</i>
            <p class="small mt-2 mb-0 text-muted">No cover image</p>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Book Details Column -->
    <div class="col-12 col-md-8 col-lg-9">
      <div class="book-details">
        <!-- Title and Author -->
        <h1 class="book-title mb-2"><?=htmlspecialchars($b['title'])?></h1>
        <p class="book-author text-muted mb-4">by <?=htmlspecialchars($b['author'])?></p>

        <!-- Price and Stock Info -->
        <div class="book-meta mb-4">
          <div class="row g-3">
            <div class="col-auto">
              <div class="meta-card">
                <span class="meta-label">Price</span>
                <span class="meta-value price">$<?=number_format($b['price'], 2)?></span>
              </div>
            </div>
            <div class="col-auto">
              <div class="meta-card">
                <span class="meta-label">Stock</span>
                <span class="meta-value stock <?= (int)$b['stock'] <= 0 ? 'text-danger' : ((int)$b['stock'] <= 3 ? 'text-warning' : 'text-success') ?>">
                  <?=intval($b['stock'])?> available
                </span>
              </div>
            </div>
            <div class="col-auto">
              <div class="meta-card">
                <span class="meta-label">Status</span>
                <span class="meta-value status <?= (int)$b['stock'] <= 0 ? 'text-danger' : 'text-success' ?>">
                  <?= (int)$b['stock'] <= 0 ? 'Out of Stock' : 'Available' ?>
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Description -->
        <div class="book-description mb-5">
          <h5 class="section-title">About this book</h5>
          <div class="description-text">
            <?= nl2br(htmlspecialchars($b['description'] ?: 'No description available.')) ?>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="book-actions mb-4">
          <div class="row g-2">
            <div class="col-auto">
              <a href="edit.php?id=<?=$b['id']?>" class="btn btn-outline-primary">
                ✏️ Edit Book
              </a>
            </div>
            <div class="col-auto">
              <a href="history.php?book_id=<?=intval($b['id'])?>" class="btn btn-outline-info">
                📊 View Transactions
              </a>
            </div>
          </div>
        </div>

        <!-- Borrow/Return Section -->
        <div class="borrow-section">
          <div class="row">
            <!-- Borrow Form -->
            <div class="col-12 col-lg-6 mb-4">
              <div class="action-card">
                <h6 class="action-title">📥 Borrow a Copy</h6>
                <?php if ((int)$b['stock'] <= 0): ?>
                  <div class="alert alert-warning mb-0">
                    <i class="me-1">⚠️</i> No copies available to borrow.
                  </div>
                <?php else: ?>
                  <form method="post" action="take.php" class="mt-3">
                    <input type="hidden" name="id" value="<?=intval($b['id'])?>">
                    <div class="mb-3">
                      <label class="form-label small fw-semibold">Your Name</label>
                      <input class="form-control" type="text" name="borrower" required placeholder="Enter your name">
                    </div>
                    <button class="btn btn-success w-100" type="submit">
                      <i class="me-1">📖</i> Borrow This Book
                    </button>
                  </form>
                <?php endif; ?>
              </div>
            </div>

            <!-- Return Form -->
            <div class="col-12 col-lg-6 mb-4">
              <div class="action-card">
                <h6 class="action-title">📤 Return a Copy</h6>
                <form method="post" action="return.php" class="mt-3">
                  <input type="hidden" name="id" value="<?=intval($b['id'])?>">
                  <div class="mb-3">
                    <label class="form-label small fw-semibold">Your Name</label>
                    <input class="form-control" type="text" name="borrower" required placeholder="Enter your name">
                  </div>
                  <button class="btn btn-outline-secondary w-100" type="submit">
                    <i class="me-1">↩️</i> Return This Book
                  </button>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</div>

</body>
</html>