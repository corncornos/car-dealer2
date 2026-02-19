<?php
session_start();
require_once __DIR__ . '/config.php';
if (!isset($_SESSION['user'])) header('Location: login.php');
$pdo = getPDO();

$q = [];
// By default hide sold units from inventory listing. If a status is explicitly requested, honor it.
$sql = "SELECT * FROM vehicles WHERE 1";
if (empty($_GET['status'])) {
  $sql .= " AND status <> 'Sold'";
}
if (!empty($_GET['brand'])) { $sql .= " AND brand LIKE ?"; $q[] = '%'.$_GET['brand'].'%'; }
if (!empty($_GET['model'])) { $sql .= " AND model LIKE ?"; $q[] = '%'.$_GET['model'].'%'; }
if (!empty($_GET['year'])) { $sql .= " AND year = ?"; $q[] = $_GET['year']; }
if (!empty($_GET['status'])) { $sql .= " AND status = ?"; $q[] = $_GET['status']; }
// sort
$order = 'created_at DESC';
if (!empty($_GET['sort'])) {
    if ($_GET['sort'] === 'price_asc') $order = 'selling_price ASC';
    if ($_GET['sort'] === 'price_desc') $order = 'selling_price DESC';
    if ($_GET['sort'] === 'date_asc') $order = 'created_at ASC';
    if ($_GET['sort'] === 'date_desc') $order = 'created_at DESC';
}
$sql .= " ORDER BY " . $order;
$stmt = $pdo->prepare($sql);
$stmt->execute($q);
$vehicles = $stmt->fetchAll();
require 'header.php';
?>
<div class="d-flex justify-content-between mb-3">
  <h3>Inventory</h3>
  <div><a class="btn btn-primary" href="vehicle_add.php">Add Vehicle</a></div>
</div>
<form method="get" class="row g-2 mb-3">
  <div class="col-md-3"><input name="brand" value="<?php echo htmlspecialchars($_GET['brand'] ?? '') ?>" class="form-control" placeholder="Brand"></div>
  <div class="col-md-3"><input name="model" value="<?php echo htmlspecialchars($_GET['model'] ?? '') ?>" class="form-control" placeholder="Model"></div>
  <div class="col-md-2"><input name="year" value="<?php echo htmlspecialchars($_GET['year'] ?? '') ?>" class="form-control" placeholder="Year"></div>
  <div class="col-md-2">
    <select name="status" class="form-select"><option value="">Any Status</option><option <?php if(($_GET['status'] ?? '')=='Available') echo 'selected'; ?>>Available</option><option <?php if(($_GET['status'] ?? '')=='Sold') echo 'selected'; ?>>Sold</option><option <?php if(($_GET['status'] ?? '')=='Reserved') echo 'selected'; ?>>Reserved</option></select>
  </div>
  <div class="col-md-2">
    <select name="sort" class="form-select"><option value="">Sort</option><option value="price_asc">Price ↑</option><option value="price_desc">Price ↓</option><option value="date_asc">Date ↑</option><option value="date_desc">Date ↓</option></select>
  </div>
  <div class="col-md-12"><button class="btn btn-secondary btn-sm mt-2">Search / Filter</button></div>
</form>

<table class="table">
  <thead><tr><th>Image</th><th>Stock</th><th>Brand</th><th>Model</th><th>Year</th><th>Price</th><th>Status</th><th>Date Added</th><th>Actions</th></tr></thead>
  <tbody>
    <?php foreach($vehicles as $v): ?>
      <tr>
        <td>
          <?php if(!empty($v['image_path'])): ?>
            <img src="<?php echo htmlspecialchars($v['image_path']); ?>" alt="Vehicle image" style="width:250px;height:auto;object-fit:cover;border-radius:4px;">
          <?php else: ?>
            <span class="text-muted small">No image</span>
          <?php endif; ?>
        </td>
        <td><?php echo htmlspecialchars($v['stock_number']); ?></td>
        <td><?php echo htmlspecialchars($v['brand']); ?></td>
        <td><?php echo htmlspecialchars($v['model']); ?></td>
        <td><?php echo htmlspecialchars($v['year']); ?></td>
        <td>₱<?php echo number_format($v['selling_price'],2); ?></td>
        <td><?php echo htmlspecialchars($v['status']); ?></td>
        <td><?php echo htmlspecialchars($v['created_at']); ?></td>
        <td>
          <a class="btn btn-sm btn-primary" href="vehicle_edit.php?id=<?php echo $v['id']; ?>">Edit</a>
          <a class="btn btn-sm btn-danger" href="vehicle_delete.php?id=<?php echo $v['id']; ?>" onclick="return confirm('Delete?')">Delete</a>
          <?php if($v['status'] !== 'Sold'): ?>
            <a class="btn btn-sm btn-success" href="sale_mark.php?id=<?php echo $v['id']; ?>">Mark Sold</a>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php require 'footer.php'; ?>
