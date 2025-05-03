<div {{ $attributes->merge(['class' => 'card w-full bg-base-200 shadow-xl text-base-content'])}}>
    @isset($image)
    <figure><img src="{!! $image !!}" /></figure>
    @endisset
    <div class="card-body">
        @isset($title)
        <h2 class="card-title">{{ $title }}</h2>
        @endisset
        {{ $slot }}
    </div>
</div>