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
<div class="inventory-container">

  <!-- Header -->
  <div class="inventory-header">
    <div>
      <h2> Vehicle Inventory</h2>
      <p>Manage and monitor all available units</p>
    </div>
    <a href="vehicle_add.php" class="btn-add">+ Add Vehicle</a>
  </div>

  <!-- Filter Card -->
  <div class="filter-card">
    <form method="get" class="filter-form">
      <input name="brand" value="<?php echo htmlspecialchars($_GET['brand'] ?? '') ?>" placeholder="Brand">

      <input name="model" value="<?php echo htmlspecialchars($_GET['model'] ?? '') ?>" placeholder="Model">

      <input name="year" value="<?php echo htmlspecialchars($_GET['year'] ?? '') ?>" placeholder="Year">

      <select name="status">
        <option value="">Any Status</option>
        <option value="Available" <?php if(($_GET['status'] ?? '')=='Available') echo 'selected'; ?>>Available</option>
        <option value="Sold" <?php if(($_GET['status'] ?? '')=='Sold') echo 'selected'; ?>>Sold</option>
        <option value="Reserved" <?php if(($_GET['status'] ?? '')=='Reserved') echo 'selected'; ?>>Reserved</option>
      </select>

      <select name="sort">
        <option value="">Sort</option>
        <option value="price_asc">Price ↑</option>
        <option value="price_desc">Price ↓</option>
        <option value="date_asc">Date ↑</option>
        <option value="date_desc">Date ↓</option>
      </select>

      <button type="submit" class="btn-search">Search</button>
    </form>
  </div>

  <!-- Table -->
  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th>Image</th>
          <th>Stock</th>
          <th>Brand</th>
          <th>Model</th>
          <th>Year</th>
          <th>Price</th>
          <th>Status</th>
          <th>Date Added</th>
          <th>Actions</th>
        </tr>
      </thead>

      <tbody>
        <?php foreach($vehicles as $v): ?>
        <tr>
          <td>
            <?php if(!empty($v['image_path'])): ?>
              <img src="<?php echo htmlspecialchars($v['image_path']); ?>" class="vehicle-img">
            <?php else: ?>
              <span class="no-image">No Image</span>
            <?php endif; ?>
          </td>

          <td><?php echo htmlspecialchars($v['stock_number']); ?></td>
          <td><?php echo htmlspecialchars($v['brand']); ?></td>
          <td><?php echo htmlspecialchars($v['model']); ?></td>
          <td><?php echo htmlspecialchars($v['year']); ?></td>
          <td class="price">₱<?php echo number_format($v['selling_price'],2); ?></td>

          <td>
            <span class="badge <?php echo strtolower($v['status']); ?>">
              <?php echo htmlspecialchars($v['status']); ?>
            </span>
          </td>

          <td><?php echo htmlspecialchars($v['created_at']); ?></td>

          <td class="actions">
            <a href="vehicle_edit.php?id=<?php echo $v['id']; ?>" class="btn-action edit">Edit</a>
            <a href="vehicle_delete.php?id=<?php echo $v['id']; ?>" class="btn-action delete" onclick="return confirm('Delete?')">Delete</a>

            <?php if($v['status'] !== 'Sold'): ?>
              <a href="sale_mark.php?id=<?php echo $v['id']; ?>" class="btn-action sold">Mark Sold</a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>

    </table>
  </div>

</div>

<?php require 'footer.php'; ?>
