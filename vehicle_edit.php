<?php
session_start();
require_once __DIR__ . '/config.php';
if (!isset($_SESSION['user'])) header('Location: login.php');
$pdo = getPDO();
$id = $_GET['id'] ?? null;
if (!$id) { header('Location: vehicles.php'); exit; }
$stmt = $pdo->prepare('SELECT * FROM vehicles WHERE id = ?');
$stmt->execute([$id]);
$v = $stmt->fetch();
if (!$v) { header('Location: vehicles.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $imagePath = $v['image_path'] ?? null;
    if (!empty($_FILES['image']['name'])) {
        $uploadDir = __DIR__ . '/uploads/vehicles';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $ext = strtolower($ext);
        $allowed = ['jpg','jpeg','png','gif','webp'];
        if (in_array($ext, $allowed)) {
            $filename = 'veh_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $target = $uploadDir . '/' . $filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                // optionally remove old file
                if (!empty($imagePath) && is_file(__DIR__ . '/' . $imagePath)) {
                    @unlink(__DIR__ . '/' . $imagePath);
                }
                $imagePath = 'uploads/vehicles/' . $filename;
            }
        }
    }
    $after = [
        'stock_number' => $_POST['stock_number'] ?? null,
        'vehicle_type' => $_POST['vehicle_type'] ?? null,
        'brand' => $_POST['brand'] ?? null,
        'model' => $_POST['model'] ?? null,
        'year' => $_POST['year'] ?? null,
        'color' => $_POST['color'] ?? null,
        'transmission' => $_POST['transmission'] ?? null,
        'fuel_type' => $_POST['fuel_type'] ?? null,
        'mileage' => $_POST['mileage'] ?? null,
        'purchase_price' => $_POST['purchase_price'] ?? null,
        'selling_price' => $_POST['selling_price'] ?? null,
        'image_path' => $imagePath,
        'status' => $_POST['status'] ?? 'Available',
        'notes' => $_POST['notes'] ?? null,
    ];
    $data = [
        $after['stock_number'],
        $after['vehicle_type'],
        $after['brand'],
        $after['model'],
        $after['year'],
        $after['color'],
        $after['transmission'],
        $after['fuel_type'],
        $after['mileage'],
        $after['purchase_price'],
        $after['selling_price'],
        $after['image_path'],
        $after['status'],
        $after['notes'],
        $id
    ];
    // 🔎 CHECK DUPLICATE STOCK NUMBER (EXCEPT CURRENT VEHICLE)
$check = $pdo->prepare(
    "SELECT id FROM vehicles 
     WHERE stock_number = ? AND id != ?"
);
$check->execute([$_POST['stock_number'], $id]);

if ($check->rowCount() > 0) {
    echo "<script>
        alert('The existing stock number is already in use.');
        window.history.back();
    </script>";
    exit; // ⛔ STOP execution (VERY IMPORTANT)
}
    $stmt = $pdo->prepare('UPDATE vehicles SET stock_number=?, vehicle_type=?, brand=?, model=?, year=?, color=?, transmission=?, fuel_type=?, mileage=?, purchase_price=?, selling_price=?, image_path=?, status=?, notes=? WHERE id=?');
    $stmt->execute($data);
    add_audit($pdo, 'Vehicle Updated', json_encode(['id'=>$id,'before'=>$v,'after'=>$after]));
    header('Location: vehicles.php'); exit;
}
require 'header.php';
?>
<h3>Edit Vehicle</h3>
<form method="post" enctype="multipart/form-data">
  <div class="row">
    <div class="mb-3 col-md-4"><label>Stock Number</label><input name="stock_number" value="<?php echo htmlspecialchars($v['stock_number']); ?>" class="form-control"></div>
    <div class="mb-3 col-md-4"><label>Type</label><input name="vehicle_type" value="<?php echo htmlspecialchars($v['vehicle_type']); ?>" class="form-control"></div>
    <div class="mb-3 col-md-4"><label>Brand</label><input name="brand" value="<?php echo htmlspecialchars($v['brand']); ?>" class="form-control"></div>
    <div class="mb-3 col-md-4"><label>Model</label><input name="model" value="<?php echo htmlspecialchars($v['model']); ?>" class="form-control"></div>
    <div class="mb-3 col-md-2"><label>Year</label><input name="year" value="<?php echo htmlspecialchars($v['year']); ?>" class="form-control" type="number"></div>
    <div class="mb-3 col-md-2"><label>Color</label><input name="color" value="<?php echo htmlspecialchars($v['color']); ?>" class="form-control"></div>
    <div class="mb-3 col-md-3"><label>Transmission</label><input name="transmission" value="<?php echo htmlspecialchars($v['transmission']); ?>" class="form-control"></div>
    <div class="mb-3 col-md-3"><label>Fuel Type</label><input name="fuel_type" value="<?php echo htmlspecialchars($v['fuel_type']); ?>" class="form-control"></div>
    <div class="mb-3 col-md-3"><label>Mileage</label><input name="mileage" value="<?php echo htmlspecialchars($v['mileage']); ?>" class="form-control"></div>
    <div class="mb-3 col-md-3"><label>Purchase Price</label><input name="purchase_price" value="<?php echo htmlspecialchars($v['purchase_price']); ?>" class="form-control" type="number" step="0.01"></div>
    <div class="mb-3 col-md-3"><label>Selling Price</label><input name="selling_price" value="<?php echo htmlspecialchars($v['selling_price']); ?>" class="form-control" type="number" step="0.01"></div>
    <div class="mb-3 col-md-3"><label>Status</label><select name="status" class="form-select"><option <?php if($v['status']=='Available') echo 'selected'; ?>>Available</option><option <?php if($v['status']=='Reserved') echo 'selected'; ?>>Reserved</option><option <?php if($v['status']=='Sold') echo 'selected'; ?>>Sold</option></select></div>
    <div class="mb-3 col-md-4">
      <label>Image</label>
      <?php if(!empty($v['image_path'])): ?>
        <div class="mb-2">
          <img src="<?php echo htmlspecialchars($v['image_path']); ?>" alt="Vehicle image" style="max-width:150px;max-height:120px;object-fit:cover;border-radius:4px;">
        </div>
      <?php endif; ?>
      <input type="file" name="image" class="form-control" accept="image/*">
    </div>
    <div class="mb-3 col-12"><label>Notes</label><textarea name="notes" class="form-control"><?php echo htmlspecialchars($v['notes']); ?></textarea></div>
  </div>
  <button class="btn btn-primary">Update</button>
</form>

<?php require 'footer.php'; ?>
