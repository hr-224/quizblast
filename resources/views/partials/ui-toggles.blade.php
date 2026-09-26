@php $mute = $mute ?? false; @endphp
<div class="ui-toggles">
  <button type="button" class="ui-toggle" data-fx-toggle aria-pressed="true" aria-label="Party effects on. Switch to calm mode." title="Party effects on. Switch to calm mode.">&#x2728;</button>
  @if($mute)
    <button type="button" class="ui-toggle" data-mute-toggle aria-pressed="false" aria-label="Mute sound" title="Mute sound">&#x1F50A;</button>
  @endif
</div>
