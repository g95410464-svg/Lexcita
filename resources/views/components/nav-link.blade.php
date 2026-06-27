@props(['href', 'active' => false, 'icon' => 'circle'])

<a href="{{ $href }}"
   class="flex items-center gap-3 px-6 py-3 text-[13px] font-grotesk font-semibold tracking-[.06em] uppercase transition-colors duration-150
          {{ $active
              ? 'border-l-2 border-secondary bg-[rgba(233,195,73,0.06)] text-secondary translate-x-0'
              : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high border-l-2 border-transparent' }}">
    <span class="material-symbols-outlined text-[18px]">{{ $icon }}</span>
    {{ $slot }}
</a>
