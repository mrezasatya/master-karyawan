{{--
    Template CV Bukaka — dipakai untuk preview HTML dan PDF (dompdf).
    Ukuran & posisi diukur dari template resmi (letter 612×792pt, Arial/Helvetica):
    judul/heading 12pt bold, isi 10pt, tabel TRAINING 13,5pt. Data administrasi sensitif TIDAK pernah dicetak.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>CV {{ $e->nama }}</title>
<style>
    @page { margin: 45pt 48pt 45pt 43pt; }
    * { box-sizing: border-box; }
    body { font-family: Helvetica, Arial, sans-serif; font-size: 10pt; line-height: 13.5pt; color: #000; margin: 0; }
    table { border-collapse: collapse; }
    td, th { padding: 0; vertical-align: top; text-align: left; font-weight: normal; }
    tr { page-break-inside: avoid; }

    .header { position: relative; height: 64.5pt; }
    .logo { position: absolute; left: 4pt; top: 5pt; width: 137pt; }
    .title { position: absolute; left: 154pt; top: 16pt; font-size: 12pt; line-height: 14pt; font-weight: bold; white-space: nowrap; }
    .photo { position: absolute; right: 0; top: 0; width: 81pt; height: 103pt; }

    h2 { font-size: 12pt; line-height: 14pt; font-weight: bold; margin: 0; page-break-after: avoid; }
    .h-personal { margin: 0 0 0 2pt; }
    .personal { margin-top: 10.5pt; }
    .personal td { height: 13.5pt; }
    .personal .num { width: 11pt; }
    .personal .label { width: 124pt; }
    .personal .colon { width: 13.6pt; }

    .h-edu { margin: 21.2pt 0 0 1.4pt; }
    .edu { margin: 4pt 0 0 1.3pt; }
    .edu p { margin: 0; }

    .h-exp { margin: 8pt 0 0 0.2pt; }
    .exp { margin: 2pt 0 0 1.3pt; }
    .exp td { line-height: 14.1pt; }
    .exp .start { width: 35.1pt; white-space: nowrap; }
    .exp .sd { width: 21.9pt; }
    .exp .end { width: 46pt; white-space: nowrap; } /* cukup untuk "Sekarang" */
    .exp .resp { font-size: 9pt; line-height: 11.5pt; padding-bottom: 3pt; white-space: pre-line; }

    .training { width: 100%; margin: 3.8pt 0 0 -1.2pt; }
    .training th { font-size: 13.5pt; line-height: 16pt; }
    .training th.subject { font-size: 12pt; font-weight: bold; }
    .training .c-name { width: 210pt; padding-left: 1.9pt; }
    .training .c-year { width: 47pt; text-align: center; }
    .training .c-org { padding-left: 83pt; }
    .training th.c-org { padding-left: 91.4pt; }
    .training td { font-size: 13.5pt; line-height: 16pt; padding-top: 12.7pt; padding-bottom: 2.3pt; }
    .training td.c-year { padding-top: 15pt; padding-bottom: 0; }
    .training td.c-org { padding-top: 14.4pt; padding-bottom: 0.6pt; }
    .muted { color: #555; }

    @media screen {
        body { max-width: 612pt; margin: 0 auto; padding: 45pt 48pt 45pt 43pt; background: #fff; }
        html { background: #e2e8f0; }
    }
</style>
</head>
<body>
    <div class="header">
        <img src="{{ $logo }}" class="logo" alt="Bukaka">
        <div class="title">CURRICULUM VITAE OF PERSONNEL</div>
        @if ($photo)
            <img src="{{ $photo }}" class="photo" alt="">
        @endif
    </div>

    <h2 class="h-personal">PERSONAL</h2>
    <table class="personal">
        @foreach ($personal as $label => $value)
            <tr>
                <td class="num">{{ $loop->iteration }}.</td>
                <td class="label">{{ $label }}</td>
                <td class="colon">:</td>
                <td>{{ $value }}</td>
            </tr>
        @endforeach
    </table>

    <h2 class="h-edu">EDUCATION BACKGROUND</h2>
    <div class="edu">
        @forelse ($e->education as $ed)
            <p>{{ collect([$ed->graduation_year, $cvLevel($ed->level), $ed->major, $ed->institution])->filter(fn ($v) => filled($v))->join(' - ') }}</p>
        @empty
            <p class="muted">-</p>
        @endforelse
    </div>

    <h2 class="h-exp">WORKING EXPERIENCES AND JOB RESPONSIBILITY</h2>
    <table class="exp">
        @forelse ($e->experiences as $x)
            <tr>
                <td class="start">{{ \App\Support\Format::monthYear($x->start_month, $x->start_year) }}</td>
                <td class="sd">s/d</td>
                <td class="end">{{ $x->is_current ? 'Sekarang' : \App\Support\Format::monthYear($x->end_month, $x->end_year) }}</td>
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
            <tr><th class="c-name subject">TRAINING</th><th class="c-year">TAHUN</th><th class="c-org">PENYELENGGARA</th></tr>
        </thead>
        <tbody>
            @forelse ($e->trainings as $t)
                <tr>
                    <td class="c-name">{{ $t->item_name }}</td>
                    <td class="c-year">{{ $t->year }}</td>
                    <td class="c-org">{{ $t->organizer }}</td>
                </tr>
            @empty
                <tr><td class="c-name muted">-</td><td></td><td></td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
