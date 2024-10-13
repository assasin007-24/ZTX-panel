const fs = require('fs');
const inquirer = require('inquirer').default; // Update here
const path = require('path');
const sqlite3 = require('sqlite3').verbose(); // Import SQLite3
const bcrypt = require('bcrypt'); // Import bcrypt

// Check if the database exists
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
        );`);
    }
});

async function createUser() {
    const questions = [
        {
            type: 'input',
            name: 'username',
            message: 'Enter username (letters only):',
            validate: (input) => /^[a-zA-Z]+$/.test(input) || 'Username must contain only letters!'
        },
        {
            type: 'input',
            name: 'email',
            message: 'Enter email:',
            validate: (input) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(input) || 'Enter a valid email!'
        },
        {
            type: 'password',
            name: 'password',
            message: 'Enter password (min 4, max 8 characters):',
            validate: (input) => {
                if (input.length < 4 || input.length > 8) {
                    return 'Password must be between 4 and 8 characters!';
                }
                return true;
            }
        },
        {
            type: 'list',
            name: 'admin',
            message: 'Is the user an admin?',
            choices: ['yes', 'no'],
            default: 'no'
        }
    ];

    const answers = await inquirer.prompt(questions);
    await saveUser(answers);
}

async function saveUser(user) {
    const { username, email, password, admin } = user;

    // Hash the password before saving
    const hashedPassword = await bcrypt.hash(password, 10); // 10 is the salt rounds

    return new Promise((resolve, reject) => {
        db.run(`INSERT INTO users (username, email, password, admin) VALUES (?, ?, ?, ?)`, 
            [username, email, hashedPassword, admin], 
            function(err) {
                if (err) {
                    console.error('Error inserting user:', err.message);
                    reject(err);
                } else {
                    console.log('User created successfully:', { username, email, admin });
                    resolve();
                }
            });
    });
}

// Execute the function if the script is run directly
if (require.main === module) {
    createUser()
        .then(() => {
            db.close(); // Close the database after saving the user
        })
        .catch((error) => {
            console.error('Error:', error);
            db.close(); // Ensure the database is closed even on error
        });
}
