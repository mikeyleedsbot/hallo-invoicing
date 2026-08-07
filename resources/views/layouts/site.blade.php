<!DOCTYPE html>
<html lang="nl" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@hasSection('title')@yield('title') | Hallo Invoicing @else Hallo Invoicing - Moderne Facturatie Software @endif</title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3csvg%20width='32'%20height='32'%20viewBox='0%200%2080%2080'%20fill='none'%20xmlns='http://www.w3.org/2000/svg'%3e%3cpath%20d='M15.6,75c6.1-9.7,10.8-20.6,14.2-32.8,3.4-12.2,5.2-24.6,5.3-37.2h29.3c0,8.2-1,16.8-3,25.6-2,8.8-5,17.1-8.8,25-3.8,7.9-8.2,14.3-13.1,19.4H15.6Z'%20fill='%23e7343f'%20stroke-width='0'/%3e%3c/svg%3e">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full antialiased bg-gradient-to-br from-blue-50 via-white to-indigo-50">
    <div class="min-h-full flex flex-col">
        <!-- Navigation -->
        <nav class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center gap-2">
                        <a href="{{ url('/') }}" class="flex items-center gap-2">
                            <svg width="32" height="32" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M15.6,75c6.1-9.7,10.8-20.6,14.2-32.8,3.4-12.2,5.2-24.6,5.3-37.2h29.3c0,8.2-1,16.8-3,25.6-2,8.8-5,17.1-8.8,25-3.8,7.9-8.2,14.3-13.1,19.4H15.6Z" fill="#e7343f" stroke-width="0"/>
                            </svg>
                            <span class="text-2xl font-bold text-indigo-600">Hallo Invoicing</span>
                        </a>
                    </div>
                    <div class="flex items-center space-x-4">
                        @if (Route::has('login'))
                            @auth
                                <a href="{{ url('/dashboard') }}" class="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium">
                                    Dashboard
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium">
                                    Inloggen
                                </a>
                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}" class="bg-indigo-600 text-white hover:bg-indigo-700 px-4 py-2 rounded-md text-sm font-medium">
                                        Gratis Starten
                                    </a>
                                @endif
                            @endauth
                        @endif
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <main class="flex-1 flex flex-col">
            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="bg-white border-t border-gray-200">
            <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
                <p class="text-center text-gray-500 text-sm">
                    &copy; {{ date('Y') }} Hallo Invoicing. Ontwikkeld door <span class="font-semibold">Hallo ICT</span>. Alle rechten voorbehouden.
                </p>
            </div>
        </footer>
    </div>
</body>
</html>
