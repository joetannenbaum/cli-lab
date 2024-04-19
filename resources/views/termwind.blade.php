<div class="w-full">
    <div class="w-full">
        @foreach ($prompt->nav as $i => $nav)
        <span class="{{ $i === $prompt->selected ? 'bg-red-500' : null }}">
            {{ $nav }}
        </span>
        @endforeach
    </div>
    <div>
        {{ $prompt->content[$prompt->selected]}}
    </div>
</div>