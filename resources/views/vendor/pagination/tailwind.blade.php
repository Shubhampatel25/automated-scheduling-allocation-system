@if ($paginator->hasPages())
<div style="display:flex;align-items:center;justify-content:space-between;margin-top:16px;font-size:0.85rem;color:#6b7280;">

    <div>
        Showing {{ $paginator->firstItem() }} to {{ $paginator->lastItem() }} of {{ $paginator->total() }} entries
    </div>

    <div style="display:flex;align-items:center;gap:4px;">

        {{-- Previous Page --}}
        @if ($paginator->onFirstPage())
            <span style="padding:6px 12px;border:1px solid #e5e7eb;border-radius:6px;color:#d1d5db;cursor:default;">Prev</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" style="padding:6px 12px;border:1px solid #e5e7eb;border-radius:6px;color:#374151;text-decoration:none;background:#fff;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='#fff'">Prev</a>
        @endif

        {{-- Page Numbers --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <span style="padding:6px 10px;color:#9ca3af;">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span style="padding:6px 12px;border:1px solid #6366f1;border-radius:6px;background:#6366f1;color:#fff;font-weight:600;">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" style="padding:6px 12px;border:1px solid #e5e7eb;border-radius:6px;color:#374151;text-decoration:none;background:#fff;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='#fff'">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next Page --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" style="padding:6px 12px;border:1px solid #e5e7eb;border-radius:6px;color:#374151;text-decoration:none;background:#fff;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='#fff'">Next</a>
        @else
            <span style="padding:6px 12px;border:1px solid #e5e7eb;border-radius:6px;color:#d1d5db;cursor:default;">Next</span>
        @endif

    </div>
</div>
@endif
