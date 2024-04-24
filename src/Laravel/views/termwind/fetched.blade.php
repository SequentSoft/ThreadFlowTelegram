<div>
    @if (! $isParsedOk)
        {{ $time }} | <span class="text-red-500">Received invalid payload</span>: <pre>{{ $payload }}</pre>
    @elseif (! $isOk)
        {{ $time }} | <span class="text-red-500">Received data with error</span>: <pre>{{ $payload }}</pre>
    @elseif (! $hasResult)
        {{ $time }} | <span>No updates received</span>
    @else
        <div class="flex">
            <span class="mr-1">{{ $time }} | <span class="text-green">✔</span> <span>Received updates: {{ $count }}</span></span>
            <span class="content-repeat-['.'] flex-1"></span>
            <span class="ml-1">{{ $size }} bytes</span>
        </div>
    @endif
</div>
