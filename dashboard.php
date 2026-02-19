<?php
session_start();
require_once __DIR__ . '/config.php';
if (!isset($_SESSION['user'])) header('Location: login.php');
$pdo = getPDO();
// Stats
$total = $pdo->query('SELECT COUNT(*) FROM vehicles')->fetchColumn();
$available = $pdo->query("SELECT COUNT(*) FROM vehicles WHERE status='Available'")->fetchColumn();
$sold = $pdo->query("SELECT COUNT(*) FROM vehicles WHERE status='Sold'")->fetchColumn();
$inventoryValue = $pdo->query('SELECT COALESCE(SUM(purchase_price),0) FROM vehicles')->fetchColumn();
$totalSales = $pdo->query('SELECT COALESCE(SUM(sale_price),0) FROM sales')->fetchColumn();
require 'header.php';
?>
<table class="dashboard-table">
  <tr>
    <td class="stat-primary">
      <h5>Total Vehicles</h5>
      <h3><?php echo $total; ?></h3>
    </td>
    <td class="stat-success">
      <h5>Available</h5>
      <h3><?php echo $available; ?></h3>
    </td>
    <td class="stat-secondary">
      <h5>Sold</h5>
      <h3><?php echo $sold; ?></h3>
    </td>
    <td class="stat-dark">
      <h5>Inventory Value</h5>
      <h3>₱<?php echo number_format($inventoryValue,2); ?></h3>
    </td>
  </tr>
</table>

<table class="dashboard-table">
  <tr>
    <td class="stat-primary" colspan="2">
      <h5>Total Sales</h5>
      <h3>₱<?php echo number_format($totalSales,2); ?></h3>
    </td>
  </tr>
</table>

<?php require 'footer.php'; ?>
