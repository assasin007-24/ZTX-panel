const sqlite3 = require('sqlite3').verbose();
const path = require('path');

// Path to the bridge database
const dbPath = path.join(__dirname, 'database', 'bridge.db');

// Create a new SQLite database
const db = new sqlite3.Database(dbPath, (err) => {
    if (err) {
        console.error('Error opening database: ' + err.message);
    } else {
        console.log('Connected to the bridge database.');
    }
});

// Create locations table
const createLocationsTable = `
CREATE TABLE IF NOT EXISTS locations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL
);`;

// Create bridges table
const createBridgesTable = `
CREATE TABLE IF NOT EXISTS bridges (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    fqdn TEXT NOT NULL,
    use_ssl INTEGER NOT NULL, -- 0 for HTTP, 1 for HTTPS
    memory INTEGER NOT NULL,
    storage INTEGER NOT NULL,
    cpu INTEGER NOT NULL,
    location TEXT NOT NULL
);`;

// Function to check if the 'status' column exists
const checkStatusColumnExists = (callback) => {
    db.all("PRAGMA table_info(bridges);", (err, rows) => {
        if (err) {
            console.error('Error checking table info: ' + err.message);
            callback(err);
            return;
        }
        // Check if the 'status' column exists in the rows
        const columnExists = rows && rows.some(col => col.name === 'status');
        callback(null, columnExists);
    });
};

// Execute table creation queries
db.serialize(() => {
    db.run(createLocationsTable, (err) => {
        if (err) {
            console.error('Error creating locations table: ' + err.message);
        } else {
            console.log('Locations table created successfully.');
        }
    });

    db.run(createBridgesTable, (err) => {
        if (err) {
            console.error('Error creating bridges table: ' + err.message);
        } else {
            console.log('Bridges table created successfully.');
        }
    });

    // Check if the 'status' column exists and add it if it doesn't
    checkStatusColumnExists((err, columnExists) => {
        if (err) return; // Handle the error if needed

        if (!columnExists) {
            const alterTableQuery = `ALTER TABLE bridges ADD COLUMN status INTEGER DEFAULT 0;`;
            db.run(alterTableQuery, (err) => {
                if (err) {
                    console.error('Error adding status column: ' + err.message);
                } else {
                    console.log('Status column added successfully to bridges table.');
                }
            });
        } else {
            console.log('Status column already exists in bridges table.');
        }

        // Close the database connection after all operations
        db.close((err) => {
            if (err) {
                console.error('Error closing the database: ' + err.message);
            } else {
                console.log('Database connection closed.');
            }
        });
    });
});
