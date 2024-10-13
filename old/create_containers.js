const fs = require('fs');
const sqlite3 = require('sqlite3').verbose();

// Database path
const dbPath = './database/containers.db';

// Create the database and containers table if it doesn't exist
const db = new sqlite3.Database(dbPath, (err) => {
    if (err) {
        console.error('Error opening database:', err.message);
    } else {
        console.log('Connected to the database.');

        db.run(`CREATE TABLE IF NOT EXISTS containers (
            container_id TEXT PRIMARY KEY,
            container_name TEXT,
            email TEXT,
            cpu INTEGER,
            ram INTEGER,
            storage INTEGER,
            port INTEGER,         -- Added port column
            image TEXT,
            directory TEXT        -- You may want to add this column as well
        )`, (err) => {
            if (err) {
                console.error('Error creating table:', err.message);
            } else {
                console.log('Containers table created or already exists.');
            }
        });

        db.close((err) => {
            if (err) {
                console.error('Error closing database:', err.message);
            } else {
                console.log('Database closed.');
            }
        });
    }
});
