<!DOCTYPE html>
<html>
<head>
    <title>SP - {{ $no_dokumen }}</title>
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
            width: 100%; /* PENTING: Lebar pasti agar DOMPDF tidak kebingungan */
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

        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 20px; 
            table-layout: fixed; /* Boleh untuk tabel body agar lebarnya konsisten */
            page-break-inside: auto;
        }

        th, td { 
            border: 1px solid #000; 
            padding: 5px; 
            vertical-align: middle; 
            /* word-wrap: break-word; TELAH DIHAPUS AGAR TEKS TIDAK TERPOTONG KE BAWAH */
        }
        
        header table { 
            margin-bottom: 0; 
            table-layout: auto; /* PENTING: Membiarkan header menyesuaikan lebar kolom secara otomatis */
        }

        .section-title { background-color: #eeeeee; font-weight: bold; padding: 5px; border: 1px solid #000; margin-top: 0; }
        
        .content-area { 
            margin-bottom: 15px; 
            text-align: justify; 
            padding: 10px; 
            border: 1px solid #000; 
            border-top: none; 
            page-break-inside: auto; 
        }

        .content-area-lampiran { 
            margin-bottom: 15px; 
            text-align: justify; 
            padding: 10px; 
            border-top: none; 
            page-break-inside: auto; 
        }

        .text-center { text-align: center; }

        .page-break { page-break-after: always; }

        /* Style khusus Tabel Riwayat agar tidak ada garis double */
        .history-table { border-collapse: collapse; margin-bottom: 0; width: 100%; border: none; }
        .history-table td, .history-table th { border: 1px solid #000; }

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
                <td>Edisi: {{ $edisi ?? '0' }}</td>
            </tr>
            <tr>
                <td>Tgl. Terbit: {{ (!empty($efektif_date) && $efektif_date != '0000-00-00') ? date('d/m/Y', strtotime($efektif_date)) : '-' }}</td>
            </tr>
            <tr>
                <td>Tgl. Revisi: {{ (!empty($dhsh_date) && $dhsh_date != '0000-00-00') ? date('d/m/Y', strtotime($dhsh_date)) : '-' }}</td>
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
                            <th width="20%" style="padding: 8px;">Versi / Tanggal</th>
                            <th width="15%" style="padding: 8px;" class="text-center">Halaman</th>
                            <th width="65%" style="padding: 8px;">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($history_notes as $note)
                            @if(!empty(trim($note['catatan'])))
                                @php
                                    // 1. Tentukan sub-notes (apakah ada pemisah HAL atau tidak)
                                    $hasHal = str_contains($note['catatan'], '[HAL:');
                                    $subNotes = $hasHal ? array_filter(explode('[END]', $note['catatan'])) : [$note['catatan']];
                                    $rowCount = count($subNotes);
                                    $isFirstRow = true;
                                @endphp

                                @foreach($subNotes as $sub)
                                    @if(!empty(trim($sub)))
                                        @php
                                            // 2. Ekstrak data jika menggunakan format [HAL:...]
                                            if ($hasHal) {
                                                preg_match('/\[HAL:(.*?)\](.*)/s', $sub, $matches);
                                                $hal = $matches[1] ?? '-';
                                                $isi = $matches[2] ?? $sub;
                                            } else {
                                                $hal = '-';
                                                $isi = $sub;
                                            }
                                        @endphp
                                        <tr>
                                            {{-- Kolom Versi/Tanggal hanya muncul di baris pertama dengan ROWSPAN --}}
                                            @if($isFirstRow)
                                                <td rowspan="{{ $rowCount }}" style="padding: 8px; vertical-align: top; background-color: #fff;">
                                                    <strong>{{ $note['versi'] }}</strong><br>
                                                    <small>{{ $note['tanggal'] }}</small>
                                                </td>
                                                @php $isFirstRow = false; @endphp
                                            @endif

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
        
        <div class="section-title">I. AKTIVITAS DAN TANGGUNG JAWAB</div>
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
                                $parts = explode('[PIC]', $item);
                                $konten = $parts[0] ?? '-';
                                $pic = $parts[1] ?? '-';
                                $formattedKonten = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', e(trim($konten)));
                            @endphp
                            <tr>
                                {{-- Bagian Aktivitas --}}
                                <td style="vertical-align: top; padding: 8px; text-align: left;"> 
                                    <table style="width: 100%; border-collapse: collapse; border: none; margin: 0; padding: 0;">
                                        <tr>
                                            <td style="width: 30px; vertical-align: top; font-weight: bold; border: none; padding: 0 5px 0 0;">
                                                1.{{ $actNum++ }}
                                            </td>
                                            <td style="vertical-align: top; text-align: justify; border: none; padding: 0;">
                                                {!! nl2br($formattedKonten) !!}
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                                {{-- Bagian PIC --}}
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
                @if(isset($people_array) && is_array($people_array))
                    @foreach($people_array as $person)
                        @php
                            $u = User::where('nama', $person)->first();
                            $jt = $u ? ($u->role == 5 ? 'Group Leader' : ($u->role == 6 ? 'Non Staf' : 'Staff')) : 'User';
                        @endphp
                        <tr>
                            <td><small>Dibuat Oleh:</small><br><strong>{{ $person }}</strong></td>
                            <td class="text-center">{{ $jt }}</td>
                            <td class="text-center">{{ (!empty($pembuat_date) && $pembuat_date != '0000-00-00') ? date('d/m/Y', strtotime($pembuat_date)) : '-' }}</td>
                            <td class="text-center">@if($appBase64) <img src="{{ $appBase64 }}" width="60"> @endif</td>
                        </tr>
                    @endforeach
                @endif
                <tr>
                    <td><small>Dibuat Oleh:</small><br><strong>{{ $pembuat }}</strong></td>
                    <td class="text-center">Group Leader</td>
                    <td class="text-center">{{ (!empty($pembuat_date) && $pembuat_date != '0000-00-00') ? date('d/m/Y', strtotime($pembuat_date)) : '-' }}</td>
                    <td class="text-center">@if($appBase64) <img src="{{ $appBase64 }}" width="60"> @endif</td>
                </tr>
                <tr>
                    <td><small>Ditinjau dan Disetujui Oleh:</small><br><strong>{{ $DHdanSH }}</strong></td>
                    <td class="text-center">{{ $jabatanDHSH }}</td>
                    <td class="text-center">{{ (!empty($dhsh_date) && $dhsh_date != '0000-00-00') ? date('d/m/Y', strtotime($dhsh_date)) : '-' }}</td>
                    <td class="text-center">@if(isset($DHdanSHApprove) && $DHdanSHApprove == 'approved' && $appBase64) <img src="{{ $appBase64 }}" width="60"> @endif</td>
                </tr>
            </tbody>
        </table>

        <div style="font-size: 11px; font-style: italic; color: #2361be; margin-top: 15px;">
            *Dokumen elektronik ini merupakan dokumen tidak terkendali apabila dicetak tanpa seijin Manajemen PT Putra Perkasa Abadi.
        </div>
    </main>
</body>
</html>