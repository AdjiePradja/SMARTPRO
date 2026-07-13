<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        /* Measurements taken directly from the source .docx (1pt = 20 twips). */
        @page { margin: 146pt 28pt 22pt 28pt; }
        * { box-sizing: border-box; }
        body { font-family: Arial, "DejaVu Sans", sans-serif; font-size: 8pt; line-height: 1.3; color: #000; }

        /* Repeating kop/footer live in the page margin */
        .page-header { position: fixed; top: -134pt; left: 0; right: 0; height: 130pt; }
        .page-footer { position: fixed; bottom: -14pt; left: 0; right: 0; height: 12pt; }

        table.kop { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.kop td { border: 0.75pt solid #000; padding: 3pt 6pt; font-size: 8pt; vertical-align: middle; }
        .kop-logo { text-align: center; padding: 3pt; }
        .kop-logo img { width: 108pt; height: 108pt; }            /* square source (828x828), enlarged */
        .kop-logo-ph { font-weight: bold; font-size: 15pt; color: #c00; }
        .kop-title { text-align: center; font-weight: bold; font-size: 10.5pt; }
        .kop-subject { text-align: center; font-weight: bold; font-size: 9pt; }
        .kop-meta { font-size: 8pt; height: 18pt; }

        .doc-footer { font-size: 7pt; color: #2a5bd7; font-style: italic; }

        .section-bar { background: #EDEDED; border: 0.75pt solid #000; font-weight: bold; padding: 2.5pt 6pt; font-size: 8pt; margin-top: 7pt; }
        .section-box { border: 0.75pt solid #000; border-top: none; padding: 2pt 0; }

        table.list { width: 100%; border-collapse: collapse; }
        table.list td { padding: 1.5pt 6pt; vertical-align: top; font-size: 8pt; }
        table.list td.num { width: 34pt; white-space: nowrap; }

        table.akt { width: 100%; border-collapse: collapse; }
        table.akt th, table.akt td { border: 0.75pt solid #000; padding: 3pt 6pt; font-size: 8pt; vertical-align: top; }
        table.akt th { background: #EDEDED; text-align: center; }
        table.akt td.pic { text-align: center; }
        .akt-num { font-weight: bold; padding-right: 3pt; }

        .pengesahan-page { page-break-before: always; }
        table.pengesahan { width: 100%; border-collapse: collapse; }
        table.pengesahan th, table.pengesahan td { border: 0.75pt solid #000; padding: 5pt 6pt; font-size: 8pt; }
        table.pengesahan th { background: #EDEDED; text-align: center; }
        .center { text-align: center; }
        .peng-role { font-size: 7pt; color: #444; }
        .stamp { display: inline-block; border: 1.5pt solid #1a7f37; color: #1a7f37; font-weight: bold; padding: 2pt 9pt; font-size: 10pt; letter-spacing: 1px; }

        .lampiran-judul { font-weight: bold; }
        .lampiran-img { max-width: 240pt; max-height: 190pt; margin-top: 3pt; }
    </style>
</head>
<body>
    <div class="page-header">@include('documents.print._kop')</div>
    <div class="page-footer">@include('documents.print._footer')</div>

    <main>
        @foreach ($schema->allSections() as $section)
            @php
                $type = $section['type'] ?? 'text';
                $val = $contentMap[$section['key']] ?? null;
            @endphp

            @if (in_array($type, ['rich_list', 'reference_picker']))
                <div class="section-bar">{{ $section['label'] }}</div>
                <div class="section-box">
                    <table class="list">
                        @foreach ((is_array($val) ? $val : []) as $i => $item)
                            <tr>
                                <td class="num">{{ ($section['auto_number'] ?? '') }}{{ $i + 1 }}</td>
                                <td>{{ $item }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>

            @elseif ($type === 'repeatable_group')
                @php $fields = collect($section['group_fields'] ?? $section['fields'] ?? []); $hasPic = $fields->contains('key', 'pic'); @endphp
                <div class="section-bar">{{ $section['label'] }}</div>

                @if ($hasPic)
                    {{-- Aktivitas: AKTIVITAS (75%) | PIC (25%) --}}
                    <table class="akt">
                        <colgroup><col style="width:75%"><col style="width:25%"></colgroup>
                        <thead><tr><th>AKTIVITAS</th><th>PIC</th></tr></thead>
                        <tbody>
                            @foreach ((is_array($val) ? $val : []) as $i => $row)
                                <tr>
                                    <td>
                                        <span class="akt-num">{{ ($section['auto_number'] ?? '') }}{{ $i + 1 }}</span>
                                        <strong>{{ $row['sub_judul'] ?? '' }}</strong><br>
                                        {!! nl2br(e($row['deskripsi'] ?? '')) !!}
                                    </td>
                                    <td class="pic">{{ $row['pic'] ?? '' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    {{-- Lampiran: numbered list, isi teks atau gambar --}}
                    <div class="section-box">
                        <table class="list">
                            @foreach ((is_array($val) ? $val : []) as $i => $row)
                                <tr>
                                    <td class="num">{{ $i + 1 }}.</td>
                                    <td>
                                        <span class="lampiran-judul">{{ $row['judul'] ?? '' }}</span>
                                        @php $isi = $row['isi'] ?? ''; @endphp
                                        @if (is_string($isi) && str_starts_with($isi, 'lampiran/'))
                                            <br><img class="lampiran-img" src="{{ $embed($isi) }}" alt="lampiran">
                                        @elseif ($isi)
                                            <br>{{ $isi }}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                @endif
            @endif
        @endforeach

        <div class="pengesahan-page">
            @include('documents.print._pengesahan')
        </div>
    </main>
</body>
</html>
