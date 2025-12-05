<?php
require 'db.php';
require_login(); // Add this line to protect the page
?>

<?php
require 'db.php';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = trim($_POST['title'] ?? '');
  $author = trim($_POST['author'] ?? '');
  $description = trim($_POST['description'] ?? '');
  $price = $_POST['price'] ?? 0;
  $stock = $_POST['stock'] ?? 0;

  if ($title === '') $errors[] = 'Title is required';
  if ($author === '') $errors[] = 'Author is required';

  $coverFilename = null;
  if (!empty($_FILES['cover']) && $_FILES['cover']['error'] !== UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['cover'];
    $allowed = ['image/jpeg','image/png','image/gif','image/webp'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
      $errors[] = 'Error uploading file';
    } elseif ($file['size'] > 2 * 1024 * 1024) {
      $errors[] = 'File too large (max 2MB)';
    } elseif (!in_array(mime_content_type($file['tmp_name']), $allowed)) {
      $errors[] = 'Only JPG, PNG, GIF, WEBP allowed';
    } else {
      $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
      $coverFilename = uniqid('cover_', true) . '.' . $ext;
      $dest = __DIR__ . '/uploads/' . $coverFilename;
      if (!move_uploaded_file($file['tmp_name'], $dest)) {
        $errors[] = 'Failed to save uploaded file';
      }
    }
  }

  if (empty($errors)) {
    $sql = "INSERT INTO books (title, author, description, price, stock, cover_image)
    VALUES (:title, :author, :description, :price, :stock, :cover)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
      ':title' => $title,
      ':author' => $author,
      ':description' => $description,
      ':price' => $price,
      ':stock' => $stock,
      ':cover' => $coverFilename
    ]);
    header('Location: index.php?success=' . urlencode('✅ Book added successfully!'));
    exit;
  }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Add Book - BookSystem</title>
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
        
        <ul class="navbar-nav mt-2 mt-lg-0 align-items-lg-center">
          <li class="nav-item me-lg-2 mb-2 mb-lg-0">
            <a class="btn btn-success w-100" href="add.php">
              <i class="me-1">➕</i> Add Book
            </a>
          </li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="me-1">👤</i> <?= htmlspecialchars(get_current_username()) ?>
            </a>
            <ul class="dropdown-menu" aria-labelledby="userDropdown">
              <li><span class="dropdown-item-text small text-muted">Signed in as <strong><?= htmlspecialchars(get_current_username()) ?></strong></span></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="logout.php"><i class="me-1">🚪</i> Logout</a></li>
            </ul>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <div class="container mt-4">
    <div class="main-card">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h3 class="mb-1">Add New Book</h3>
          <p class="text-muted mb-0">Fill in the book details below</p>
        </div>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">← Back to Books</a>
      </div>

      <?php if ($errors): ?>
        <div class="alert alert-danger mb-4">
          <strong>Please fix the following errors:</strong>
          <ul class="mb-0 mt-2">
            <?php foreach($errors as $e): ?>
              <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="post" enctype="multipart/form-data" class="row g-4">
        <!-- Book Basic Info -->
        <div class="col-12">
          <div class="card border-0 bg-light">
            <div class="card-body">
              <h5 class="card-title mb-3">📖 Basic Information</h5>
              <div class="row g-3">
                <div class="col-12 col-md-6">
                  <label class="form-label fw-semibold">Book Title *</label>
                  <input class="form-control form-control-lg" type="text" name="title" value="<?=htmlspecialchars($_POST['title'] ?? '')?>" placeholder="Enter book title" required>
                </div>
                <div class="col-12 col-md-6">
                  <label class="form-label fw-semibold">Author *</label>
                  <input class="form-control form-control-lg" type="text" name="author" value="<?=htmlspecialchars($_POST['author'] ?? '')?>" placeholder="Enter author name" required>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Book Description -->
        <div class="col-12">
          <div class="card border-0 bg-light">
            <div class="card-body">
              <h5 class="card-title mb-3">📝 Description</h5>
              <label class="form-label fw-semibold">Book Description</label>
              <textarea class="form-control" name="description" rows="5" placeholder="Enter book description..."><?=htmlspecialchars($_POST['description'] ?? '')?></textarea>
            </div>
          </div>
        </div>

        <!-- Pricing & Stock -->
        <div class="col-12 col-md-6">
          <div class="card border-0 bg-light h-100">
            <div class="card-body">
              <h5 class="card-title mb-3">💰 Pricing & Stock</h5>
              <div class="row g-3">
                <div class="col-12">
                  <label class="form-label fw-semibold">Price ($)</label>
                  <div class="input-group">
                    <span class="input-group-text">$</span>
                    <input class="form-control" type="number" step="0.01" name="price" value="<?=htmlspecialchars($_POST['price'] ?? '0.00')?>" placeholder="0.00">
                  </div>
                </div>
                <div class="col-12">
                  <label class="form-label fw-semibold">Stock Quantity</label>
                  <input class="form-control" type="number" name="stock" value="<?=htmlspecialchars($_POST['stock'] ?? '0')?>" placeholder="0">
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Cover Image -->
        <div class="col-12 col-md-6">
          <div class="card border-0 bg-light h-100">
            <div class="card-body">
              <h5 class="card-title mb-3">🖼️ Cover Image</h5>
              <label class="form-label fw-semibold">Upload Cover</label>
              <input class="form-control" type="file" name="cover" accept="image/*">
              <div class="form-text mt-2">
                <small>• Max file size: 2MB</small><br>
                <small>• Supported formats: JPG, PNG, GIF, WEBP</small><br>
                <small>• Optional - you can add a cover later</small>
              </div>
              
              <!-- Preview placeholder -->
              <div class="mt-3 text-center">
                <div class="book-cover-preview">
                  <div class="preview-placeholder">
                    <i class="fs-1">📚</i>
                    <p class="small mt-2 mb-0">Cover preview</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Submit Button -->
        <div class="col-12">
          <div class="card border-0 bg-transparent">
            <div class="card-body text-center p-0">
              <button class="btn btn-primary btn-lg px-5" type="submit">
                <i class="me-2">➕</i> Add Book
              </button>
              <a class="btn btn-outline-secondary btn-lg ms-3 px-5" href="index.php">Cancel</a>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>

  <script>
    // Simple image preview for cover upload
    document.querySelector('input[name="cover"]').addEventListener('change', function(e) {
      const file = e.target.files[0];
      const preview = document.querySelector('.book-cover-preview');
      
      if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
          preview.innerHTML = `<img src="${e.target.result}" class="img-fluid rounded" style="max-height: 200px;" alt="Cover preview">`;
        }
        reader.readAsDataURL(file);
      } else {
        preview.innerHTML = `
          <div class="preview-placeholder">
            <i class="fs-1">📚</i>
            <p class="small mt-2 mb-0">Cover preview</p>
          </div>
        `;
      }
    });
  </script>
</body>
</html>