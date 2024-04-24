<div class="mt-1">
    @if ($attempt === 0)
        {{ $time }} | Waiting for updates
    @else
        {{ $time }} | Waiting for updates (attempt: {{ $attempt }})
    @endif
</div>
