@props(['status'])
<x-badge :color="in_array($status, ['Aktif']) ? 'green' : 'slate'">{{ $status }}</x-badge>
