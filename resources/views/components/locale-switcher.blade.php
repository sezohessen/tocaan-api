@php
    $current = app()->getLocale();
@endphp

<div class="flex items-center gap-1 px-2">
    @foreach (['en' => 'EN', 'ar' => 'ع'] as $locale => $label)
        <a
            href="{{ request()->fullUrlWithQuery(['locale' => $locale]) }}"
            @class([
                'rounded-md px-2 py-1 text-sm font-medium transition',
                'bg-primary-600 text-white' => $current === $locale,
                'text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800' => $current !== $locale,
            ])
        >
            {{ $label }}
        </a>
    @endforeach
</div>
