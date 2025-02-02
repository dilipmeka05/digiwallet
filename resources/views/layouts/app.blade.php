<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Wallet</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <a class="navbar-brand" href="{{ url('/') }}">Digital Wallet</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item" id="loginLink">
                    <a class="nav-link" href="{{ route('login') }}">Login</a>
                </li>
                <li class="nav-item" id="registerLink">
                    <a class="nav-link" href="{{ route('register') }}">Register</a>
                </li>

                <li class="nav-item" id="dashboardLink" style="display: none;">
                    <a class="nav-link" href="{{ route('wallet.dashboard') }}">Dashboard</a>
                </li>
                <li class="nav-item" id="logoutLink" style="display: none;">
                    <button class="btn btn-link nav-link" id="logoutButton">Logout</button>
                </li>
            </ul>
        </div>
    </nav>
    
    <div class="container mt-4">
        @yield('content')
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/app.js') }}"></script>

    <script>
        const token = localStorage.getItem('token');
        const currentPath = window.location.pathname;

        if (token) {
            if (currentPath !== '/wallet/dashboard') {
                window.location.href = '/wallet/dashboard';
            }
            document.getElementById('loginLink').style.display = 'none';
            document.getElementById('registerLink').style.display = 'none';
            document.getElementById('dashboardLink').style.display = 'block';
            document.getElementById('logoutLink').style.display = 'block';
        } else {
            if (currentPath !== '/login' && currentPath !== '/register') {
                window.location.href = '/login';
            }
            document.getElementById('loginLink').style.display = 'block';
            document.getElementById('registerLink').style.display = 'block';
            document.getElementById('dashboardLink').style.display = 'none';
            document.getElementById('logoutLink').style.display = 'none';
        }

        document.getElementById('logoutButton')?.addEventListener('click', function () {
            localStorage.removeItem('token'); 
            
            document.getElementById('loginLink').style.display = 'block';
            document.getElementById('registerLink').style.display = 'block';
            document.getElementById('dashboardLink').style.display = 'none';
            document.getElementById('logoutLink').style.display = 'none';

            window.location.href = '/login';  
        });
    </script>
</body>
</html>
