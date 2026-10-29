@props(['employee'])
@php
    $label = $employee->contractReminder();
    $days = $employee->contractDaysLeft();
    $color = match (true) {
        $days === null => null,
        $days < 0 => 'red',
        $days <= 30 => 'orange',
        $days <= 60 => 'amber',
        $days <= 90 => 'blue',
        default => null,
    };
@endphp
@if ($label)
    <x-badge :color="$color">{{ $label }}</x-badge>
@endif
