<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMA 3 SUMENEP</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .background-image {
            background-image: url('{{ asset('/img/Untitled.svg') }}');
            background-size: cover; /* or 'contain' or '50%' depending on your needs */
            background-repeat: no-repeat;
            background-attachment: fixed; /* keeps the background fixed while scrolling */
            background-position: center;
        }
        .border-opacity-90 {
            border-color: rgba(255, 255, 255, 0.9); /* More opaque border */
        }
        .border-b-2 {
            border-bottom-width: 2px;
        }
        .background-link {
        position: relative;
    }
    .custom-link {
        position: relative;
        padding: 0.5rem 1rem; /* Adjust padding as needed */
        background-color: rgba(107, 114, 128, 0.1); /* Light gray background */
        border-radius: 0.25rem; /* Rounded corners */
        transition: background-color 0.3s ease; /* Smooth transition for background color */
    }
    .custom-link:hover, .custom-link:focus {
        background-color: rgba(107, 114, 128, 0.3); /* Darker gray on hover/focus */
    }
    </style>
</head>
<body class="bg-gray-100 text-gray-900 flex flex-col min-h-screen background-image">
    <header class="bg-white bg-opacity-90 shadow border-b border-opacity-90">
        <div class="container mx-auto flex justify-between items-center p-6">
            <img src="{{ asset('/img/img 1.svg') }}" alt="Logo Sekolah" class="h-12">

            <nav>
                <ul class="flex space-x-4">
                    @if (Route::has('login'))
                    <div class="flex space-x-4">
                        @auth
                        @else
                            <a href="{{ route('login') }}" class="text-gray-700 hover:text-gray-900 font-semibold border-b-2 border-gray-700 custom-link">Log in</a>

                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="ml-4 text-gray-700 hover:text-gray-900 font-semibold border-b-2 border-gray-700 custom-link">Register</a>
                            @endif
                        @endauth
                    </div>
                    @endif
                </ul>
            </nav>

        </div>
    </header>

    <main class="container mx-auto mt-8 flex-grow bg-white bg-opacity-90 p-6 rounded shadow border border-opacity-90">
        <section id="visi-misi">
            <h2 class="text-2xl font-bold mb-4">Visi Misi</h2>
            <p class="mb-2">MISI: BERIMAN DAN BERTAQWA, BERPRESTASI SERTA BERBUDAYA LINGKUNGAN</p>
            <p>VISI:</p>
            <p>1. Menumbuh kembangkan pemahaman dan  penghayatan terhadap ajaran agamanya masing-masing, nilai-nilai luhur budaya bangsa sehingga tumbuh perilaku dan budi pekerti luhur.</p>
            <p>2. Menciptakan lingkungan pembelajaran yang kondusif dalam upaya meningkatkan mutu pembelajaran.</p>
            <p>3. Mewujudkan prestasi bidang akademik dan non akademik secara kompetitif.</p>
            <p>4. Melaksanakan pembelajaran dan bimbingan secara efektif sehingga setiap siswa berkembang secara optimal sesuai dengan potensi yang dimilikinya.</p>
            <p>5. Mewujudkan lingkungan sekolah yang hijau, bersih, indah dan sehat</p>
            <p>6. Mewujudkan perilaku peduli lingkungan melalui pembiasaan-pembiasaan yang positif</p>
            <p>7. Mewujudkan pelestarian lingkungan sekitar sekolah</p>
        </section>
    </main>

    <footer class="bg-blue-900 text-white p-4 text-center bg-opacity-90">
        <p>&copy; 2025 SMAN 3 SUMENEP</p>
    </footer>

    <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>