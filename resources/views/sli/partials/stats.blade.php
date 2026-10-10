<div class="sli-stats {{ count($metrics) === 6 ? 'sli-stats-six' : '' }}">
@foreach($metrics as [$label, $value, $icon, $note])
    <div class="sli-stat"><p class="sli-stat-label"><span>{{ $label }}</span><span class="material-symbols-outlined" aria-hidden="true">{{ $icon }}</span></p><p class="sli-stat-value">{{ $value }}</p><p class="sli-stat-note">{{ $note }}</p></div>
@endforeach
</div>
