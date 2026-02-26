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
        <?php
          $img = htmlspecialchars($v['image_path'] ?? '');
          $brand = htmlspecialchars($v['brand']);
          $model = htmlspecialchars($v['model']);
          $year = htmlspecialchars($v['year']);
          $stock = htmlspecialchars($v['stock_number']);
          $status = htmlspecialchars($v['status']);
          $price = '₱' . number_format($v['selling_price'], 2);
          $date = htmlspecialchars($v['created_at']);
          // Build JSON data for modal (escape for HTML attribute)
          $data = htmlspecialchars(json_encode([
            'img'            => $v['image_path'] ?? '',
            'brand'          => $v['brand'],
            'model'          => $v['model'],
            'year'           => $v['year'],
            'stock'          => $v['stock_number'],
            'status'         => $v['status'],
            'price'          => $price,
            'date'           => $v['created_at'],
            'id'             => $v['id'],
            'vehicle_type'   => $v['vehicle_type'] ?? '',
            'purchase_price' => $v['purchase_price'] ? '₱' . number_format($v['purchase_price'], 2) : '',
            'color'          => $v['color'] ?? '',
            'transmission'   => $v['transmission'] ?? '',
            'fuel_type'      => $v['fuel_type'] ?? '',
            'mileage'        => $v['mileage'] ?? '',
            'notes'          => $v['notes'] ?? '',
            'engine_type'    => $v['engine_type'] ?? '',
            'plate_number'   => $v['plate_number'] ?? '',
            'body_type'      => $v['body_type'] ?? '',
          ]), ENT_QUOTES);
        ?>
        <tr onclick="openModal(this)" data-vehicle='<?php echo $data; ?>'>
          <td>
            <?php if(!empty($v['image_path'])): ?>
              <img src="<?php echo $img; ?>" class="vehicle-img">
            <?php else: ?>
              <span class="no-image">No Image</span>
            <?php endif; ?>
          </td>

          <td><?php echo $stock; ?></td>
          <td><?php echo $brand; ?></td>
          <td><?php echo $model; ?></td>
          <td><?php echo $year; ?></td>
          <td class="price"><?php echo $price; ?></td>

          <td>
            <span class="badge <?php echo strtolower($v['status']); ?>">
              <?php echo $status; ?>
            </span>
          </td>

          <td><?php echo $date; ?></td>

          <td class="actions" onclick="event.stopPropagation()">
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

<!-- Vehicle Detail Modal -->
<div class="modal-overlay" id="vehicleModal" onclick="closeModalOnOverlay(event)">
  <div class="modal-card">
    <button class="modal-close" onclick="closeModal()" title="Close">&times;</button>
    <img id="mImg" class="modal-img" src="" alt="Vehicle Image" style="display:none">
    <div id="mNoImg" style="display:none; text-align:center; padding:20px; color:#555; font-size:13px;">No Image Available</div>
    <div class="modal-title" id="mTitle"></div>
    <div class="modal-stock" id="mStock"></div>
    <div class="modal-grid">
      <div class="modal-field"><label>Stock Number</label><span id="mStockNumber"></span></div>
      <div class="modal-field"><label>Vehicle Type</label><span id="mVehicleType"></span></div>
      <div class="modal-field"><label>Brand</label><span id="mBrand"></span></div>
      <div class="modal-field"><label>Model</label><span id="mModel"></span></div>
      <div class="modal-field"><label>Year</label><span id="mYear"></span></div>
      <div class="modal-field"><label>Purchase Price</label><span id="mPurchasePrice"></span></div>
      <div class="modal-field"><label>Selling Price</label><span class="price" id="mPrice"></span></div>
      <div class="modal-field"><label>Status</label><span id="mStatus"></span></div>
      <div class="modal-field"><label>Color</label><span id="mColor"></span></div>
      <div class="modal-field"><label>Transmission</label><span id="mTransmission"></span></div>
      <div class="modal-field"><label>Fuel Type</label><span id="mFuelType"></span></div>
      <div class="modal-field"><label>Mileage</label><span id="mMileage"></span></div>
      <div class="modal-field"><label>Engine Type</label><span id="mEngineType"></span></div>
      <div class="modal-field"><label>Plate Number</label><span id="mPlateNumber"></span></div>
      <div class="modal-field"><label>Body Type</label><span id="mBodyType"></span></div>
      <div class="modal-field"><label>Date Added</label><span id="mDate"></span></div>
    </div>
    <div class="modal-notes-section">
      <div class="modal-field"><label>Notes</label><span id="mNotes"></span></div>
    </div>
    <div class="modal-actions" id="mActions"></div>
  </div>
</div>

<script>
function openModal(row) {
  const v = JSON.parse(row.getAttribute('data-vehicle'));
  const img = document.getElementById('mImg');
  const noImg = document.getElementById('mNoImg');
  if (v.img) {
    img.src = v.img;
    img.style.display = 'block';
    noImg.style.display = 'none';
  } else {
    img.style.display = 'none';
    noImg.style.display = 'block';
  }
  document.getElementById('mTitle').textContent = v.brand + ' ' + v.model;
  document.getElementById('mStock').textContent = 'Stock #: ' + v.stock;
  
  // Populate all fields
  document.getElementById('mStockNumber').textContent = v.stock || 'N/A';
  document.getElementById('mVehicleType').textContent = v.vehicle_type || 'N/A';
  document.getElementById('mBrand').textContent = v.brand || 'N/A';
  document.getElementById('mModel').textContent = v.model || 'N/A';
  document.getElementById('mYear').textContent = v.year || 'N/A';
  document.getElementById('mPurchasePrice').textContent = v.purchase_price || 'N/A';
  document.getElementById('mPrice').textContent = v.price || 'N/A';
  document.getElementById('mColor').textContent = v.color || 'N/A';
  document.getElementById('mTransmission').textContent = v.transmission || 'N/A';
  document.getElementById('mFuelType').textContent = v.fuel_type || 'N/A';
  document.getElementById('mMileage').textContent = v.mileage || 'N/A';
  document.getElementById('mEngineType').textContent = v.engine_type || 'N/A';
  document.getElementById('mPlateNumber').textContent = v.plate_number || 'N/A';
  document.getElementById('mBodyType').textContent = v.body_type || 'N/A';
  document.getElementById('mDate').textContent = v.date || 'N/A';
  document.getElementById('mNotes').textContent = v.notes || 'No notes available';

  const statusEl = document.getElementById('mStatus');
  statusEl.innerHTML = '<span class="badge ' + v.status.toLowerCase() + '">' + v.status + '</span>';

  const actions = document.getElementById('mActions');
  actions.innerHTML =
    '<a href="vehicle_edit.php?id=' + v.id + '" class="btn-action edit">Edit</a>' +
    '<a href="vehicle_delete.php?id=' + v.id + '" class="btn-action delete" onclick="return confirm(\'Delete?\')">Delete</a>' +
    (v.status !== 'Sold' ? '<a href="sale_mark.php?id=' + v.id + '" class="btn-action sold">Mark Sold</a>' : '');

  document.getElementById('vehicleModal').classList.add('active');
  document.body.style.overflow = 'hidden';
}

function closeModal() {
  document.getElementById('vehicleModal').classList.remove('active');
  document.body.style.overflow = '';
}

function closeModalOnOverlay(e) {
  if (e.target === document.getElementById('vehicleModal')) closeModal();
}

document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closeModal();
});
</script>

<?php require 'footer.php'; ?>