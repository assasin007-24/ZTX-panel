package main

import (
    "encoding/json"
    "net/http"
    "golang.org/x/crypto/bcrypt"
)

// User structure for JSON requests
type User struct {
    Username string `json:"username"`
    Password string `json:"password"`
}

// Registration handler
func registerHandler(w http.ResponseWriter, r *http.Request) {
    if r.Method == http.MethodPost {
        var user User
        err := json.NewDecoder(r.Body).Decode(&user)
        if err != nil {
            http.Error(w, "Invalid input", http.StatusBadRequest)
            return
        }

        // Hash the password
        hashedPassword, err := bcrypt.GenerateFromPassword([]byte(user.Password), bcrypt.DefaultCost)
        if err != nil {
            http.Error(w, "Could not hash password", http.StatusInternalServerError)
            return
        }

        err = createUser(user.Username, string(hashedPassword))
        if err != nil {
            http.Error(w, "User already exists", http.StatusConflict)
            return
        }

        w.WriteHeader(http.StatusCreated)
        w.Write([]byte("User registered successfully"))
    } else {
        http.Error(w, "Invalid request method", http.StatusMethodNotAllowed)
    }
}

// Login handler
func loginHandler(w http.ResponseWriter, r *http.Request) {
    if r.Method == http.MethodPost {
        var user User
        err := json.NewDecoder(r.Body).Decode(&user)
        if err != nil {
            http.Error(w, "Invalid input", http.StatusBadRequest)
            return
        }

        // Get stored hashed password
        hashedPassword, err := getUser(user.Username)
        if err != nil {
            http.Error(w, "Invalid username or password", http.StatusUnauthorized)
            return
        }

        // Compare hashed password
        err = bcrypt.CompareHashAndPassword([]byte(hashedPassword), []byte(user.Password))
        if err != nil {
            http.Error(w, "Invalid username or password", http.StatusUnauthorized)
            return
        }

        w.Write([]byte("Login successful"))
    } else {
        http.Error(w, "Invalid request method", http.StatusMethodNotAllowed)
    }
}
