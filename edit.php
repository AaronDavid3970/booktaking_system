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
$book = $stmt->fetch();
if (!$book) { echo "Book not found"; exit; }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = trim($_POST['title'] ?? '');
  $author = trim($_POST['author'] ?? '');
  $description = trim($_POST['description'] ?? '');
  $price = $_POST['price'] ?? 0;
  $stock = $_POST['stock'] ?? 0;

  if ($title === '') $errors[] = 'Title required';
  if ($author === '') $errors[] = 'Author required';

  $coverFilename = $book['cover_image'];

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
      $newName = uniqid('cover_', true) . '.' . $ext;
      $dest = __DIR__ . '/uploads/' . $newName;
      if (!move_uploaded_file($file['tmp_name'], $dest)) {
        $errors[] = 'Failed to move uploaded file';
      } else {
        if (!empty($book['cover_image']) && file_exists(__DIR__.'/uploads/'.$book['cover_image'])) {
          @unlink(__DIR__.'/uploads/'.$book['cover_image']);
        }
        $coverFilename = $newName;
      }
    }
  }

  if (empty($errors)) {
    $sql = "UPDATE books 
    SET title=:title, author=:author, description=:description,
    price=:price, stock=:stock, cover_image=:cover
    WHERE id=:id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
      ':title'=>$title,
      ':author'=>$author,
      ':description'=>$description,
      ':price'=>$price,
      ':stock'=>$stock,
      ':cover'=>$coverFilename,
      ':id'=>$id
    ]);
    header('Location: index.php?success=' . urlencode('✅ Book updated successfully!'));
    exit;
  }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Edit - <?=htmlspecialchars($book['title'])?> - BookSystem</title>
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
  <div class="main-card">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h3 class="mb-1">Edit Book</h3>
        <p class="text-muted mb-0">Update the book details below</p>
      </div>
      <a href="index.php" class="btn btn-outline-secondary btn-sm">← Back to Books</a>
    </div>

    <?php if($errors): ?>
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
                <input class="form-control form-control-lg" type="text" name="title" value="<?=htmlspecialchars($_POST['title'] ?? $book['title'])?>" placeholder="Enter book title" required>
              </div>
              <div class="col-12 col-md-6">
                <label class="form-label fw-semibold">Author *</label>
                <input class="form-control form-control-lg" type="text" name="author" value="<?=htmlspecialchars($_POST['author'] ?? $book['author'])?>" placeholder="Enter author name" required>
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
            <textarea class="form-control" name="description" rows="5" placeholder="Enter book description..."><?=htmlspecialchars($_POST['description'] ?? $book['description'])?></textarea>
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
                  <input class="form-control" type="number" step="0.01" name="price" value="<?=htmlspecialchars($_POST['price'] ?? $book['price'])?>" placeholder="0.00">
                </div>
              </div>
              <div class="col-12">
                <label class="form-label fw-semibold">Stock Quantity</label>
                <input class="form-control" type="number" name="stock" value="<?=htmlspecialchars($_POST['stock'] ?? $book['stock'])?>" placeholder="0">
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
            
            <!-- Current Cover -->
            <div class="mb-3">
              <label class="form-label fw-semibold">Current Cover</label>
              <div class="text-center">
                <?php if (!empty($book['cover_image']) && file_exists(__DIR__.'/uploads/'.$book['cover_image'])): ?>
                <img src="uploads/<?=htmlspecialchars($book['cover_image'])?>" class="img-fluid rounded shadow" style="max-height: 200px;" alt="Current cover">
                <p class="small text-muted mt-2">Current cover image</p>
              <?php elseif(file_exists(__DIR__.'/uploads/default.png')): ?>
                <img src="uploads/default.png" class="img-fluid rounded shadow" style="max-height: 200px;" alt="Default cover">
                <p class="small text-muted mt-2">Default cover image</p>
              <?php else: ?>
                <div class="book-cover-preview">
                  <div class="preview-placeholder">
                    <i class="fs-1">📚</i>
                    <p class="small mt-2 mb-0">No cover image</p>
                  </div>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Replace Cover -->
          <label class="form-label fw-semibold">Replace Cover</label>
          <input class="form-control" type="file" name="cover" accept="image/*">
          <div class="form-text mt-2">
            <small>• Max file size: 2MB</small><br>
            <small>• Supported formats: JPG, PNG, GIF, WEBP</small><br>
            <small>• Leave empty to keep current cover</small>
          </div>
        </div>
      </div>
    </div>

    <!-- Submit Button -->
    <div class="col-12">
      <div class="card border-0 bg-transparent">
        <div class="card-body text-center p-0">
          <button class="btn btn-primary btn-lg px-5" type="submit">
            <i class="me-2">💾</i> Update Book
          </button>
          <a class="btn btn-outline-secondary btn-lg ms-3 px-5" href="read.php?id=<?=$id?>">Cancel</a>
        </div>
      </div>
    </div>
  </form>
</div>
</div>
</body>
</html>