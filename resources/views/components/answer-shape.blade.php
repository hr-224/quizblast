@props(['index' => 0])
@php
    $shapes = [
        'M12 2l2.9 6.6 7.1.6-5.4 4.7 1.6 7L12 17.3 5.8 20.9l1.6-7L2 9.2l7.1-.6z',
        'M12 2l8.7 5v10L12 22l-8.7-5V7z',
        'M9 2h6v7h7v6h-7v7H9v-7H2V9h7z',
        'M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z',
    ];
    $d = $shapes[((int) $index) % 4];
@endphp
<svg class="ans-shape" viewBox="0 0 24 24" aria-hidden="true" focusable="false" {{ $attributes }}><path d="{{ $d }}" fill="currentColor"/></svg>
