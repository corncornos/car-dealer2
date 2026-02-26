<?php
session_start();
require_once __DIR__ . '/config.php';
if (!isset($_SESSION['user'])) header('Location: login.php');

$pdo = getPDO();

// Stats
$total = $pdo->query('SELECT COUNT(*) FROM vehicles')->fetchColumn();
$available = $pdo->query("SELECT COUNT(*) FROM vehicles WHERE status='Available'")->fetchColumn();
$inventoryValue = $pdo->query('SELECT COALESCE(SUM(purchase_price),0) FROM vehicles')->fetchColumn();

require 'header.php';
?>
<body>
<div class="dashboard-container">
    <div class="dashboard-main-box">

        <!-- Stats Section -->
        <div class="dashboard-stats-container">
            <div class="dashboard-stat">
                <h5>Total Vehicles</h5>
                <h3><?php echo $total; ?></h3>
            </div>

            <div class="dashboard-stat">
                <h5>Available</h5>
                <h3><?php echo $available; ?></h3>
            </div>

            <div class="dashboard-stat">
                <h5>Inventory Value</h5>
                <h3>₱<?php echo number_format($inventoryValue,2); ?></h3>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="dashboard-actions">
            <button class="action-btn" onclick="openModal('reservedModal')">Reserve Unit</button>
            <button class="action-btn" onclick="openModal('viewingModal')">Viewing Schedule</button>
            <button class="action-btn" onclick="openModal('priorityModal')">Priority to Sell</button>
        </div>

    </div>
</div>

<h3>Reserved Units</h3>
<div class="reserved-units-container">
<?php
$stmt = $pdo->query("SELECT * FROM vehicles WHERE status='Reserved'");
$reserved = $stmt->fetchAll(PDO::FETCH_ASSOC);

if($reserved){
    foreach($reserved as $row){
        echo "<div class='reserved-card'>";
        echo "<strong>".htmlspecialchars($row['brand']." ".$row['model'])."</strong><br>";
        echo "Plate: ".htmlspecialchars($row['plate_number'])."<br>";
        echo "Price: ₱".number_format($row['selling_price'],2);
        echo "</div>";
    }
} else {
    echo "<p>No reserved units yet.</p>";
}
?>
</div>
<h3>Viewing Schedule</h3>
<div class="viewing-container">
<?php
$stmt = $pdo->query("SELECT * FROM vehicles 
                     WHERE status='Reserved' 
                     AND viewing_date IS NOT NULL");
$viewings = $stmt->fetchAll(PDO::FETCH_ASSOC);

if($viewings){
    foreach($viewings as $unit){
        echo "<div class='viewing-card'>";
        echo "<strong>".htmlspecialchars($unit['brand']." ".$unit['model'])."</strong><br>";
        echo "Plate: ".htmlspecialchars($unit['plate_number'])."<br>";
        echo "Viewing Date: ".$unit['viewing_date']."<br>";
        echo "Price: ₱".number_format($unit['selling_price'],2);
        echo "</div>";
    }
} else {
    echo "<p>No scheduled viewings yet.</p>";
}
?>
</div>

<!-- ===== Modals ===== -->

<!-- Unit Reserved Modal -->
<div id="reservedModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('reservedModal')">&times;</span>
        <h3>Unit Reserve</h3>

        <!-- ================= SEARCH FORM ================= -->
        <form method="GET">
            <input type="text" name="reserved_value" placeholder="Enter keyword...">
            <select name="reserved_field">
                <option value="brand">Brand Name</option>
                <option value="model">Model</option>
                <option value="plate_number">Plate Number</option>
            </select>
            <button type="submit" name="search_reserved" class="action-btn">Search</button>
        </form>

        <!-- ================= INVENTORY LIST ================= -->
<form method="POST">
    <div class="search-results-container">

        <?php
        $sql = "SELECT * FROM vehicles WHERE status='Available'";
        $params = [];

        if(isset($_GET['search_reserved']) && !empty($_GET['reserved_value'])){
            $value = trim($_GET['reserved_value']);
            $field = $_GET['reserved_field'];
            $allowed = ['brand','model','plate_number'];
            if(in_array($field, $allowed)){
                $sql .= " AND $field LIKE :search";
                $params[':search'] = "%$value%";
            }
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if($rows){
            foreach($rows as $row){
        ?>
                <div class="result-card">
                    <input type="checkbox" name="bulk_ids[]" value="<?php echo $row['id']; ?>">

                    <strong><?php echo htmlspecialchars($row['brand']." ".$row['model']); ?></strong><br>
                    Plate: <?php echo htmlspecialchars($row['plate_number']); ?><br>
                    Price: ₱<?php echo number_format($row['selling_price'],2); ?><br><br>

                    <!-- Single Reserve -->
                    <button type="submit" name="reserve_single" value="<?php echo $row['id']; ?>">
                        Reserve
                    </button>
                </div>
        <?php
            }
        } else {
            echo "<p>No available units found.</p>";
        }
        ?>

    </div>

    <br>
    <button type="submit" name="bulk_reserve">Reserve Selected</button>
</form>
<?php
// Single Reserve
if(isset($_POST['reserve_single'])){
    $id = intval($_POST['reserve_single']);
    $stmt = $pdo->prepare("UPDATE vehicles SET status='Reserved' WHERE id=?");
    $stmt->execute([$id]);
    header("Location: dashboard.php"); // redirect so modal closes and dashboard reloads
    exit();
}

// Bulk Reserve
if(isset($_POST['bulk_reserve']) && !empty($_POST['bulk_ids'])){
    $ids = array_map('intval', $_POST['bulk_ids']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("UPDATE vehicles SET status='Reserved' WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    header("Location: dashboard.php");
    exit();
}
?>

<!-- Viewing Schedule Modal -->
<div id="viewingModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('viewingModal')">&times;</span>
        <h3>Viewing Schedule</h3>

        <!-- Search Reserved Units -->
        <form method="GET">
            <input type="text" name="viewing_value" placeholder="Enter keyword..." required>
            <select name="viewing_field" required>
                <option value="brand">Brand Name</option>
                <option value="model">Model</option>
                <option value="plate_number">Plate Number</option>
            </select>
            <button type="submit" name="search_viewing" class="action-btn">Search</button>
        </form>

        <div class="search-results-container">
        <?php
        if(isset($_GET['search_viewing'])){
            $value = trim($_GET['viewing_value']);
            $field = $_GET['viewing_field'];
            $allowed = ['brand','model','plate_number'];

            if(in_array($field, $allowed)){
                $stmt = $pdo->prepare("SELECT * FROM vehicles 
                                       WHERE status='Reserved' 
                                       AND $field LIKE :search");
                $stmt->execute(['search' => "%$value%"]);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if($rows){
                    foreach($rows as $row){
        ?>
                        <div class="result-card">
						<strong><?php echo htmlspecialchars($row['brand']." ".$row['model']); ?></strong><br>
						Plate: <?php echo htmlspecialchars($row['plate_number']); ?><br>
						Price: ₱<?php echo number_format($row['selling_price'],2); ?><br><br>

						<form method="POST">
							<input type="hidden" name="vehicle_id" value="<?php echo $row['id']; ?>">

							<!-- THIS IS THE DATE YOU WANT TO SCHEDULE -->
							<input type="date" name="viewing_date" required 
								   min="<?php echo date('Y-m-d'); ?>">

							<button type="submit" name="schedule_viewing">
								Schedule Viewing
							</button>
						</form>
</div>
        <?php
                    }
                } else {
                    echo "<p>No reserved units found.</p>";
                }
            }
        }
        ?>
        </div>
    </div>
</div>
<?php
if(isset($_POST['schedule_viewing'])){
    $id = intval($_POST['vehicle_id']);
    $date = $_POST['viewing_date'];

    $stmt = $pdo->prepare("UPDATE vehicles 
                           SET viewing_date=? 
                           WHERE id=?");
    $stmt->execute([$date, $id]);

    header("Location: dashboard.php");
    exit();
}
?>

<!-- Priority to Sell Modal -->
<div id="priorityModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('priorityModal')">&times;</span>
        <h3>Priority to Sell Search</h3>
        <form method="GET">
            <input type="text" name="priority_value" placeholder="Enter keyword..." required>
            <select name="priority_field" required>
                <option value="brand">Brand Name</option>
                <option value="model">Model</option>
                <option value="plate_no">Plate Number</option>
            </select>
            <button type="submit" name="search_priority" class="action-btn">Search</button>
        </form>

        <div class="search-results-container">
            <?php
            if(isset($_GET['search_priority'])){
                $value = $_GET['priority_value'];
                $field = $_GET['priority_field'];
                $allowed = ['brand','model','plate_no'];

                if(in_array($field, $allowed)){
                    $stmt = $pdo->prepare("SELECT * FROM inventory WHERE status='Priority' AND $field LIKE :search");
                    $stmt->execute(['search' => "%$value%"]);
                    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if($rows){
                        foreach($rows as $row){
                            echo "<div class='result-card'>";
                            echo "<strong>".$row['brand']." ".$row['model']."</strong><br>";
                            echo "Plate: ".$row['plate_no']."<br>";
                            echo "Price: ₱".number_format($row['price'],2);
                            echo "</div>";
                        }
                    } else {
                        echo "<p>No priority units found.</p>";
                    }
                }
            }
            ?>
        </div>
    </div>
</div>
<!-- ================= JAVASCRIPT ================= -->
<script>
// ====== Modal Open/Close ======
function openModal(id){
    var modal = document.getElementById(id);
    if(modal) modal.style.display = "block";
}

function closeModal(id){
    var modal = document.getElementById(id);
    if(modal) modal.style.display = "none";
}

// Close modal when clicking outside
window.onclick = function(event){
    var modals = document.querySelectorAll(".modal");
    modals.forEach(function(modal){
        if(event.target === modal){
            modal.style.display = "none";
        }
    });
}

// ====== Toast Message ======
function showToast(message){
    let toast = document.createElement('div');
    toast.innerText = message;
    toast.style.position = 'fixed';
    toast.style.bottom = '20px';
    toast.style.left = '50%';
    toast.style.transform = 'translateX(-50%)';
    toast.style.background = '#4CAF50';
    toast.style.color = '#fff';
    toast.style.padding = '12px 20px';
    toast.style.borderRadius = '6px';
    toast.style.boxShadow = '0 2px 6px rgba(0,0,0,0.3)';
    toast.style.zIndex = 10000;
    toast.style.fontSize = '14px';
    document.body.appendChild(toast);

    setTimeout(() => toast.remove(), 1500);
}

// ====== Single Reserve ======
function reserveUnit(id){
    const card = document.getElementById('card-' + id);

    fetch('reserve_process.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=single&id=' + id
    })
    .then(() => {
        // Remove card immediately
        if(card) card.remove();

        // Show toast
        showToast('Unit Reserved Successfully!');

        // Close modal
        document.getElementById('reservedModal').style.display = 'none';

        // Redirect to dashboard
        setTimeout(() => window.location.href = 'dashboard.php', 1000);
    })
    .catch(err => console.error(err));
}

// ====== Bulk Reserve ======
function bulkReserve(){
    let checked = document.querySelectorAll('input[name="bulk_ids[]"]:checked');
    let ids = [];
    checked.forEach(item => ids.push(item.value));

    if(ids.length === 0){
        alert('Select at least one unit.');
        return;
    }

    // Remove selected cards immediately
    checked.forEach(item => {
        const card = item.closest('.result-card');
        if(card) card.remove();
    });

    fetch('reserve_process.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=bulk&ids[]=' + ids.join('&ids[]=')
    })
    .then(() => {
        showToast('Selected Units Reserved Successfully!');

        document.getElementById('reservedModal').style.display = 'none';

        setTimeout(() => window.location.href = 'dashboard.php', 1000);
    })
    .catch(err => console.error(err));
}
</script>
</body>