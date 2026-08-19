<!DOCTYPE html>
<html>
<head>
    <title>Debug Login</title>
</head>
<body>
    <h1>Debug Login Test</h1>
    <div id="result"></div>

    <script>
        async function testLogin() {
            const response = await fetch('/api/admin/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    email: 'admin@example.com',
                    password: 'admin123'
                }),
            });

            const text = await response.text();
            document.getElementById('result').innerHTML =
                '<pre>' + response.status + ': ' + text + '</pre>';
        }

        testLogin();
    </script>
</body>
</html>

