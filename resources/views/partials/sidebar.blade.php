{{-- Sidebar dipakai bersama di semua halaman --}}
@php
    $active = trim($active ?? '');
    $user = auth()->user();
    $role = $user ? $user->role : 'student';

    // Base menu structure filtered by role
    $allMenus = [
        ['label' => 'Home', 'icon' => '🏠', 'route' => 'dashboard', 'roles' => ['admin', 'teacher', 'student']],
        ['label' => 'Students', 'icon' => '🎓', 'route' => 'students.index', 'roles' => ['admin', 'teacher']],
        ['label' => 'Teachers', 'icon' => '👤', 'route' => 'teachers.index', 'roles' => ['admin', 'teacher']],
        ['label' => 'Courses', 'icon' => '📘', 'route' => 'courses.index', 'roles' => ['admin', 'teacher', 'student']],
        ['label' => 'Live Class', 'icon' => '▶️', 'route' => 'live-class.index', 'roles' => ['admin', 'teacher', 'student']],
        ['label' => 'Attendance', 'icon' => '📋', 'route' => 'attendance.index', 'roles' => ['admin', 'teacher']],
        ['label' => 'Payments', 'icon' => '💳', 'route' => 'payments.index', 'roles' => ['admin', 'teacher']],
        ['label' => 'Library', 'icon' => '📚', 'route' => 'library.index', 'roles' => ['admin', 'teacher', 'student']],
        ['label' => 'Reports', 'icon' => '⚠️', 'route' => 'reports.index', 'roles' => ['admin', 'teacher']],
    ];

    $menu = array_filter($allMenus, function($item) use ($role) {
        return in_array($role, $item['roles']);
    });
@endphp

<aside
    class="w-64 bg-white dark:bg-slate-800 border-r border-slate-100 dark:border-slate-700/60 flex flex-col justify-between py-6 px-4 shrink-0 min-h-screen">
    <div>
        <div class="flex items-center justify-between px-2 mb-8">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-indigo-500 text-white font-bold flex items-center justify-center text-base shadow-sm shadow-indigo-500/30">
                    S
                </div>
                <div>
                    <span class="font-extrabold text-lg tracking-tight text-slate-900 dark:text-white block leading-tight">SkillSet</span>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-500 block">
                        {{ strtoupper($role) }} PANEL
                    </span>
                </div>
            </div>
            <button onclick="toggleDarkMode()" title="Ganti mode terang/gelap"
                class="w-8 h-8 rounded-full bg-slate-50 dark:bg-slate-700 flex items-center justify-center text-xs text-slate-600 dark:text-slate-300 hover:bg-slate-100 transition">
                <span class="dark:hidden">🌙</span>
                <span class="hidden dark:inline">☀️</span>
            </button>
        </div>

        <nav class="space-y-1">
            @foreach ($menu as $item)
                <a href="{{ $item['route'] ? route($item['route']) : '#' }}"
                    class="flex items-center gap-3 px-3.5 py-2.5 rounded-2xl text-xs font-semibold transition-all duration-200
                   {{ $active === $item['label'] ? 'bg-indigo-500 text-white shadow-sm shadow-indigo-500/30' : 'text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700/50 hover:text-slate-800 dark:hover:text-slate-200' }}">
                    <span>{{ $item['icon'] }}</span>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>
    </div>

    <div class="space-y-3">
        {{-- USER PROFILE CARD & LOGOUT BUTTON --}}
        <div class="rounded-2xl bg-slate-50 dark:bg-slate-700/50 p-3.5 border border-slate-100 dark:border-slate-700/40 flex items-center justify-between">
            <div class="flex items-center gap-2.5 min-w-0">
                <img src="{{ 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . urlencode($user->name ?? 'User') }}"
                     class="w-8 h-8 rounded-full bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 shrink-0" alt="">
                <div class="truncate">
                    <p class="text-xs font-bold text-slate-800 dark:text-slate-100 truncate">{{ $user->name ?? 'User SkillSet' }}</p>
                    <p class="text-[10px] text-indigo-500 font-semibold capitalize truncate">{{ $role }}</p>
                </div>
            </div>
            
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" title="Logout dari Sistem" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-500/10 transition">
                    🚪
                </button>
            </form>
        </div>
    </div>
</aside>
