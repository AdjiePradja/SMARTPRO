<!DOCTYPE html>
<html>
<head>
    <title>SOP - {{ $no_dokumen }}</title>
    <style>
        @page {
            size: A4;
            /* Margin atas ditingkatkan ke 190px untuk memberi ruang header yang sangat aman */
            margin: 190px 1.2cm 1cm 1.2cm; 
        }
        
        header {
            position: fixed;
            /* Top ditarik lebih jauh ke atas agar menjauh dari isi body */
            top: -170px; 
            left: 0;
            right: 0;
            height: 140px;
            z-index: 1000;
        }

        body { 
            font-family: sans-serif; 
            font-size: 11px; 
            line-height: 1.5; 
            margin: 0;
            padding: 0;
        }

        /* Container utama */
        main {
            width: 100%;
            padding-top: 10px;
        }

        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; table-layout: fixed; }
        th, td { border: 1px solid #000; padding: 5px; vertical-align: middle; word-wrap: break-word; }
        
        header table { margin-bottom: 0; }

        .section-title { background-color: #eeeeee; font-weight: bold; padding: 5px; border: 1px solid #000; margin-top: 0; }
        .content-area { margin-bottom: 15px; text-align: justify; padding: 10px; border: 1px solid #000; border-top: none; page-break-inside: auto; }
        .text-center { text-align: center; }

        .page-break { page-break-after: always; }

        /* Style khusus Tabel Riwayat agar tidak ada garis double */
        .history-table { border-collapse: collapse; margin-bottom: 0; width: 100%; border: none; }
        .history-table td, .history-table th { border: 1px solid #000; }

        /* Tambahkan atau update di bagian style */
        .lampiran-wrapper {
            page-break-inside: avoid; /* Mencegah konten di dalamnya terpotong antar halaman */
            margin-bottom: 20px;
            width: 100%;
        }

        .img-container {
            text-align: center;
            margin-top: 10px;
            display: block;
        }

        /* Memastikan gambar tidak melebihi lebar halaman */
        .img-container img {
            max-width: 100%;
            max-height: 500px; /* Batasi tinggi maksimal agar tidak terlalu besar */
            object-fit: contain;
        }

        /* Update pada bagian style */
        .content-area { 
            margin-bottom: 15px; 
            text-align: justify; /* Sudah benar */
            padding: 10px; 
            border: 1px solid #000; 
            border-top: none; 
            page-break-inside: auto; 
        }

        /* Update pada bagian style */
        .content-area-lampiran { 
            margin-bottom: 15px; 
            text-align: justify; /* Sudah benar */
            padding: 10px; 
            border-top: none; 
            page-break-inside: auto; 
        }

        /* Pastikan kolom aktivitas dalam tabel juga justify */
        .activity-cell {
            text-align: justify;
            vertical-align: top;
            padding: 8px;
        }

        /* Penomoran otomatis untuk list di dalam content-area */
        .purpose-list {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }

        .purpose-item {
            display: table;
            width: 100%;
            margin-bottom: 5px;
            text-align: justify; /* Membuat teks rata kiri-kanan */
        }

        .purpose-number {
            display: table-cell;
            width: 35px; /* Sesuaikan lebar kolom nomor */
            vertical-align: top;
            font-weight: normal;
        }

        .purpose-text {
            display: table-cell;
            vertical-align: top;
        }

        /* Pastikan tabel diizinkan untuk pecah halaman, tapi header tetap ikut */
        table {
            page-break-inside: auto;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        thead {
            display: table-header-group; /* Fungsi ini membuat header muncul lagi di halaman baru */
        }

        tfoot {
            display: table-footer-group;
        }
    </style>
</head>
<body>
    <header>
        <table>
            <tr>
                <td rowspan="5" width="20%" class="text-center">
                    @php
                        $path = public_path('assets/img/LogoPPA.png');
                        $base64 = null;
                        if (file_exists($path)) {
                            $type = pathinfo($path, PATHINFO_EXTENSION);
                            $data = file_get_contents($path);
                            $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                        }
                    @endphp
                    @if($base64)
                        <img src="{{ $base64 }}" style="width: 80px; height: auto;">
                    @else
                        <strong>LOGO</strong>
                    @endif
                </td>
                <td rowspan="2" width="45%" class="text-center" style="font-size: 14px;">
                    <strong>STANDARD OPERATING PROCEDURE</strong>
                </td>
                <td width="35%">No. Dokumen: {{ $no_dokumen }}</td>
            </tr>
            <tr>
                <td>No. Revisi: {{ $revisi ?? '0' }}</td>
            </tr>
            <tr>
                <td rowspan="3" class="text-center" style="font-size: 12px;">
                    <strong>{{ strtoupper($judul) }}</strong>
                </td>
                <td>Edisi: {{ $edisi ?? '1' }}</td>
            </tr>
            <tr>
                <td>Tgl. Terbit: {{ (!empty($efektif_date) && $efektif_date != '0000-00-00') ? date('d/m/Y', strtotime($efektif_date)) : '-' }}</td>
            </tr>
            <tr>
                <!-- tanggal revisi kosong ketika awal pembuatan dokumen -->
                <td>Tgl. Revisi: {{ (!empty($pjo_date) && $pjo_date != '-') ? date('d/m/Y', strtotime($pjo_date)) : '-' }}</td>
            </tr>
        </table>
    </header>

    <main>
        @if(isset($history_notes) && count($history_notes) > 0)
            <div class="section-title text-center">CATATAN REVISI</div>
            <div class="content-area" style="padding: 0; border-bottom: none;">
                <table class="history-table" style="margin-top: 10px;">
                    <thead>
                        <tr style="background-color: #f9f9f9;">
                            <th width="5%" style="padding: 8px;" class="text-center">No</th>
                            <th width="18%" style="padding: 8px;">No Revisi &amp; Edisi</th>
                            <th width="17%" style="padding: 8px;">Tanggal Revisi</th>
                            <th width="12%" style="padding: 8px;" class="text-center">Halaman</th>
                            <th width="48%" style="padding: 8px;">Catatan Revisi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($history_notes as $note)
                            @if(!empty(trim($note['catatan'])))
                                @php
                                    $nomor = $loop->iteration; // Nomor urut berdasarkan iterasi
                                    $hasHal = str_contains($note['catatan'], '[HAL:');
                                    $subNotes = $hasHal ? array_filter(explode('[END]', $note['catatan'])) : [$note['catatan']];
                                @endphp

                                @foreach($subNotes as $sub)
                                    @if(!empty(trim($sub)))
                                        @php
                                            // Ambil tanggal manual per-catatan (marker [TGL:...]) jika ada.
                                            $tglRow = null;
                                            if (preg_match('/\[TGL:(.*?)\]/', $sub, $tglMatch)) {
                                                $tglRow = trim($tglMatch[1]);
                                            }
                                            // Bersihkan marker tanggal sebelum parsing halaman/isi.
                                            $subClean = preg_replace('/\[TGL:.*?\]/', '', $sub);

                                            if ($hasHal) {
                                                preg_match('/\[HAL:(.*?)\](.*)/s', $subClean, $matches);
                                                $hal = $matches[1] ?? '-';
                                                $isi = $matches[2] ?? $subClean;
                                            } else {
                                                $hal = '-';
                                                $isi = $subClean;
                                            }

                                            // Tampilkan tanggal input manual per-catatan; jika tidak ada,
                                            // gunakan tanggal note (hanya baris pertama) sebagai fallback.
                                            if (!empty($tglRow)) {
                                                $tglDisplay = $tglRow;
                                            } elseif ($loop->first) {
                                                $tglDisplay = $note['tanggal'] ?? '';
                                            } else {
                                                $tglDisplay = '';
                                            }
                                        @endphp
                                        <tr>
                                            {{-- No, No Revisi & Edisi, dan Tanggal hanya tampil di baris pertama tiap revisi --}}
                                            <td style="padding: 8px; vertical-align: top;" class="text-center">
                                                @if($loop->first){{ $nomor }}@endif
                                            </td>
                                            <td style="padding: 8px; vertical-align: top;">
                                                @if($loop->first)<strong>{{ $note['versi'] ?? '-' }}</strong>@endif
                                            </td>
                                            <!-- edit disini , diatas-->
                                            <td style="padding: 8px; vertical-align: top;">
                                                @if(!empty($tglDisplay))<small>{{ $tglDisplay }}</small>@endif
                                            </td>
                                            <td style="padding: 8px; vertical-align: top;" class="text-center">
                                                {{ $hal }}
                                            </td>
                                            <td style="padding: 8px; vertical-align: top;">
                                                {!! nl2br(e(trim($isi))) !!}
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="page-break"></div>
        @endif

        <div class="section-title">I. TUJUAN</div>
        <div class="content-area">
            @if(!empty($tujuan))
                <div class="purpose-list">
                    @php 
                        // Memecah teks tujuan berdasarkan baris baru
                        $tujuanLines = explode("\n", str_replace("\r", "", $tujuan)); 
                        $lineNum = 1;
                    @endphp
                    @foreach($tujuanLines as $line)
                        @if(trim($line) !== "")
                            <div class="purpose-item">
                                <div class="purpose-number">1.{{ $lineNum++ }}</div>
                                <div class="purpose-text">{{ trim($line) }}</div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @else
                -
            @endif
        </div>

        <div class="section-title">II. RUANG LINGKUP</div>
        <div class="content-area">
            @if(!empty($ruang_lingkup))
                <div class="purpose-list">
                    @php 
                        // Memecah teks ruang lingkup berdasarkan baris baru
                        $lingkupLines = explode("\n", str_replace("\r", "", $ruang_lingkup)); 
                        $lingkupNum = 1;
                    @endphp
                    @foreach($lingkupLines as $line)
                        @if(trim($line) !== "")
                            <div class="purpose-item">
                                <div class="purpose-number">2.{{ $lingkupNum++ }}</div>
                                <div class="purpose-text" style="text-align: justify;">
                                    {{ trim($line) }}
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @else
                <div class="text-center">-</div>
            @endif
        </div>

        <div class="section-title">III. REFERENSI</div>
        <div class="content-area">
            @if(!empty($referensi))
                <div class="purpose-list">
                    @php 
                        $refLines = explode("\n", str_replace("\r", "", $referensi)); 
                        $refNum = 1;
                    @endphp
                    @foreach($refLines as $line)
                        @if(trim($line) !== "")
                            <div class="purpose-item">
                                <div class="purpose-number">3.{{ $refNum++ }}</div>
                                <div class="purpose-text">{{ trim($line) }}</div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>

        <div class="section-title">IV. DEFINISI</div>
        <div class="content-area">
            @if(!empty($definisi))
                <div class="purpose-list">
                    @php 
                        // Pecah berdasarkan baris baru, bersihkan karakter \r jika ada
                        $definisiLines = explode("\n", str_replace("\r", "", $definisi)); 
                        $defNum = 1;
                    @endphp
                    @foreach($definisiLines as $line)
                        @if(trim($line) !== "")
                            <div class="purpose-item">
                                <div class="purpose-number">4.{{ $defNum++ }}</div>
                                <div class="purpose-text" style="text-align: justify;">
                                    {{ trim($line) }}
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @else
                <div class="text-center">-</div>
            @endif
        </div>
        <div style="page-break-before: always;"></div>
        <div class="section-title">V. AKTIVITAS DAN TANGGUNG JAWAB</div>
        <div style="margin-bottom: 20px;">
            <table style="width: 100%; table-layout: fixed; border-collapse: collapse;">
                <thead>
                    <tr style="background-color: #f2f2f2;">
                        <th width="75%" class="text-center">AKTIVITAS</th>
                        <th width="25%" class="text-center">PIC</th>
                    </tr>
                </thead>
                <tbody>
                    @php 
                        // Pecah berdasarkan marker [END] yang dibuat di JavaScript form
                        $items = explode('[END]', $aktifitas_tanggung_jawab); 
                        $actNum = 1;
                    @endphp
                    @foreach($items as $item)
                        @if(trim($item) !== "")
                            @php
                                // Pisahkan antara konten aktivitas dan PIC
                                $parts = explode('[PIC]', $item);
                                $konten = $parts[0] ?? '-';
                                $pic = $parts[1] ?? '-';
                                
                                // Ubah format **Teks** menjadi <strong>Teks</strong>
                                $formattedKonten = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', e(trim($konten)));
                            @endphp
                            <tr>
                                <td style="vertical-align: top; padding: 8px;">
                                    {{-- Gunakan struktur table internal agar nomor dan teks sejajar sempurna --}}
                                    <div style="display: table; width: 100%;">
                                        <div style="display: table-cell; width: 30px; vertical-align: top; font-weight: bold;">
                                            5.{{ $actNum++ }}
                                        </div>
                                        <div style="display: table-cell; vertical-align: top; text-align: justify;">
                                            {!! nl2br($formattedKonten) !!}
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center" style="vertical-align: top; padding: 8px; font-weight: bold;">
                                    {{ trim($pic) }}
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
        <div style="page-break-before: always;"></div>
        <div class="section-title">VI. LAMPIRAN</div>
        
        {{-- Menggunakan div container alih-alih tabel untuk lampiran --}}
        <div class="content-area-lampiran" style="border-top: 0px solid #000; padding: 15px;">
            @php 
                // Cek apakah data dikirim sebagai lampiran_array dari controller
                // Jika tidak, cek apakah ada string JSON lampiran. Jika kosong, buat array kosong.
                $lampirans = $lampiran_array ?? (isset($lampiran) && !empty($lampiran) ? json_decode($lampiran, true) : []); 
            @endphp
            
            @if(!empty($lampirans))
                @foreach($lampirans as $index => $item)
                    <div class="lampiran-wrapper" style="margin-bottom: 30px; page-break-inside: avoid;">
                        <div style="font-weight: bold; margin-bottom: 8px;">
                            {{ $index + 1 }}. {{ $item['judul'] ?? 'Lampiran' }}
                        </div>
                        
                        @if(!empty($item['text'])) 
                            <div style="text-align: justify; margin-bottom: 10px;">
                                {!! nl2br(e($item['text'])) !!}
                            </div>
                        @endif
                        
                        @if(!empty($item['file']))
                            <div class="img-container" style="text-align: center; margin-top: 10px;">
                                @php $img_path = storage_path('app/public/' . $item['file']); @endphp
                                @if(file_exists($img_path))
                                    @php
                                        $img_data = base64_encode(file_get_contents($img_path));
                                        $img_type = pathinfo($img_path, PATHINFO_EXTENSION);
                                    @endphp
                                    <img src="data:image/{{ $img_type }};base64,{{ $img_data }}" style="max-width: 100%; height: auto; border: 1px solid #ccc;">
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            @else 
                <div style="text-align: center;">-</div> 
            @endif
        </div>

        <div style="page-break-before: always;"></div>
        <div class="section-title text-center">HALAMAN PENGESAHAN</div>

        @php
            use App\Models\User;
            $appPath = public_path('assets/img/Approved.png');
            $appBase64 = null;
            if (file_exists($appPath)) {
                $appData = file_get_contents($appPath);
                $appBase64 = 'data:image/' . pathinfo($appPath, PATHINFO_EXTENSION) . ';base64,' . base64_encode($appData);
            }
            $userDHSH = User::where('nama', $DHdanSH)->first();
            $jabatanDHSH = ($userDHSH && $userDHSH->role == 4) ? 'Section Head' : 'Department Head';
        @endphp

        <table style="margin-top: 10px;">
            <thead>
                <tr style="background-color: #f2f2f2;">
                    <th width="35%" class="text-center">NAMA</th>
                    <th width="20%" class="text-center">JABATAN</th>
                    <th width="20%" class="text-center">TANGGAL</th>
                    <th width="25%" class="text-center">PENGESAHAN</th>
                </tr>
            </thead>
            <tbody>
                @php
                    // Pastikan data status didecode menjadi array
                    // Contoh isi: ["Nama User" => "approved", "Nama Lain" => "pending"]
                    $statusData = is_array($people_approve) ? $people_approve : json_decode($people_approve, true);
                @endphp

                @if(isset($people_array) && is_array($people_array))
                    @foreach($people_array as $person)
                        @php
                            $u = User::where('nama', $person)->first();
                            // Logika jabatan berdasarkan role
                            if ($u) {
                                if ($u->role == 1 || $u->role == 5) {
                                    $jt = 'Group Leader';
                                } elseif ($u->role == 6) {
                                    $jt = 'Non Staf';
                                } else {
                                    $jt = '-';
                                }
                            } else {
                                $jt = 'User';
                            }
                                                    
                            // Ambil status approval untuk orang ini
                            $currentStatus = $statusData[$person] ?? 'pending';
                        @endphp
                        <tr>
                            <td><small>Dibuat Oleh:</small><br><strong>{{ $person }}</strong></td>
                            <td class="text-center">{{ $jt }}</td>
                            <td class="text-center">
                                {{-- Tanggal hanya tampil jika statusnya approved --}}
                                {{ ($currentStatus == 'approved' && !empty($pembuat_date)) ? date('d/m/Y', strtotime($pembuat_date)) : '-' }}
                            </td>
                            <td class="text-center">
                                {{-- Gambar HANYA tampil jika status person ini adalah 'approved' --}}
                                @if($currentStatus == 'approved' && $appBase64) 
                                    <img src="{{ $appBase64 }}" width="60"> 
                                @endif
                            </td>
                        </tr>
                    @endforeach
                @endif

                {{-- Baris Pembuat Utama --}}
                <tr>
                    <td><small>Dibuat Oleh:</small><br><strong>{{ $pembuat }}</strong></td>
                    <td class="text-center">Group Leader</td>
                    <td class="text-center">{{ (!empty($pembuat_date) && $pembuat_date != '0000-00-00') ? date('d/m/Y', strtotime($pembuat_date)) : '-' }}</td>
                    <td class="text-center">@if($appBase64) <img src="{{ $appBase64 }}" width="60"> @endif</td>
                </tr>

                {{-- Baris DH/SH --}}
                <tr>
                    <td><small>Ditinjau Oleh:</small><br><strong>{{ $DHdanSH }}</strong></td>
                    <td class="text-center">{{ $jabatanDHSH }}</td>
                    <td class="text-center">{{ (!empty($dhsh_date) && $dhsh_date != '0000-00-00') ? date('d/m/Y', strtotime($dhsh_date)) : '-' }}</td>
                    <td class="text-center">
                        @if(isset($DHdanSHApprove) && $DHdanSHApprove == 'approved' && $appBase64) 
                            <img src="{{ $appBase64 }}" width="60"> 
                        @endif
                    </td>
                </tr>

                {{-- Baris PJO --}}
                <tr>
                    <td><small>Disetujui Oleh:</small><br><strong>{{ $PJO }}</strong></td>
                    <td class="text-center">PJO</td>
                    <td class="text-center">{{ (!empty($pjo_date) && $pjo_date != '0000-00-00') ? date('d/m/Y', strtotime($pjo_date)) : '-' }}</td>
                    <td class="text-center">
                        @if(isset($PJOApprove) && $PJOApprove == 'approved' && $appBase64) 
                            <img src="{{ $appBase64 }}" width="60"> 
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>

        <div style="font-size: 11px; font-style: italic; color: #2361be; margin-top: 15px;">
            *Dokumen elektronik ini merupakan dokumen tidak terkendali apabila dicetak.
        </div>
    </main>
</body>
</html>