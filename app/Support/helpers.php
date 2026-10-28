<?php

if (! function_exists('form_value')) {
    /**
     * Nilai field: old input bila form yang sama baru saja gagal validasi, selain itu nilai default.
     * Menghindari old input satu form "bocor" ke form lain pada halaman yang sama.
     */
    function form_value(?string $form, string $name, mixed $default = null): mixed
    {
        if (session()->hasOldInput() && old('_form') === $form) {
            return old($name);
        }

        return $default instanceof DateTimeInterface ? $default->format('Y-m-d') : $default;
    }
}

if (! function_exists('form_error')) {
    function form_error(?string $form, string $name): ?string
    {
        $errors = session('errors');
        if (! $errors) {
            return null;
        }

        return $errors->getBag($form ?? 'default')->first($name) ?: null;
    }
}
