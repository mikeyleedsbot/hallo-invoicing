@props(['column', 'label', 'sort' => null, 'direction' => 'asc'])

@php
    $isActive = $sort === $column;
    $nextDirection = $isActive && $direction === 'asc' ? 'desc' : 'asc';
    $params = array_merge(request()->query(), ['sort' => $column, 'direction' => $nextDirection]);
@endphp

<a href="{{ request()->url() . '?' . http_build_query($params) }}"
   class="group inline-flex items-center gap-1 hover:text-gray-900 dark:hover:text-white transition-colors">
    {{ $label }}
    <span class="flex-none">
        @if($isActive && $direction === 'asc')
            {{-- Pijl omhoog: oplopend --}}
            <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd"/>
            </svg>
        @elseif($isActive && $direction === 'desc')
            {{-- Pijl omlaag: aflopend --}}
            <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
            </svg>
        @else
            {{-- Neutrale dubbele pijl: niet gesorteerd, zichtbaar bij hover --}}
            <svg class="w-4 h-4 text-gray-400 dark:text-gray-500 opacity-0 group-hover:opacity-100 transition-opacity" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 3a.75.75 0 01.55.24l3.25 3.5a.75.75 0 11-1.1 1.02L10 4.852 7.3 7.76a.75.75 0 01-1.1-1.02l3.25-3.5A.75.75 0 0110 3zm-3.25 9.24a.75.75 0 011.1.02L10 15.148l2.7-2.908a.75.75 0 111.1 1.02l-3.25 3.5a.75.75 0 01-1.1 0l-3.25-3.5a.75.75 0 01.05-1.06z" clip-rule="evenodd"/>
            </svg>
        @endif
    </span>
</a>
