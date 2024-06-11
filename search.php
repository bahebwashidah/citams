    <?php

//include 'menu.php';

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ams";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables for filters
$serialNumber = '';
$engraving = '';
$status = '';
$startDate = '';
$endDate = '';
$user = '';
$location = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $serialNumber = $_POST['serial_number'];
    $engraving = $_POST['engraving'];
    $status = $_POST['status'];
    $startDate = $_POST['start_date'];
    $endDate = $_POST['end_date'];
    $user = $_POST['user'];
    $location = $_POST['location'];
    
    $query = "SELECT a.*, u.First_Name, u.Last_Name, aa.location
              FROM assets a 
              LEFT JOIN asset_assignments aa ON a.Asset_ID = aa.asset_id
              LEFT JOIN users u ON aa.user_id = u.User_ID
              WHERE 1=1";
    
    // Use prepared statements to prevent SQL injection
    $params = [];
    $types = '';

    if (!empty($serialNumber)) {
        $query .= " AND a.Serial_Number LIKE ?";
        $params[] = "%$serialNumber%";
        $types .= 's';
    }
    if (!empty($engraving)) {
        $query .= " AND a.Engraving LIKE ?";
        $params[] = "%$engraving%";
        $types .= 's';
    }
    if (!empty($status)) {
        $query .= " AND a.Status LIKE ?";
        $params[] = "%$status%";
        $types .= 's';
    }
    if (!empty($startDate) && !empty($endDate)) {
        $query .= " AND a.Date_of_Delivery BETWEEN ? AND ?";
        $params[] = $startDate;
        $params[] = $endDate;
        $types .= 'ss';
    } elseif (!empty($startDate)) {
        $query .= " AND a.Date_of_Delivery >= ?";
        $params[] = $startDate;
        $types .= 's';
    } elseif (!empty($endDate)) {
        $query .= " AND a.Date_of_Delivery <= ?";
        $params[] = $endDate;
        $types .= 's';
    }
    if (!empty($user)) {
        $query .= " AND (u.First_Name LIKE ? OR u.Last_Name LIKE ?)";
        $params[] = "%$user%";
        $params[] = "%$user%";
        $types .= 'ss';
    }
    if (!empty($location)) {
        $query .= " AND aa.location LIKE ?";
        $params[] = "%$location%";
        $types .= 's';
    }

    $stmt = $conn->prepare($query);

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $query = "SELECT a.*, u.First_Name, u.Last_Name, aa.location
              FROM assets a 
              LEFT JOIN asset_assignments aa ON a.Asset_ID = aa.asset_id
              LEFT JOIN users u ON aa.user_id = u.User_ID";
    $result = $conn->query($query);
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Asset Search</title>
    <style>
        .container {
            background: rgba(255, 255, 255, 0.7);
        }
    </style>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container">
    <h2 class="mt-5">Search Assets</h2>
    <form method="POST" action="search.php">
        <div class="form-row">
            <div class="form-group col-md-2">
                <label for="serial_number">Serial Number</label>
                <input type="text" class="form-control" id="serial_number" name="serial_number" value="<?php echo htmlspecialchars($serialNumber); ?>">
            </div>
            <div class="form-group col-md-2">
                <label for="engraving">Engraving</label>
                <input type="text" class="form-control" id="engraving" name="engraving" value="<?php echo htmlspecialchars($engraving); ?>">
            </div>
            <div class="form-group col-md-2">
                <label for="status">Status</label>
                <select id="status" name="status" class="form-control">
                    <option value="">Choose...</option>
                    <option value="Available" <?php if ($status == 'Available') echo 'selected'; ?>>Available</option>
                    <option value="Assigned" <?php if ($status == 'Assigned') echo 'selected'; ?>>Assigned</option>
                    <option value="Maintenance" <?php if ($status == 'Maintenance') echo 'selected'; ?>>Maintenance</option>
                </select>
            </div>
            <div class="form-group col-md-2">
                <label for="start_date">Start Date</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>">
            </div>
            <div class="form-group col-md-2">
                <label for="end_date">End Date</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>">
            </div>
            <div class="form-group col-md-3">
                <label for="user">User</label>
                <input type="text" class="form-control" id="user" name="user" value="<?php echo htmlspecialchars($user); ?>">
            </div>
            <div class="form-group col-md-2">
                <label for="location">Location</label>
                <input type="text" class="form-control" id="location" name="location" value="<?php echo htmlspecialchars($location); ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-md-2">
                <button type="submit" class="btn btn-primary">Search</button>
                <button type="reset" class="btn btn-secondary" onclick="resetForm()">Reset</button>
            </div>
        </div>
    </form>
</div>

<div class="container">
    <h2 class="mt-5">Results</h2>
    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>Name</th>
                <th>Model</th>
                <th>Serial Number</th>
                <th>Engraving</th>
                <th>Date of Delivery</th>
                <th>Status</th>
                <th>User</th>
                <th>Location</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['Name'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['Model'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['Serial_Number'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['Engraving'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['Date_of_Delivery'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['Status'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars(($row['First_Name'] ?? '') . ' ' . ($row['Last_Name'] ?? '')); ?></td>
                        <td><?php echo htmlspecialchars($row['location'] ?? ''); ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8">No results found</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function resetForm() {
    document.getElementById("serial_number").value = "";
    document.getElementById("engraving").value = "";
    document.getElementById("status").value = "";
    document.getElementById("start_date").value = "";
    document.getElementById("end_date").value = "";
    document.getElementById("user").value = "";
    document.getElementById("location").value = "";
}
</script>
</body>
</html>
