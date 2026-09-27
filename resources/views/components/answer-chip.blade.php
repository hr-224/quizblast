@props(['text', 'correct' => false])
<span {{ $attributes->merge(['class' => 'ans-chip' . ($correct ? ' is-correct' : '')]) }}>@if($correct)<span aria-hidden="true">✓</span> @endif{{ $text }}</span>
