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
$schema_path = "data/user_{$user_id}/database_{$db_id}_schema.json";
$data = [];
$schema = [];

// Load schema if exists
if (file_exists($schema_path)) {
    $schema = json_decode(file_get_contents($schema_path), true);
}

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
                    <button class="btn btn-secondary" onclick="openModal('columnsModal')">Columns</button>
                </div>
            </div>

            <div class="table-container">
                <?php if (count($data) > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <?php 
                                if (count($schema) > 0) {
                                    foreach ($schema as $col): ?>
                                        <th>
                                            <?php echo htmlspecialchars($col['name']); ?>
                                            <span class="type-badge"><?php echo htmlspecialchars($col['type']); ?></span>
                                        </th>
                                    <?php endforeach;
                                } elseif (count($data) > 0) {
                                    foreach ($data[0] as $header): ?>
                                        <th><?php echo htmlspecialchars($header ?? ''); ?></th>
                                    <?php endforeach;
                                } ?>
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

    <!-- Columns Modal -->
    <div id="columnsModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('columnsModal')">&times;</span>
            <h2>Manage Columns</h2>
            
            <div id="schemaList" class="schema-list">
                <!-- Populated by JavaScript -->
            </div>

            <hr style="margin: 20px 0;">
            <h3>Add New Column</h3>
            <form id="addColumnForm">
                <div class="form-group">
                    <label for="col-name">Column Name:</label>
                    <input type="text" id="col-name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="col-type">Data Type:</label>
                    <select id="col-type" name="type">
                        <option value="text">Text</option>
                        <option value="integer">Integer</option>
                        <option value="decimal">Decimal</option>
                        <option value="date">Date</option>
                        <option value="email">Email</option>
                        <option value="url">URL</option>
                    </select>
                </div>
                <button type="submit" class="btn">Add Column</button>
            </form>
        </div>
    </div>

    <script src="js/script.js"></script>
    <script>
        const headers = <?php echo json_encode(count($data) > 0 ? $data[0] : []); ?>;
        const schemaColumns = <?php echo json_encode($schema); ?>;

        // Generate form fields for adding rows
        function populateFormFields() {
            const container = document.getElementById('formFields');
            container.innerHTML = ''; // Clear existing fields
            
            // Use schema if available, otherwise use CSV headers
            let fields = [];
            if (schemaColumns && schemaColumns.length > 0) {
                fields = schemaColumns;
            } else if (headers && headers.length > 0) {
                fields = headers.map(h => {
                    if (typeof h === 'object') return h;
                    return {name: h, type: 'text'};
                });
            }
            
            if (fields.length === 0) {
                container.innerHTML = '<p>No columns defined. Create columns in the Columns tab first.</p>';
                return;
            }
            
            fields.forEach((field, idx) => {
                // Handle both object and string formats
                const fieldName = (typeof field === 'object' ? field.name : field) || `field_${idx}`;
                const fieldType = (typeof field === 'object' ? field.type : 'text') || 'text';
                
                const group = document.createElement('div');
                group.className = 'form-group';
                
                let inputHTML = '';
                const sanitizedName = fieldName.replace(/[^a-zA-Z0-9_-]/g, '_');
                
                if (fieldType === 'date') {
                    inputHTML = `<input type="date" name="${sanitizedName}" required>`;
                } else if (fieldType === 'email') {
                    inputHTML = `<input type="email" name="${sanitizedName}" required>`;
                } else if (fieldType === 'integer') {
                    inputHTML = `<input type="number" step="1" name="${sanitizedName}" required>`;
                } else if (fieldType === 'decimal') {
                    inputHTML = `<input type="number" step="0.01" name="${sanitizedName}" required>`;
                } else if (fieldType === 'url') {
                    inputHTML = `<input type="url" name="${sanitizedName}" required>`;
                } else {
                    inputHTML = `<input type="text" name="${sanitizedName}" required>`;
                }
                
                group.innerHTML = `
                    <label for="field-${sanitizedName}">${fieldName} <span class="type-hint">(${fieldType})</span>:</label>
                    ${inputHTML}
                `;
                container.appendChild(group);
            });
        }

        // Initialize form on page load
        populateFormFields();

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

        // Schema/Column Management
        const schema = <?php echo json_encode($schema); ?>;
        const dbId = <?php echo $db_id; ?>;

        function loadSchema() {
            fetch('api/manage_columns.php?db_id=' + dbId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displaySchema(data.schema);
                    }
                });
        }

        function displaySchema(schemaData) {
            const list = document.getElementById('schemaList');
            list.innerHTML = '';
            
            if (schemaData.length === 0) {
                list.innerHTML = '<p>No columns defined yet. Add one to get started!</p>';
                return;
            }

            const table = document.createElement('table');
            table.className = 'schema-table';
            table.innerHTML = '<tr><th>Column Name</th><th>Type</th><th>Action</th></tr>';
            
            schemaData.forEach((col, index) => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${col.name}</td>
                    <td><span class="type-badge">${col.type}</span></td>
                    <td><button class="btn btn-small btn-danger" onclick="deleteColumn(${index})">Delete</button></td>
                `;
                table.appendChild(row);
            });
            
            list.appendChild(table);
        }

        function deleteColumn(index) {
            if (confirm('Delete this column? This cannot be undone.')) {
                const formData = new FormData();
                formData.append('db_id', dbId);
                formData.append('action', 'delete_column');
                formData.append('index', index);

                fetch('api/manage_columns.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displaySchema(data.schema);
                    } else {
                        alert('Error: ' + data.error);
                    }
                });
            }
        }

        // Add column form
        document.getElementById('addColumnForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('db_id', dbId);
            formData.append('action', 'add_column');

            fetch('api/manage_columns.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.reset();
                    displaySchema(data.schema);
                } else {
                    alert('Error: ' + data.error);
                }
            });
        });

        // Load schema on page load
        loadSchema();
    </script>
</body>
</html>
