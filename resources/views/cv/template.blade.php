{{-- Template CV Bukaka. Dipakai untuk preview HTML dan PDF (dompdf). Data administrasi sensitif TIDAK pernah dicetak. --}}
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>CV {{ $e->nama }}</title>
<style>
    @page { margin: 42pt 46pt 46pt 46pt; }
    * { box-sizing: border-box; }
    body { font-family: Helvetica, Arial, sans-serif; font-size: 9pt; color: #000; margin: 0; line-height: 1.3; }
    table { border-collapse: collapse; width: 100%; }
    td, th { padding: 0; vertical-align: top; text-align: left; }
    tr { page-break-inside: avoid; }
    .header td { vertical-align: top; }
    .logo { width: 140pt; }
    .title { font-weight: bold; font-size: 10.5pt; padding: 14pt 0 0 6pt; white-space: nowrap; }
    .photo-cell { width: 80pt; text-align: right; }
    .photo { width: 78pt; height: 104pt; object-fit: cover; }
    .photo-empty { width: 78pt; height: 104pt; }
    h2 { font-size: 10pt; font-weight: bold; margin: 16pt 0 6pt 0; page-break-after: avoid; }
    .personal td { padding: 1.5pt 0; }
    .personal .label { width: 135pt; padding-left: 0; }
    .personal .colon { width: 12pt; }
    .edu-line { margin: 0 0 3pt 0; }
    .exp td { padding: 2pt 0; }
    .exp .period { width: 92pt; white-space: nowrap; }
    .exp .resp { padding-top: 1pt; color: #222; white-space: pre-line; }
    .training { margin-top: 14pt; }
    .training th { font-weight: bold; font-size: 10pt; padding: 6pt 0 6pt 0; }
    .training td { padding: 4pt 0 8pt 0; font-size: 9.5pt; }
    .training .year { width: 90pt; text-align: center; }
    .training th.year { text-align: center; }
    .training .org { width: 170pt; }
    .muted { color: #555; }
    @media screen { body { max-width: 612pt; margin: 0 auto; padding: 42pt 46pt; } }
</style>
</head>
<body>
    <table class="header">
        <tr>
            <td style="width: 140pt;"><img src="{{ $logo }}" class="logo" alt="Bukaka"></td>
            <td class="title">CURRICULUM VITAE OF PERSONNEL</td>
            <td class="photo-cell">
                @if ($photo)
                    <img src="{{ $photo }}" class="photo" alt="">
                @else
                    <div class="photo-empty"></div>
                @endif
            </td>
        </tr>
    </table>

    <h2>PERSONAL</h2>
    <table class="personal">
        @foreach ($personal as $label => $value)
            <tr>
                <td class="label">{{ $loop->iteration }}. {{ $label }}</td>
                <td class="colon">:</td>
                <td>{{ $value }}</td>
            </tr>
        @endforeach
    </table>

    <h2>EDUCATION BACKGROUND</h2>
    @forelse ($e->education as $ed)
        <p class="edu-line">{{ collect([$ed->graduation_year, $ed->level, $ed->major, $ed->institution])->filter(fn ($v) => filled($v))->join(' - ') }}</p>
    @empty
        <p class="edu-line muted">-</p>
    @endforelse

    <h2>WORKING EXPERIENCES AND JOB RESPONSIBILITY</h2>
    <table class="exp">
        @forelse ($e->experiences as $x)
            <tr>
                <td class="period">{{ $x->periodLabel() }}</td>
                <td>
                    {{ $x->cvTitle() }}
                    @if ($x->responsibilities)
                        <div class="resp">{{ $x->responsibilities }}</div>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td class="muted">-</td></tr>
        @endforelse
    </table>

    <table class="training">
        <thead>
            <tr><th>TRAINING</th><th class="year">TAHUN</th><th class="org">PENYELENGGARA</th></tr>
        </thead>
        <tbody>
            @forelse ($e->trainings as $t)
                <tr>
                    <td>{{ $t->item_name }}</td>
                    <td class="year">{{ $t->year }}</td>
                    <td class="org">{{ $t->organizer }}</td>
                </tr>
            @empty
                <tr><td class="muted">-</td><td></td><td></td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
