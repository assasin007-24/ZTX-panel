// create_api.js

const sqlite3 = require('sqlite3').verbose();
const path = require('path');
const fs = require('fs');

// Define the path to the API database
const dbPath = path.join(__dirname, 'database', 'api.db');

// Check if the database file exists
if (!fs.existsSync(dbPath)) {
    // Create a new database
    const db = new sqlite3.Database(dbPath, (err) => {
        if (err) {
            console.error('Error creating database:', err.message);
        } else {
            console.log('Database created successfully at:', dbPath);

            // Create the api_keys table
            db.run(`CREATE TABLE IF NOT EXISTS api_keys (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                api_key TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )`, (err) => {
                if (err) {
                    console.error('Error creating table:', err.message);
                } else {
                    console.log('Table api_keys created successfully.');
                }
                db.close();
            });
        }
    });
} else {
    console.log('Database already exists at:', dbPath);
}
