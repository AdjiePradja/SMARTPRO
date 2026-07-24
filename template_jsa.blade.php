<!DOCTYPE html>
<html>
<head>
    <title>JSA - {{ $jsa->no_jsa }}</title>
    <style>
        @page { margin: 0.5cm; size: a4 landscape; } /* Landscape agar muat seperti di gambar */
        body { font-family: 'Arial', sans-serif; font-size: 8pt; line-height: 1.2; color: #000; }
        
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        td, th { border: 1px solid #000; padding: 3px 5px; vertical-align: middle; }

        /* Header Area */
        .logo-cell { width: 12%; text-align: center; }
        .title-cell { width: 64%; text-align: center; font-size: 14pt; font-weight: bold; font-style: italic; }
        .meta-cell { width: 24%; padding: 0; }
        .meta-table { border: none; width: 100%; }
        .meta-table td { border: none; border-bottom: 1px solid #000; border-left: 1px solid #000; font-size: 7pt; }
        .meta-table tr:last-child td { border-bottom: none; }

        /* Info & Approval Section */
        .label-cell { width: 15%; font-weight: bold; background-color: #ffffff; }
        .value-cell { width: 30%; font-weight: normal; }
        .approval-head { width: 18.3%; text-align: center; font-weight: bold; }
        .approval-box { height: 60px; }
        .name-row td { height: 15px; font-size: 7pt; }

        /* Table Content */
        .bg-grey { background-color: #d9e1f2; font-weight: bold; text-align: center; }
    </style>
</head>
<body>

    @php
        // Gambar stempel "Approved" untuk kotak pengesahan
        $appPath = public_path('assets/img/Approved.png');
        $appBase64 = null;
        if (file_exists($appPath)) {
            $appData = file_get_contents($appPath);
            $appBase64 = 'data:image/' . pathinfo($appPath, PATHINFO_EXTENSION) . ';base64,' . base64_encode($appData);
        }
    @endphp

    <table>
        <tr>
            <td class="logo-cell">
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
                        <img src="{{ $base64 }}" style="width: 40px; height: auto;">
                    @else
                        <strong>LOGO</strong>
                    @endif
            </td>
            <td class="title-cell">FORMULIR JOB SAFETY ANALYSIS</td>
            <td class="meta-cell">
                <table class="meta-table">
                    <tr><td>No. Dokumen</td><td>PPA-ADRO-F-SHE-03B</td></tr>
                    <tr><td>Revisi</td><td>2</td></tr>
                    <tr><td>Tgl Efektif</td><td>6 September 2022</td></tr>
                    <tr><td>Halaman</td><td>1 dari 1</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table>
        <tr>
            <td class="label-cell">No. Pekerjaan/JSA</td>
            <td class="value-cell">: {{ $jsa->no_jsa }}</td>
            <td class="approval-head">Dibuat Oleh,</td>
            <td class="approval-head">Direview Oleh,</td>
            <td class="approval-head">Disetujui Oleh,</td>
        </tr>
        <tr>
            <td class="label-cell">Tanggal Pembuatan</td>
            <td class="value-cell">: {{ ($jsa->tgl_pembuatan) }}</td>
            {{-- Dibuat Oleh: stempel tampil setelah dokumen dibuat --}}
            <td rowspan="4" class="approval-box" style="text-align: center;">
                @if($appBase64)
                    <img src="{{ $appBase64 }}" style="width: 70px; height: auto;">
                @endif
            </td>
            {{-- Direview Oleh: stempel tampil jika reviewer approve --}}
            <td rowspan="4" class="approval-box" style="text-align: center;">
                @if($jsa->direview_oleh_approve == 'approved' && $appBase64)
                    <img src="{{ $appBase64 }}" style="width: 70px; height: auto;">
                @endif
            </td>
            {{-- Disetujui Oleh: stempel tampil jika DH/SH approve --}}
            <td rowspan="4" class="approval-box" style="text-align: center;">
                @if($jsa->disetujui_oleh_approve == 'approved' && $appBase64)
                    <img src="{{ $appBase64 }}" style="width: 70px; height: auto;">
                @endif
            </td>
        </tr>
        <tr>
            <td class="label-cell">Nama Pekerjaan</td>
            <td class="value-cell">: {{ $jsa->nama_pekerjaan }}</td>
        </tr>
        <tr>
            <td class="label-cell">Departemen</td>
            <td class="value-cell">: {{ $jsa->departemen }}</td>
        </tr>
        <tr>
            <td class="label-cell">Lokasi kerja</td>
            <td class="value-cell">: {{ $jsa->lokasi_kerja }}</td>
        </tr>
        <tr class="name-row">
            <td class="label-cell">APD yang digunakan</td>
            <td class="value-cell">: {{ $jsa->apd_wajib }}</td>
            <td>Nama : {{ $jsa->dibuat_oleh }}</td>
            <td>Nama : {{ $jsa->direview_oleh }}</td>
            <td>Nama : {{ $jsa->disetujui_oleh }}</td>
        </tr>
        <tr class="name-row">
            <td class="label-cell">Peralatan yang digunakan</td>
            <td class="value-cell">: {{ $jsa->peralatan_pendukung }}</td>
            <td>Jabatan : Group Leader</td>
            <td>Jabatan : Group Leader</td>
            <td>Jabatan : {{ $jabatanPenyetuju }}</td>
        </tr>
    </table>

    <table style="margin-top: 0px;">
        <thead>
            <tr class="bg-grey">
                <th style="width: 20%;">Uraian Langkah Pekerjaan</th>
                <th style="width: 20%;">Bahaya dan Risiko</th>
                <th style="width: 50%;">Tindakan Pengendalian</th>
                <th style="width: 10%;">Beri tanda (✓) apabila tindakan pengendalian telah sesuai dan diterapkan pada pekerjaan yang dilakukan. </th>
            </tr>
        </thead>
        <tbody>
            @foreach($jsa->steps as $step)
                @php $totalRows = $step->hazards->flatMap->controls->count(); @endphp
                @foreach($step->hazards as $hIndex => $hazard)
                    @foreach($hazard->controls as $cIndex => $control)
                    <tr>
                        @if($hIndex == 0 && $cIndex == 0)
                            <td rowspan="{{ $totalRows }}">{{ $step->step_no }}. {{ $step->description }}</td>
                        @endif
                        @if($cIndex == 0)
                            <td rowspan="{{ $hazard->controls->count() }}">{{ $hazard->hazard_no }} {{ $hazard->hazard_description }}</td>
                        @endif
                        <td>{{ $control->control_no }} {{ $control->control_description }}</td>
                        <td style="text-align: center; font-family: DejaVu Sans;">{{ $control->approved ? '✔' : '' }}</td>
                    </tr>
                    @endforeach
                @endforeach
            @endforeach
        </tbody>
    </table>

</body>
</html>