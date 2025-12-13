<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <title>Panduan Pemasangan Relay Set Biled Foglamp - RelayLab Autolight</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous" />

    <style>
        body {
            background-color: #f5f5f7;
        }

        .brand-header {
            background: linear-gradient(135deg, #111827, #1f2937);
            color: #f9fafb;
            padding: 2.5rem 0 2rem 0;
        }

        .brand-logo-circle {
            width: 60px;
            height: 60px;
            border-radius: 999px;
            background: rgba(249, 250, 251, 0.08);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.4rem;
            border: 1px solid rgba(249, 250, 251, 0.25);
        }

        .step-badge {
            min-width: 34px;
            height: 34px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 0.75rem;
            background-color: #111827;
            color: #f9fafb;
            font-size: 0.9rem;
        }

        .section-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: #111827;
        }

        .card-step {
            border-radius: 0.85rem;
            border: 1px solid #e5e7eb;
            background-color: #ffffff;
        }

        .card-step+.card-step {
            margin-top: 0.75rem;
        }

        .badge-tip {
            font-size: 0.75rem;
            letter-spacing: 0.03em;
        }

        @media (min-width: 992px) {
            .max-width-800 {
                max-width: 820px;
                margin-left: auto;
                margin-right: auto;
            }
        }
    </style>
</head>

<body>

    <!-- Header / Hero -->
    <header class="brand-header mb-4 mb-md-5">
        <div class="container max-width-800">
            <div class="d-flex align-items-center mb-3">

                <div>
                    <div class="fw-semibold text-uppercase small">
                        RelayLab Autolight
                    </div>
                    <h1 class="h4 h3-md mb-0">
                        Panduan Pemasangan Relay Set Biled Foglamp
                    </h1>
                </div>
            </div>
            <p class="mb-0 small">
                Halaman ini berisi langkah-langkah pemasangan Relay Set Biled Foglamp beserta pengaturan kabel
                <strong>PUTIH</strong> dan <strong>KUNING</strong> (sensor High Beam), dibuat agar mudah dipahami
                oleh bengkel maupun pengguna langsung.
            </p>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container max-width-800 pb-5">

        <!-- Info singkat -->
        <section class="mb-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-3 p-md-4">
                    <span class="badge rounded-pill text-bg-dark badge-tip mb-2">
                        INFO SINGKAT
                    </span>
                    <p class="mb-2 small text-secondary">
                        Panduan ini berlaku untuk:
                    </p>
                    <ul class="small mb-0 text-secondary">
                        <li>Relay Set Biled Foglamp varian <strong>Standar</strong> dan <strong>Auto Off Devil</strong>.
                        </li>
                        <li>Mobil dengan sistem trigger headlamp <strong>positif</strong> maupun
                            <strong>negatif</strong>.
                        </li>
                        <li>Jenis headlamp: <strong>H4</strong>, kombinasi <strong>H11 + HB3</strong>, maupun
                            headlamp <strong>LED bawaan pabrik</strong>.</li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- Bagian A: Cara pemasangan umum -->
        <section class="mb-4">
            <h2 class="section-title">A. Pemasangan Umum Relay Set Biled Foglamp</h2>

            <!-- Step 1 -->
            <div class="card-step p-3 p-md-3">
                <div class="d-flex">
                    <div class="step-badge">1</div>
                    <div>
                        <p class="mb-1 fw-semibold">Pasang soket H11 / HB3 male ke kabel merah &amp; hitam kecil</p>
                        <p class="mb-0 small text-secondary">
                            Pasang soket H11 / HB3 male (yang ada di paket pembelian) ke
                            <strong>kabel merah dan hitam kecil</strong> dari Relay Set.
                            Pastikan posisi <strong>positif (+)</strong> dan <strong>negatif (–)</strong> sesuai
                            dengan soket H11 / HB3 female (soket Foglamp bawaan mobil).
                        </p>
                        <p class="mb-0 mt-1 small text-danger">
                            ⚠️ Jika terbalik, Foglamp tidak akan menyala.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Step 2 -->
            <div class="card-step p-3 p-md-3">
                <div class="d-flex">
                    <div class="step-badge">2</div>
                    <div>
                        <p class="mb-1 fw-semibold">Hubungkan Output Relay Set ke Biled Foglamp</p>
                        <p class="mb-0 small text-secondary">
                            Relay Set memiliki <strong>2 Output</strong> menuju Biled Foglamp kanan dan kiri.
                            Pasang soket Output <strong>H11 female</strong> dari Relay Set ke masing-masing
                            <strong>Biled Foglamp</strong>.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Step 3 -->
            <div class="card-step p-3 p-md-3">
                <div class="d-flex">
                    <div class="step-badge">3</div>
                    <div>
                        <p class="mb-1 fw-semibold">Sambungkan kabel High Beam Foglamp ke solenoid Biled</p>
                        <p class="mb-0 small text-secondary">
                            Sambungkan kabel <strong>Lampu Jauh</strong> dari Relay Set ke
                            <strong>kabel Positif Selenoid Biled</strong>. Jika selenoid Biled memiliki
                            <strong>kabel Negatif / Massa</strong>, sambungkan ke <strong>Body Mobil</strong>
                            sebagai <strong>Grounding</strong>.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Step 4 -->
            <div class="card-step p-3 p-md-3">
                <div class="d-flex">
                    <div class="step-badge">4</div>
                    <div>
                        <p class="mb-1 fw-semibold">Varian "Auto Off Devil"</p>
                        <p class="mb-0 small text-secondary">
                            Untuk varian produk <strong>"Auto Off Devil"</strong>:
                        </p>
                        <ul class="small text-secondary mb-0 mt-1">
                            <li>Sambungkan kabel <strong>Positif Devil Eye</strong> dari Relay Set ke
                                <strong>kabel Positif Devil Eye Biled Foglamp</strong>.
                            </li>
                            <li>Jika Devil Eye memiliki kabel Negatif / Massa, sambungkan ke
                                <strong>Body Mobil</strong> sebagai Grounding.
                            </li>
                            <li>Sambungkan kabel bertuliskan <strong>"Ke Jalur Senja"</strong> ke
                                <strong>kabel positif jalur senja</strong> mobil. Kabel ini merupakan
                                <strong>sumber arus Devil Eye</strong>.
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Step 5 -->
            <div class="card-step p-3 p-md-3">
                <div class="d-flex">
                    <div class="step-badge">5</div>
                    <div>
                        <p class="mb-1 fw-semibold">Grounding utama Biled Foglamp</p>
                        <p class="mb-0 small text-secondary">
                            Sambungkan semua <strong>kabel hitam besar</strong> dari Relay Set ke
                            <strong>Body Mobil</strong> sebagai <strong>Grounding utama Biled Foglamp</strong>.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Step 6 -->
            <div class="card-step p-3 p-md-3">
                <div class="d-flex">
                    <div class="step-badge">6</div>
                    <div>
                        <p class="mb-1 fw-semibold">Sambungkan ke Aki</p>
                        <p class="mb-0 small text-secondary">
                            Sambungkan <strong>kabel merah besar</strong> dari Relay Set ke
                            <strong>Positif Aki</strong>.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Step 7 -->
            <div class="card-step p-3 p-md-3">
                <div class="d-flex">
                    <div class="step-badge">7</div>
                    <div>
                        <p class="mb-1 fw-semibold">Tes awal Low Beam Foglamp</p>
                        <p class="mb-0 small text-secondary">
                            Nyalakan saklar Foglamp:
                        </p>
                        <ul class="small text-secondary mb-0 mt-1">
                            <li>Jika <strong>Low Beam Foglamp belum menyala</strong>, cek kembali pemasangan soket
                                H11 / HB3 pada langkah 1 dan pastikan <strong>+ dan – tidak terbalik</strong>.</li>
                            <li>Jika <strong>Low Beam Foglamp sudah menyala</strong>, lanjut ke pengaturan
                                <strong>kabel PUTIH dan KUNING</strong> pada langkah berikutnya.
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

        </section>

        <!-- Bagian B: Kabel putih & kuning -->
        <section class="mb-4">
            <h2 class="section-title">B. Pengaturan Kabel PUTIH &amp; KUNING (Sensor High Beam)</h2>

            <!-- Step 8 -->
            <div class="card-step p-3 p-md-3 mb-2">
                <div class="d-flex">
                    <div class="step-badge">8</div>
                    <div>
                        <p class="mb-1 fw-semibold">Fungsi kabel PUTIH dan KUNING</p>
                        <p class="mb-0 small text-secondary">
                            Dari Relay Set terdapat <strong>kabel PUTIH</strong> dan
                            <strong>kabel KUNING</strong>. Kedua kabel ini <strong>bukan kabel beban</strong>,
                            melainkan <strong>kabel sensor</strong> untuk membaca sinyal
                            <strong>lampu jauh (High Beam / Pass Beam)</strong>.
                        </p>
                        <p class="mb-0 mt-2 small text-secondary">
                            <strong>Aturan umum:</strong>
                        </p>
                        <ul class="small text-secondary mb-0 mt-1">
                            <li><strong>PUTIH</strong> → sambungkan ke <strong>jalur lampu jauh headlamp</strong>.</li>
                            <li><strong>KUNING</strong> → sambungkan ke <strong>jalur kebalikan PUTIH</strong>
                                pada soket yang sama (jalur yang tegangannya selalu <em>berlawanan</em>
                                dengan kabel PUTIH).</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Catatan -->
            <div class="alert alert-secondary py-2 px-3 small mb-0">
                <strong>Catatan:</strong> Dengan cara ini, Relay Set dapat dipakai pada mobil dengan
                sistem trigger headlamp <strong>positif</strong> maupun <strong>negatif</strong> tanpa
                perlu mengubah jalur kabel bawaan.
            </div>
        </section>

        <!-- Bagian C: Contoh berdasarkan jenis headlamp -->
        <section class="mb-4">
            <h2 class="section-title">C. Contoh Pemasangan Berdasarkan Jenis Headlamp</h2>

            <!-- Step 9: H4 -->
            <div class="card-step p-3 p-md-3">
                <div class="d-flex">
                    <div class="step-badge">9</div>
                    <div>
                        <p class="mb-1 fw-semibold">
                            Mobil dengan headlamp soket H4 (3 pin)
                        </p>
                        <p class="mb-0 small text-secondary">
                            Contoh: Avanza Lama, Xenia Lama, Sigra, Calya, dan Mobil yang non LED dengan soket H4.
                        </p>
                        <ul class="small text-secondary mb-0 mt-1">
                            <li>Sambungkan <strong>kabel PUTIH</strong> ke
                                <strong>jalur High Beam soket H4</strong>
                                (kabel yang mendapat arus saat lampu jauh / pass dinyalakan).
                            </li>
                            <li>Sambungkan <strong>kabel KUNING</strong> ke
                                <strong>jalur Standby soket H4</strong>
                                (jalur yang menjadi <em>kebalikan</em> dari jalur High Beam).
                            </li>
                        </ul>
                        <p class="mb-0 mt-2 small text-secondary">
                            <strong>Ringkasan:</strong>
                            <br />
                            <strong>PUTIH → High Beam H4</strong><br />
                            <strong>KUNING → Standby H4</strong>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Step 10: H11 + HB3 -->
            <div class="card-step p-3 p-md-3">
                <div class="d-flex">
                    <div class="step-badge">10</div>
                    <div>
                        <p class="mb-1 fw-semibold">
                            Mobil dengan headlamp double (Low H11, High HB3)
                        </p>
                        <p class="mb-0 small text-secondary">
                            Contoh: Rush, Terios, Fortuner, Pajero, dan sejenisnya.
                        </p>
                        <p class="mb-0 mt-1 small text-secondary">
                            Pada <strong>soket HB3 (High Beam)</strong>:
                        </p>
                        <ul class="small text-secondary mb-0 mt-1">
                            <li>Sambungkan <strong>kabel PUTIH</strong> ke
                                <strong>kabel positif (+) HB3</strong>.
                            </li>
                            <li>Sambungkan <strong>kabel KUNING</strong> ke
                                <strong>kabel negatif (–) HB3</strong>.
                            </li>
                        </ul>
                        <p class="mb-0 mt-2 small text-secondary">
                            Karena HB3 hanya memiliki 2 kabel (+ dan –),
                            <strong>kabel KUNING otomatis menjadi jalur kebalikan dari PUTIH</strong>.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Step 11: Headlamp LED -->
            <div class="card-step p-3 p-md-3">
                <div class="d-flex">
                    <div class="step-badge">11</div>
                    <div>
                        <p class="mb-1 fw-semibold">
                            Mobil dengan headlamp LED bawaan pabrik
                        </p>
                        <p class="mb-0 small text-secondary">
                            Pada soket headlamp LED:
                        </p>
                        <ul class="small text-secondary mb-0 mt-1">
                            <li>Sambungkan <strong>kabel PUTIH</strong> ke
                                <strong>kabel lampu jauh (High Beam)</strong>.
                            </li>
                            <li>Sambungkan <strong>kabel KUNING</strong> ke kabel lain yang
                                <strong>polaritasnya berlawanan</strong> dengan kabel PUTIH:
                                <ul class="mt-1">
                                    <li>Jika kabel PUTIH membaca <strong>arus positif</strong>,
                                        maka kabel KUNING sambungkan ke <strong>jalur negatif / ground</strong>.</li>
                                    <li>Jika kabel PUTIH membaca <strong>jalur negatif</strong>,
                                        maka kabel KUNING sambungkan ke <strong>jalur positif</strong>.</li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

        </section>

        <!-- Bagian D: Tes akhir -->
        <section class="mb-4">
            <h2 class="section-title">D. Tes Akhir High Beam Foglamp</h2>

            <!-- Step 12 -->
            <div class="card-step p-3 p-md-3">
                <div class="d-flex">
                    <div class="step-badge">12</div>
                    <div>
                        <p class="mb-1 fw-semibold">Pastikan Foglamp ikut menyala saat lampu jauh diaktifkan</p>
                        <ol class="small text-secondary mb-2">
                            <li>Nyalakan lampu seperti kondisi normal.</li>
                            <li>Tarik tuas <strong>lampu jauh / Pass Beam</strong>.</li>
                            <li>
                                Jika pemasangan benar:
                                <ul class="mt-1">
                                    <li>Relay <strong>High Beam Foglamp</strong> akan berbunyi <strong>"klik"</strong>
                                        dan lampu indikator relay akan menyala.
                                    </li>
                                    <li>Foglamp ikut menyala saat lampu jauh / pass diaktifkan.</li>
                                </ul>
                            </li>
                        </ol>
                        <p class="mb-1 small text-secondary">
                            Jika <strong>Foglamp belum ikut menyala</strong> ketika lampu jauh dinyalakan:
                        </p>
                        <ul class="small text-secondary mb-0">
                            <li>Biarkan <strong>kabel PUTIH</strong> tetap di jalur lampu jauh.</li>
                            <li>Pindahkan <strong>kabel KUNING</strong> ke jalur lain yang
                                <strong>kebalikan</strong> dari kabel PUTIH pada soket yang sama.
                            </li>
                            <li>Tes kembali sampai Foglamp ikut menyala saat lampu jauh / pass dinyalakan.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer kecil -->
        <footer class="pt-3 border-top small text-secondary text-center">
            <p class="mb-1">
                &copy; <span id="year"></span> RelayLab Autolight &mdash; Panduan pemasangan Relay Set Biled Foglamp.
            </p>
            <p class="mb-0">
                Jika masih ragu dalam pemasangan, silakan konsultasi ke bengkel terpercaya atau hubungi RelayLab Hotline
                (0898-4967-370).
            </p>
        </footer>

    </main>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>

    <script>
        // Set tahun di footer
        document.getElementById('year').textContent = new Date().getFullYear();
    </script>
</body>

</html>