<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'SkillSet Dashboard')</title>

    {{-- Set class "dark" SEBELUM Tailwind dimuat, supaya tidak ada kedipan
         (flash) dari mode terang ke gelap saat halaman pertama kali dibuka. --}}
    <script>
        (function () {
            const saved = localStorage.getItem('skillset_theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (saved === 'dark' || (!saved && prefersDark)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class' };
    </script>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-[#F5F6FA] text-slate-800 dark:bg-slate-900 dark:text-slate-100 transition-colors">
    @yield('content')

    <script>
        // Dipakai tombol 🌙/☀️ di seluruh halaman (lihat partials/sidebar & top bar dashboard)
        function toggleDarkMode() {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('skillset_theme', isDark ? 'dark' : 'light');
        }
    </script>
</body>
</html>
