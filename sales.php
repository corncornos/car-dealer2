<?php
session_start();
require_once __DIR__ . '/config.php';
if (!isset($_SESSION['user'])) header('Location: login.php');
$pdo = getPDO();
$stmt = $pdo->query('SELECT s.*, v.brand, v.model
                     FROM sales s 
                     JOIN vehicles v ON v.id = s.vehicle_id 
                     ORDER BY s.sale_date DESC');
$sales = $stmt->fetchAll();
require 'header.php';
?>
<h3 class="sales-title">Sales</h3>
<table class="table table-striped gold-black-table">
  <thead>
    <tr>
      <th>Vehicle</th>
      <th>Buyer</th>
      <th>Price</th>
      <th>Date</th>
      <th>Method</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach($sales as $s): ?>
      <tr>
        <td><?php echo htmlspecialchars($s['brand'].' '.$s['model']); ?></td>
        <td><?php echo htmlspecialchars($s['buyer_name']); ?></td>
        <td>₱<?php echo number_format($s['sale_price'],2); ?></td>
        <td><?php echo htmlspecialchars($s['sale_date']); ?></td>
        <td><?php echo htmlspecialchars($s['payment_method']); ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php require 'footer.php'; ?>
