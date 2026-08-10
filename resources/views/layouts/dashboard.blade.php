
    {{-- Layout bersama untuk SEMUA halaman yang punya sidebar (dashboard, courses,
    live-class, teachers, students, dst). Sidebar & tombol dark mode jadi
    otomatis ada di halaman manapun yang extends layout ini — tidak perlu
    copy-paste manual lagi, dan tidak akan ada lagi halaman yang "kelewat"
    tidak dapat sidebar seperti sebelumnya.

    Cara pakai di halaman:
        @extends('layouts.dashboard')
        @section('title', 'Judul Halaman')
        @php($active = 'Attendance')
@section('page')
    ... isi halaman di sini, TANPA <div class="flex min-h-screen">,
        TANPA @include('partials.sidebar'), TANPA <main> ...
        @endsection

        Halaman yang MEMANG tidak boleh punya sidebar (misalnya halaman publik
        self check-in) tetap extends 'layouts.app' langsung, bukan layout ini. --}}

        @extends('layouts.app')

        @section('content')
            <div class="flex min-h-screen">
                @include('partials.sidebar', ['active' => $active ?? null])

                <main class="flex-1 p-6">
                    @yield('page')
                </main>
            </div>
        @endsection
