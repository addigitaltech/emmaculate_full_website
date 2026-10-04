@props(['name'])
@php
    $brand = in_array($name, ['facebook', 'twitter', 'instagram', 'youtube', 'whatsapp', 'tiktok', 'linkedin'], true);
@endphp
<svg {{ $attributes->class(['icon', 'icon--fill' => $brand]) }} aria-hidden="true" focusable="false"><use href="#i-{{ $name }}"></use></svg>
