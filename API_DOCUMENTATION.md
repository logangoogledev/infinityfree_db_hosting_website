# Remote Database API Documentation

## Overview
The Remote API allows users to access and sync their databases programmatically. Perfect for:
- Pulling databases into local applications
- Bulk uploading data
- Cross-platform synchronization
- Automated backups

## Authentication
Use your **email address** as your API token.

**Header Method (Recommended):**
```bash
curl -H "X-API-TOKEN: your-email@example.com" https://doom-dbhosting.xo.je/api/remote.php?action=get
```

**Query Parameter Method:**
```bash
curl https://doom-dbhosting.xo.je/api/remote.php?token=your-email@example.com&action=get
```

## Endpoints

### 1. Get All Databases
**Request:**
```bash
GET /api/remote.php?token=EMAIL&action=get
```

**Response:**
```json
{
  "success": true,
  "databases": [
    {
      "id": 1,
      "name": "users",
      "created_at": "2025-12-18 10:30:00"
    },
    {
      "id": 2,
      "name": "products",
      "created_at": "2025-12-18 11:00:00"
    }
  ],
  "count": 2
}
```

### 2. Get Specific Database
**Request:**
```bash
GET /api/remote.php?token=EMAIL&db_id=1&action=get
```

**Response:**
```json
{
  "success": true,
  "database": {
    "id": 1,
    "name": "users",
    "created_at": "2025-12-18 10:30:00"
  },
  "schema": [
    { "name": "username", "type": "text" },
    { "name": "age", "type": "integer" },
    { "name": "email", "type": "email" }
  ],
  "data": [
    ["username", "age", "email"],
    ["john_doe", "28", "john@example.com"],
    ["jane_smith", "32", "jane@example.com"]
  ],
  "row_count": 2
}
```

### 3. Update Database Data
Replace all data in a database with new data.

**Request:**
```bash
POST /api/remote.php?token=EMAIL&db_id=1&action=update

Content-Type: application/json

{
  "data": [
    ["username", "age", "email"],
    ["alice", "25", "alice@example.com"],
    ["bob", "30", "bob@example.com"]
  ],
  "schema": [
    { "name": "username", "type": "text" },
    { "name": "age", "type": "integer" },
    { "name": "email", "type": "email" }
  ]
}
```

**Response:**
```json
{
  "success": true,
  "message": "Database updated"
}
```

### 4. Add Single Row
**Request:**
```bash
POST /api/remote.php?token=EMAIL&db_id=1&action=add_row

Content-Type: application/json

{
  "row": ["charlie", "35", "charlie@example.com"]
}
```

**Response:**
```json
{
  "success": true,
  "message": "Row added"
}
```

## Python Example

```python
import requests
import json

# Configuration
EMAIL = "your-email@example.com"
API_URL = "https://doom-dbhosting.xo.je/api/remote.php"
DB_ID = 1

# Headers with token
headers = {
    "X-API-TOKEN": EMAIL,
    "Content-Type": "application/json"
}

# Get all databases
response = requests.get(f"{API_URL}?action=get", headers=headers)
databases = response.json()
print(f"Found {databases['count']} databases")

# Get specific database
response = requests.get(f"{API_URL}?db_id={DB_ID}&action=get", headers=headers)
database = response.json()
print(f"Database: {database['database']['name']}")
print(f"Rows: {database['row_count']}")
print(f"Schema: {database['schema']}")

# Update database
new_data = {
    "data": [
        ["name", "age"],
        ["User1", "25"],
        ["User2", "30"]
    ],
    "schema": [
        {"name": "name", "type": "text"},
        {"name": "age", "type": "integer"}
    ]
}

response = requests.post(
    f"{API_URL}?db_id={DB_ID}&action=update",
    headers=headers,
    json=new_data
)
print(response.json())

# Add row
new_row = {
    "row": ["User3", "28"]
}

response = requests.post(
    f"{API_URL}?db_id={DB_ID}&action=add_row",
    headers=headers,
    json=new_row
)
print(response.json())
```

## JavaScript Example

```javascript
const EMAIL = "your-email@example.com";
const API_URL = "https://doom-dbhosting.xo.je/api/remote.php";
const DB_ID = 1;

const headers = {
    "X-API-TOKEN": EMAIL,
    "Content-Type": "application/json"
};

// Get all databases
async function getDatabases() {
    const response = await fetch(
        `${API_URL}?action=get`,
        { headers }
    );
    return response.json();
}

// Get specific database
async function getDatabase(dbId) {
    const response = await fetch(
        `${API_URL}?db_id=${dbId}&action=get`,
        { headers }
    );
    return response.json();
}

// Update database
async function updateDatabase(dbId, data, schema) {
    const response = await fetch(
        `${API_URL}?db_id=${dbId}&action=update`,
        {
            method: "POST",
            headers,
            body: JSON.stringify({ data, schema })
        }
    );
    return response.json();
}

// Add row
async function addRow(dbId, row) {
    const response = await fetch(
        `${API_URL}?db_id=${dbId}&action=add_row`,
        {
            method: "POST",
            headers,
            body: JSON.stringify({ row })
        }
    );
    return response.json();
}

// Usage
async function main() {
    const dbs = await getDatabases();
    console.log(`Found ${dbs.count} databases`);
    
    const db = await getDatabase(DB_ID);
    console.log(`Database: ${db.database.name}`);
    console.log(`Rows: ${db.row_count}`);
    
    await addRow(DB_ID, ["NewUser", "26"]);
    console.log("Row added!");
}

main();
```

## Error Responses

### 401 Unauthorized
```json
{
  "success": false,
  "error": "Invalid token"
}
```

### 403 Forbidden
```json
{
  "success": false,
  "error": "Database not found or unauthorized"
}
```

### 400 Bad Request
```json
{
  "success": false,
  "error": "Invalid data format"
}
```

## Rate Limiting
No rate limiting currently implemented. Use responsibly.

## Security Notes
- Your email serves as your API token - keep it private
- Use HTTPS only (https://doom-dbhosting.xo.je)
- Don't expose tokens in public repositories
- Implement your own API key system if needed for multiple applications
