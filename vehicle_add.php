<?php
session_start();
require_once __DIR__ . '/config.php';
if (!isset($_SESSION['user'])) header('Location: login.php');
$pdo = getPDO();
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $imagePath = null;
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
                $imagePath = 'uploads/vehicles/' . $filename;
            }
        }
    }
    $data = [
        $_POST['stock_number'] ?? null,
        $_POST['vehicle_type'] ?? null,
        $_POST['brand'] ?? null,
        $_POST['model'] ?? null,
        $_POST['year'] ?? null,
        $_POST['color'] ?? null,
        $_POST['transmission'] ?? null,
        $_POST['fuel_type'] ?? null,
        $_POST['mileage'] ?? null,
        $_POST['purchase_price'] ?? null,
        $_POST['selling_price'] ?? null,
        $imagePath,
        $_POST['status'] ?? 'Available',
        $_POST['notes'] ?? null,
    ];
    // 🔎 CHECK DUPLICATE STOCK NUMBER
$check = $pdo->prepare(
    "SELECT id FROM vehicles WHERE stock_number = ?"
);
$check->execute([$_POST['stock_number'] ?? null]);

if ($check->rowCount() > 0) {
    echo "<script>
        alert('The existing stock number is already in use.');
        window.history.back();
    </script>";
    exit; // ⛔ VERY IMPORTANT
}

    $stmt = $pdo->prepare('INSERT INTO vehicles (stock_number, vehicle_type, brand, model, year, color, transmission, fuel_type, mileage, purchase_price, selling_price, image_path, status, notes) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $stmt->execute($data);
    $id = $pdo->lastInsertId();
    add_audit($pdo, 'Vehicle Added', json_encode(['id'=>$id,'stock_number'=>$_POST['stock_number'] ?? null,'brand'=>$_POST['brand'] ?? null,'model'=>$_POST['model'] ?? null]));
    header('Location: vehicles.php');
    exit;
}
require 'header.php';
?>
<h3>Add Vehicle</h3>
<form method="post" enctype="multipart/form-data">
  <div class="row">
    <div class="mb-3 col-md-4"><label>Stock Number</label><input name="stock_number" class="form-control"></div>
    <div class="mb-3 col-md-4"><label>Type</label><input name="vehicle_type" class="form-control" placeholder="CAR, Motor..."></div>
    <div class="mb-3 col-md-4"><label>Brand</label><input name="brand" class="form-control"></div>
    <div class="mb-3 col-md-4"><label>Model</label><input name="model" class="form-control"></div>
    <div class="mb-3 col-md-2"><label>Year</label><input name="year" class="form-control" type="number"></div>
    <div class="mb-3 col-md-2"><label>Color</label><input name="color" class="form-control"></div>
    <div class="mb-3 col-md-3"><label>Transmission</label><input name="transmission" class="form-control"></div>
    <div class="mb-3 col-md-3"><label>Fuel Type</label><input name="fuel_type" class="form-control"></div>
    <div class="mb-3 col-md-3"><label>Mileage</label><input name="mileage" class="form-control"></div>
    <div class="mb-3 col-md-3"><label>Purchase Price</label><input name="purchase_price" class="form-control" type="number" step="0.01"></div>
    <div class="mb-3 col-md-3"><label>Selling Price</label><input name="selling_price" class="form-control" type="number" step="0.01"></div>
    <div class="mb-3 col-md-4"><label>Image</label><input type="file" name="image" class="form-control" accept="image/*"></div>
    <div class="mb-3 col-md-3"><label>Status</label><select name="status" class="form-select"><option>Available</option><option>Reserved</option><option>Sold</option></select></div>
    <div class="mb-3 col-12"><label>Notes</label><textarea name="notes" class="form-control"></textarea></div>
  </div>
  <button class="btn btn-primary">Save</button>
</form>

<?php require 'footer.php'; ?>
