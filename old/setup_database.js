const sqlite3 = require('sqlite3').verbose();
const path = require('path');

// Path to the database file
const dbPath = path.join(__dirname, 'database', 'users.db');

// Create a new database or open the existing one
const db = new sqlite3.Database(dbPath, (err) => {
    if (err) {
        console.error('Error opening database ' + err.message);
    } else {
        // Create users table if it doesn't exist
        db.run(`CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            email TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            admin TEXT NOT NULL
        );`, (err) => {
            if (err) {
                console.error('Error creating table ' + err.message);
            } else {
                console.log('Users table created or already exists.');
            }
            db.close();
        });
    }
});
