<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once 'config/db.php';

$user_id = $_SESSION['user_id'];
$db_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Verify the database belongs to the user
$verify_query = "SELECT id, name FROM `databases` WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($verify_query);
$stmt->bind_param("ii", $db_id, $user_id);
$stmt->execute();
$db_result = $stmt->get_result();

if ($db_result->num_rows == 0) {
    header('Location: dashboard.php');
    exit;
}

$database = $db_result->fetch_assoc();
$csv_path = "data/user_{$user_id}/database_{$db_id}.csv";
$data = [];

// Read CSV file if it exists
if (file_exists($csv_path)) {
    if (($handle = fopen($csv_path, 'r')) !== false) {
        while (($row = fgetcsv($handle)) !== false) {
            $data[] = $row;
        }
        fclose($handle);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($database['name']); ?> - Database</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">DB Hosting</div>
            <div class="nav-menu">
                <a href="dashboard.php" class="nav-link">Back to Dashboard</a>
                <a href="auth/logout.php" class="nav-link">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="database-view">
            <div class="view-header">
                <h1><?php echo htmlspecialchars($database['name']); ?></h1>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="openModal('uploadModal')">Upload CSV</button>
                    <button class="btn btn-primary" onclick="openModal('addRowModal')">Add Row</button>
                </div>
            </div>

            <div class="table-container">
                <?php if (count($data) > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <?php foreach ($data[0] as $header): ?>
                                    <th><?php echo htmlspecialchars($header ?? ''); ?></th>
                                <?php endforeach; ?>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for ($i = 1; $i < count($data); $i++): ?>
                                <tr>
                                    <?php foreach ($data[$i] as $cell): ?>
                                        <td><?php echo htmlspecialchars($cell ?? ''); ?></td>
                                    <?php endforeach; ?>
                                    <td>
                                        <button class="btn btn-small btn-danger" onclick="deleteRow(<?php echo $i; ?>)">Delete</button>
                                    </td>
                                </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="no-data">No data in this database. Upload a CSV file or add rows manually.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Upload CSV Modal -->
    <div id="uploadModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('uploadModal')">&times;</span>
            <h2>Upload CSV File</h2>
            <form id="uploadForm">
                <input type="hidden" name="db_id" value="<?php echo $db_id; ?>">
                <div class="form-group">
                    <label for="csv-file">Select CSV File:</label>
                    <input type="file" id="csv-file" name="file" accept=".csv" required>
                </div>
                <button type="submit" class="btn">Upload</button>
            </form>
        </div>
    </div>

    <!-- Add Row Modal -->
    <div id="addRowModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('addRowModal')">&times;</span>
            <h2>Add New Row</h2>
            <form id="addRowForm">
                <input type="hidden" name="db_id" value="<?php echo $db_id; ?>">
                <div id="formFields"></div>
                <button type="submit" class="btn">Add Row</button>
            </form>
        </div>
    </div>

    <script src="js/script.js"></script>
    <script>
        const headers = <?php echo json_encode(count($data) > 0 ? $data[0] : []); ?>;

        // Generate form fields for adding rows
        function populateFormFields() {
            const container = document.getElementById('formFields');
            headers.forEach(header => {
                const group = document.createElement('div');
                group.className = 'form-group';
                group.innerHTML = `
                    <label for="field-${header}">${header}:</label>
                    <input type="text" id="field-${header}" name="${header}" required>
                `;
                container.appendChild(group);
            });
        }

        if (headers.length > 0) {
            populateFormFields();
        }

        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('api/upload_csv.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            });
        });

        document.getElementById('addRowForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('api/add_row.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            });
        });

        function deleteRow(rowIndex) {
            if (confirm('Are you sure you want to delete this row?')) {
                fetch('api/delete_row.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        db_id: <?php echo $db_id; ?>,
                        row_index: rowIndex
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.error);
                    }
                });
            }
        }
    </script>
</body>
</html>
