@if (session('success'))
    <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800" role="status">{{ session('success') }}</div>
@endif
@if (session('warning'))
    <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800" role="status">
        @foreach ((array) session('warning') as $w)<div>{{ $w }}</div>@endforeach
    </div>
@endif
@if (session('error'))
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ session('error') }}</div>
@endif
@if (session('credential'))
    @php $c = session('credential'); @endphp
    <div class="mb-4 rounded-lg border border-brand-100 bg-brand-50 px-4 py-3 text-sm text-brand-800" role="status">
        <p class="font-semibold">Kredensial awal untuk {{ $c['username'] }}</p>
        @if ($c['show'])
            <p class="mt-1">Password sementara: <code class="rounded bg-white px-2 py-0.5 font-mono text-base">{{ $c['password'] }}</code></p>
        @endif
        <p class="mt-1">{{ $c['note'] }} Pengguna wajib mengganti password saat login pertama.</p>
    </div>
@endif
