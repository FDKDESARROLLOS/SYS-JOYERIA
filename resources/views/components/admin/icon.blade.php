@props(['name'])

<svg {{ $attributes->merge(['class' => 'h-4 w-4 shrink-0']) }} viewBox="0 0 24 24" fill="none"
    stroke="currentColor" stroke-width="{{ $name === 'trash' ? '1.8' : '1.5' }}" aria-hidden="true">
    @switch($name)
        @case('dashboard')
            <rect x="4" y="4" width="6" height="6" rx="1" />
            <rect x="14" y="4" width="6" height="6" rx="1" />
            <rect x="4" y="14" width="6" height="6" rx="1" />
            <rect x="14" y="14" width="6" height="6" rx="1" />
        @break

        @case('products')
            <path d="M6 8.5 12 4l6 4.5-6 11-6-11Z" />
            <path d="m6 8.5 6 2.5 6-2.5M12 11v8.5" />
        @break

        @case('categories')
            <path d="M5 5h5v5H5zM14 5h5v5h-5zM5 14h5v5H5zM14 14h5v5h-5z" />
        @break

        @case('collections')
            <path d="M6 4h12v16H6zM9 8h6M9 12h6M9 16h4" />
        @break

        @case('materials')
            <path d="m12 4 7 4v8l-7 4-7-4V8l7-4Z" />
            <path d="m5 8 7 4 7-4M12 12v8" />
        @break

        @case('stones')
            <path d="m12 3 7 6-7 12L5 9l7-6Z" />
            <path d="m5 9 7 3 7-3M12 12v9" />
        @break

        @case('sizes')
            <path d="M5 7v10M19 7v10M5 12h14M8 9v6M16 9v6" />
        @break

        @case('variants')
            <path d="M5 7h14M5 12h14M5 17h14" />
            <circle cx="9" cy="7" r="1.5" />
            <circle cx="15" cy="12" r="1.5" />
            <circle cx="11" cy="17" r="1.5" />
        @break

        @case('orders')
            <path d="M5 6h14v14H5zM8 4v4M16 4v4M5 10h14" />
        @break

        @case('inventory')
            <path d="M4 7h16v13H4zM8 4h8l2 3H6l2-3ZM8 12h8" />
        @break

        @case('movements')
            <path d="M5 7h11l-2.5-2.5M19 17H8l2.5 2.5M16 4l3 3-3 3M8 14l-3 3 3 3" />
        @break

        @case('store')
            <path d="M4 7h16v13H4zM7 7V5h10v2M8 11h8M8 15h5" />
        @break

        @case('payments')
            <rect x="4" y="6" width="16" height="12" rx="1" />
            <path d="M4 10h16M8 14h3" />
        @break

        @case('shipping')
            <path d="M3 6h11v10H3zM14 10h4l3 3v3h-7zM7 19h.01M18 19h.01" />
        @break

        @case('system-errors')
            <path d="M12 4 3.5 19h17L12 4Z" />
            <path d="M12 9v4M12 16v.01" />
        @break

        @case('users')
            <circle cx="12" cy="8" r="3" />
            <path d="M5 20a7 7 0 0 1 14 0" />
        @break

        @case('trash')
            <g stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 7h16" />
                <path d="M9 7V4h6v3" />
                <path d="M6 7l1 13h10l1-13" />
                <path d="M10 11v5M14 11v5" />
            </g>
        @break

        @case('eye')
            <path d="M2.5 12s3.5-5 9.5-5 9.5 5 9.5 5-3.5 5-9.5 5-9.5-5-9.5-5Z" />
            <circle cx="12" cy="12" r="2.5" />
        @break

        @case('eye-off')
            <path d="m3 3 18 18M10.6 6.2A10.7 10.7 0 0 1 12 6c6 0 9.5 6 9.5 6a16.6 16.6 0 0 1-3.1 3.5M6.2 6.3C3.9 7.8 2.5 10 2.5 10s3.5 6 9.5 6c.8 0 1.6-.1 2.3-.3" />
            <path d="M9.9 9.9a2.5 2.5 0 0 0 3.5 3.5" />
        @break

        @case('close')
            <path d="m6 6 12 12M18 6 6 18" />
        @break
    @endswitch
</svg>
