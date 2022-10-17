<div {{ $attributes->merge(['class' => 'card w-full bg-base-100 shadow-xl text-base-content overflow-x-auto self-start'])}}>
    <div class="card-body">
        @isset($title)
        <h2 class="card-title">{{ $title ?? ''}}</h2>
        @endisset
        {{ $slot }}
    </div>
</div>