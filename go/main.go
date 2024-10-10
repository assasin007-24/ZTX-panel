package main

import (
    "log"
    "net/http"
    "net/http/httputil"
    "net/url"
)

func main() {
    initDB()
    defer closeDB()

    http.HandleFunc("/register", registerHandler)
    http.HandleFunc("/login", loginHandler)

    // Serve PHP files
    phpURL, err := url.Parse("http://localhost:8000")
    if err != nil {
        log.Fatal(err)
    }
    http.Handle("/php/", httputil.NewSingleHostReverseProxy(phpURL))

    log.Println("Starting server on :2009...")
    err = http.ListenAndServe(":2009", nil)
    if err != nil {
        log.Fatal(err)
    }
}
