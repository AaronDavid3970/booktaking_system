<?php
require 'db.php';
require_login(); // Add this line to protect the page
?>

<?php
// Handle search and filters
$q = trim($_GET['q'] ?? '');
$author_filter = trim($_GET['author'] ?? '');
$availability_filter = $_GET['availability'] ?? '';

// Build query with filters
$params = [];
$sql = "SELECT * FROM books WHERE 1=1";

if ($q !== '') {
  $sql .= " AND (title LIKE :q OR author LIKE :q OR description LIKE :q)";
  $params[':q'] = "%$q%";
}

if ($author_filter !== '') {
  $sql .= " AND author LIKE :author";
  $params[':author'] = "%$author_filter%";
}

if ($availability_filter === 'available') {
  $sql .= " AND stock > 0";
} elseif ($availability_filter === 'unavailable') {
  $sql .= " AND stock <= 0";
}

$sql .= " ORDER BY title ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();

// Get unique authors for filter
$authors_stmt = $pdo->query("SELECT DISTINCT author FROM books WHERE author IS NOT NULL AND author != '' ORDER BY author");
$unique_authors = $authors_stmt->fetchAll();

// Get stats
$total_books = count($books);
$available_books = array_filter($books, function($book) {
  return $book['stock'] > 0;
});
$unavailable_books = array_filter($books, function($book) {
  return $book['stock'] <= 0;
});
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Library Catalog - BookSystem</title>
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
    </nav>

    <div class="container mt-4">
      <div class="main-card">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
          <div>
            <h2 class="mb-1">🔍 Library Catalog</h2>
            <p class="text-muted mb-0">Search and browse all available books in the library</p>
          </div>
          <a href="index.php" class="btn btn-outline-secondary btn-sm">← Back to Dashboard</a>
        </div>

        <!-- Search & Filters Card -->
        <div class="card border-0 bg-light mb-4">
          <div class="card-body">
            <h5 class="card-title mb-3">🔍 Search & Filter Books</h5>
            <form method="get" class="row g-3">
              <!-- Search Input -->
              <div class="col-12 col-md-4">
                <label class="form-label fw-semibold">Search Books</label>
                <input class="form-control" type="text" name="q" placeholder="Title, author, or description..." value="<?=htmlspecialchars($q)?>">
              </div>

              <!-- Author Filter -->
              <div class="col-12 col-md-3">
                <label class="form-label fw-semibold">Filter by Author</label>
                <select class="form-select" name="author">
                  <option value="">All Authors</option>
                  <?php foreach($unique_authors as $author): ?>
                    <option value="<?=htmlspecialchars($author['author'])?>" <?= $author_filter === $author['author'] ? 'selected' : '' ?>>
                      <?=htmlspecialchars($author['author'])?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <!-- Availability Filter -->
              <div class="col-12 col-md-3">
                <label class="form-label fw-semibold">Availability</label>
                <select class="form-select" name="availability">
                  <option value="">All Books</option>
                  <option value="available" <?= $availability_filter === 'available' ? 'selected' : '' ?>>Available Only</option>
                  <option value="unavailable" <?= $availability_filter === 'unavailable' ? 'selected' : '' ?>>Unavailable Only</option>
                </select>
              </div>

              <!-- Action Buttons -->
              <div class="col-12 col-md-2">
                <label class="form-label fw-semibold d-md-block d-none">&nbsp;</label>
                <div class="d-grid gap-2">
                  <button class="btn btn-primary" type="submit">
                    <i class="me-1">🔍</i> Search
                  </button>
                  <a class="btn btn-outline-secondary" href="books.php">
                    <i class="me-1">🔄</i> Reset
                  </a>
                </div>
              </div>
            </form>
          </div>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
          <div class="col-12 col-md-4 mb-3">
            <div class="card bg-primary text-white">
              <div class="card-body text-center">
                <i class="fs-1 mb-2">📚</i>
                <h3 class="stat-number"><?=count($books)?></h3>
                <p class="stat-label mb-0">Books Found</p>
              </div>
            </div>
          </div>
          <div class="col-12 col-md-4 mb-3">
            <div class="card bg-success text-white">
              <div class="card-body text-center">
                <i class="fs-1 mb-2">✅</i>
                <h3 class="stat-number"><?=count($available_books)?></h3>
                <p class="stat-label mb-0">Available</p>
              </div>
            </div>
          </div>
          <div class="col-12 col-md-4 mb-3">
            <div class="card bg-warning text-dark">
              <div class="card-body text-center">
                <i class="fs-1 mb-2">⏳</i>
                <h3 class="stat-number"><?=count($unavailable_books)?></h3>
                <p class="stat-label mb-0">Unavailable</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Active Filters Info -->
        <?php if($q !== '' || $author_filter !== '' || $availability_filter !== ''): ?>
          <div class="alert alert-info mb-4">
            <i class="me-2">📋</i> 
            <strong>Active Filters:</strong>
            <?php
            $filters = [];
            if ($q !== '') $filters[] = "Search: \"<strong>" . htmlspecialchars($q) . "</strong>\"";
            if ($author_filter !== '') $filters[] = "Author: \"<strong>" . htmlspecialchars($author_filter) . "</strong>\"";
            if ($availability_filter === 'available') $filters[] = "<strong>Available Books Only</strong>";
            if ($availability_filter === 'unavailable') $filters[] = "<strong>Unavailable Books Only</strong>";
            echo implode(' • ', $filters);
            ?>
            <a href="books.php" class="btn btn-sm btn-outline-info ms-2">Clear All</a>
          </div>
        <?php endif; ?>

        <!-- Books Grid -->
        <?php if(count($books) === 0): ?>
          <div class="card border-0 bg-light">
            <div class="card-body text-center py-5">
              <i class="fs-1 text-muted">🔍</i>
              <h4 class="text-muted mt-3">No books found</h4>
              <p class="text-muted">
                <?php if($q !== '' || $author_filter !== '' || $availability_filter !== ''): ?>
                  No books match your search criteria. Try adjusting your filters.
                <?php else: ?>
                  No books found in the library. Start by adding some books!
                <?php endif; ?>
              </p>
              <a href="add.php" class="btn btn-primary btn-lg">
                <i class="me-1">➕</i> Add New Book
              </a>
            </div>
          </div>
        <?php else: ?>
          <div class="row">
            <?php foreach($books as $b): ?>
              <div class="col-12 col-md-6 col-lg-4 mb-4">
                <div class="card book-card h-100">
                  <div class="card-body">
                    <!-- Book Cover -->
                    <div class="text-center mb-3">
                      <?php if(!empty($b['cover_image']) && file_exists(__DIR__.'/uploads/'.$b['cover_image'])): ?>
                      <img src="uploads/<?=htmlspecialchars($b['cover_image'])?>" class="book-cover-catalog img-fluid rounded shadow" alt="<?=htmlspecialchars($b['title'])?>">
                    <?php elseif(file_exists(__DIR__.'/uploads/default.png')): ?>
                      <img src="uploads/default.png" class="book-cover-catalog img-fluid rounded shadow" alt="Default cover">
                    <?php else: ?>
                      <div class="book-cover-placeholder-catalog mx-auto">
                        <i class="fs-1">📚</i>
                      </div>
                    <?php endif; ?>
                  </div>

                  <!-- Book Info -->
                  <h5 class="book-title-catalog mb-2"><?=htmlspecialchars($b['title'])?></h5>
                  <p class="book-author-catalog text-muted mb-3">
                    <i class="me-1">✍️</i> <?=htmlspecialchars($b['author'])?>
                  </p>

                  <!-- Description Preview -->
                  <p class="book-description-preview small text-muted mb-3">
                    <?php
                    $description = $b['description'] ?: 'No description available.';
                    echo strlen($description) > 120 ? substr($description, 0, 120) . '...' : $description;
                    ?>
                  </p>

                  <!-- Price & Stock -->
                  <div class="row g-2 mb-3">
                    <div class="col-6">
                      <div class="text-center">
                        <span class="fw-bold text-success">$<?=number_format($b['price'], 2)?></span>
                        <div class="small text-muted">Price</div>
                      </div>
                    </div>
                    <div class="col-6">
                      <div class="text-center">
                        <?php 
                        $stock = (int)$b['stock'];
                        $stock_class = $stock <= 0 ? 'danger' : ($stock <= 3 ? 'warning' : 'success');
                        $stock_icon = $stock <= 0 ? '🚫' : ($stock <= 3 ? '⚠️' : '✅');
                        ?>
                        <span class="badge bg-<?=$stock_class?>">
                          <i class="me-1"><?=$stock_icon?></i> <?=$stock?>
                        </span>
                        <div class="small text-muted">Stock</div>
                      </div>
                    </div>
                  </div>

                  <!-- Action Buttons -->
                  <div class="d-grid gap-2">
                    <a href="read.php?id=<?=$b['id']?>" class="btn btn-outline-primary btn-sm">
                      <i class="me-1">👁️</i> View Details
                    </a>
                    <?php if ($stock > 0): ?>
                      <small class="text-success text-center">
                        <i class="me-1">✅</i> Available for borrowing
                      </small>
                    <?php else: ?>
                      <small class="text-danger text-center">
                        <i class="me-1">❌</i> Currently unavailable
                      </small>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Results Info -->
        <div class="mt-4 text-center">
          <p class="text-muted">
            Showing <?=count($books)?> book<?=count($books) !== 1 ? 's' : ''?> in the library catalog
          </p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <style>
    .book-card {
      transition: transform 0.2s ease, box-shadow 0.2s ease;
      border: 1px solid #e9ecef;
    }

    .book-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .book-cover-catalog {
      max-height: 200px;
      width: auto;
    }

    .book-cover-placeholder-catalog {
      width: 120px;
      height: 160px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #f8f9fa;
      border: 1px solid #e9ecef;
      border-radius: 6px;
      color: #6c757d;
    }

    .book-title-catalog {
      font-size: 1.1rem;
      font-weight: 600;
      line-height: 1.3;
      height: 2.6em;
      overflow: hidden;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
    }

    .book-author-catalog {
      font-size: 0.9rem;
    }

    .book-description-preview {
      line-height: 1.4;
      height: 2.8em;
      overflow: hidden;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
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

    /* Dark mode adjustments */
    body.dark-mode .book-card {
      background: var(--card-bg);
      border-color: #374151;
    }

    body.dark-mode .book-cover-placeholder-catalog {
      background: #1f2937;
      border-color: #374151;
      color: #9ca3af;
    }
  </style>
</body>
</html>