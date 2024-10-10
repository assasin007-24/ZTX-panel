package main

import (
    "database/sql"
    "log"

    _ "github.com/mattn/go-sqlite3" // SQLite driver
)

var db *sql.DB

// Initialize database connection
func initDB() {
    var err error
    db, err = sql.Open("sqlite3", "../database/users.db")
    if err != nil {
        log.Fatal(err)
    }

    // Create users table if it doesn't exist
    sqlStmt := `CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE,
        password TEXT
    );`
    _, err = db.Exec(sqlStmt)
    if err != nil {
        log.Fatalf("Failed to create table: %v", err)
    }
}

// Close the database connection
func closeDB() {
    db.Close()
}

// Function to create a user
func createUser(username, password string) error {
    stmt, err := db.Prepare("INSERT INTO users (username, password) VALUES (?, ?)")
    if err != nil {
        return err
    }
    _, err = stmt.Exec(username, password)
    return err
}

// Function to get user by username
func getUser(username string) (string, error) {
    var password string
    err := db.QueryRow("SELECT password FROM users WHERE username = ?", username).Scan(&password)
    return password, err
}
