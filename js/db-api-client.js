/**
 * Database Hosting API Client
 * A simple JavaScript library for interacting with the remote database API
 * 
 * Usage:
 *   const client = new DBHostingClient('your-email@example.com');
 *   client.getDatabases().then(dbs => console.log(dbs));
 */

class DBHostingClient {
    constructor(token, baseURL = 'https://doom-dbhosting.xo.je/api/remote.php') {
        this.token = token;
        this.baseURL = baseURL;
    }

    /**
     * Get all databases for the user
     * @returns {Promise} Array of databases
     */
    async getDatabases() {
        return this._makeRequest('GET', { action: 'get' });
    }

    /**
     * Get a specific database with all its data
     * @param {number} dbId - Database ID
     * @returns {Promise} Database object with data
     */
    async getDatabase(dbId) {
        return this._makeRequest('GET', { action: 'get', db_id: dbId });
    }

    /**
     * Add a new row to a database
     * @param {number} dbId - Database ID
     * @param {Array} row - Array of values for the new row
     * @returns {Promise} Success response
     */
    async addRow(dbId, row) {
        const data = {
            token: this.token,
            db_id: dbId,
            action: 'add_row',
            row: row
        };
        return this._makeRequest('POST', data);
    }

    /**
     * Update entire database with new data
     * @param {number} dbId - Database ID
     * @param {Array} data - 2D array of data (rows and columns)
     * @param {Object} schema - Optional schema definition
     * @returns {Promise} Success response
     */
    async updateDatabase(dbId, data, schema = null) {
        const payload = {
            token: this.token,
            db_id: dbId,
            action: 'update',
            data: data
        };
        
        if (schema) {
            payload.schema = schema;
        }
        
        return this._makeRequest('POST', payload);
    }

    /**
     * Internal method to make API requests
     * @private
     */
    async _makeRequest(method, params) {
        try {
            let url = this.baseURL;
            let options = {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-API-Token': this.token
                }
            };

            if (method === 'GET') {
                // For GET requests, add parameters to URL
                const queryParams = new URLSearchParams(params);
                queryParams.append('token', this.token);
                url += '?' + queryParams.toString();
            } else {
                // For POST requests, add to body
                options.body = JSON.stringify({
                    token: this.token,
                    ...params
                });
            }

            const response = await fetch(url, options);
            const responseData = await response.json();

            if (!response.ok) {
                throw new Error(responseData.error || `HTTP ${response.status}`);
            }

            if (!responseData.success) {
                throw new Error(responseData.error || 'API request failed');
            }

            return responseData;
        } catch (error) {
            console.error('API Error:', error.message);
            throw error;
        }
    }
}

/**
 * Helper function to convert CSV string to 2D array
 * @param {string} csvString - CSV data as string
 * @returns {Array} 2D array
 */
function csvToArray(csvString) {
    const lines = csvString.trim().split('\n');
    return lines.map(line => {
        // Simple CSV parsing (handles basic cases)
        return line.split(',').map(cell => cell.trim().replace(/^["']|["']$/g, ''));
    });
}

/**
 * Helper function to convert 2D array to CSV string
 * @param {Array} data - 2D array
 * @returns {string} CSV string
 */
function arrayToCsv(data) {
    return data.map(row => 
        row.map(cell => {
            // Quote cells that contain commas or quotes
            if (typeof cell === 'string' && (cell.includes(',') || cell.includes('"'))) {
                return `"${cell.replace(/"/g, '""')}"`;
            }
            return cell;
        }).join(',')
    ).join('\n');
}

/**
 * Helper to display database records in an HTML table
 * @param {Array} data - 2D array from API response
 * @param {string} containerId - ID of container element
 */
function displayDatabaseTable(data, containerId) {
    if (!data || !Array.isArray(data) || data.length === 0) {
        document.getElementById(containerId).innerHTML = '<p>No data available</p>';
        return;
    }

    let html = '<table class="data-table"><thead><tr>';
    
    // Create header row
    data[0].forEach(cell => {
        html += `<th>${escapeHtml(cell)}</th>`;
    });
    html += '</tr></thead><tbody>';

    // Create data rows
    for (let i = 1; i < data.length; i++) {
        html += '<tr>';
        data[i].forEach(cell => {
            html += `<td>${escapeHtml(cell)}</td>`;
        });
        html += '</tr>';
    }

    html += '</tbody></table>';
    document.getElementById(containerId).innerHTML = html;
}

/**
 * Helper to escape HTML special characters
 * @private
 */
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return String(text).replace(/[&<>"']/g, m => map[m]);
}

// Export for use in different environments
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { DBHostingClient, csvToArray, arrayToCsv, displayDatabaseTable };
}
