<div>
    {{ $time }} | <span class="text-red-500">Error occurred</span>: {{ $exception->getMessage() }}
    <div>
        <pre>{{ $exception->getTraceAsString() }}</pre>
    </div>
</div>
