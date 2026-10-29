@props(['title', 'subtitle' => null])
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h1 class="text-xl font-bold text-slate-900 sm:text-2xl">{{ $title }}</h1>
        @if ($subtitle)<p class="mt-1 text-sm text-slate-500">{{ $subtitle }}</p>@endif
    </div>
    @if (isset($actions))
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endif
</div>
