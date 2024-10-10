<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZTX - Register</title>
    <link rel="stylesheet" href="register.css">
</head>
<body>

    <div class="container">
        <h1>Register</h1>
        <form action="javascript:void(0);" method="POST" id="registerForm">
            <input type="text" name="username" placeholder="Username" required><br>
            <input type="password" name="password" placeholder="Password" required><br>
            <button type="submit">Register</button>
        </form>
        <p><a href="login.php">Already have an account? Login here</a></p>
    </div>

    <footer>
        &copy 2024 <a href="https://google.com">ZTX Panel</a>
    </footer>

    <script>
        document.getElementById('registerForm').onsubmit = async function() {
            const formData = new FormData(this);
            const response = await fetch('http://localhost:2009/register', {
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
