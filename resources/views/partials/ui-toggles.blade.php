@php $mute = $mute ?? false; @endphp
<div class="ui-toggles">
  <button type="button" class="ui-toggle" data-fx-toggle aria-pressed="true" aria-label="Party effects" title="Party effects on. Switch to calm mode.">&#x2728;</button>
  @if($mute)
    <button type="button" class="ui-toggle" data-mute-toggle aria-pressed="true" aria-label="Sound" title="Sound is on. Mute.">&#x1F50A;</button>
  @endif
</div>
