<div class="flex">
    <span class="mr-1">
        {{ $time }}
        |
        <span class="mr-1">←</span>
        <b class="mr-1">Out:</b>
        <span class="text-blue-500">{{ $classNameLatestPart }}</span>
        (ID: {{ $message->getId() }})
        {{ $pageClassName }}
    </span>
    <span class="content-repeat-['.'] flex-1"></span>
    <span class="ml-1">
        to: {{ $to }}
    </span>
</div>
