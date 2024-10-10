<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZTX - Login</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>

    <div class="container">
        <h1>Login</h1>
        <form action="javascript:void(0);" method="POST" id="loginForm">
            <input type="text" name="username" placeholder="Username" required><br>
            <input type="password" name="password" placeholder="Password" required><br>
            <button type="submit">Login</button>
        </form>
        <p><a href="register.php">Don't have an account? Register here</a></p>
    </div>

    <footer>
        &copy 2024 <a href="https://google.com">ZTX Panel</a>
    </footer>

    <script>
        document.getElementById('loginForm').onsubmit = async function() {
            const formData = new FormData(this);
            const response = await fetch('http://localhost:2009/login', {
                method: 'POST',
                body: JSON.stringify(Object.fromEntries(formData)),
                headers: {
                    'Content-Type': 'application/json'
                }
            });

            const result = await response.text();
            alert(result);
        };
    </script>
</body>
</html>
