<x-app-layout>
    <x-slot name="action">
        <a class="btn btn-ghost btn-circle" href="{{ route('catalogs.index') }}">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
    </x-slot>
    <x-slot name="header"><span class="italic">{{ $species->genus }} {{ $species->name }}</span> {{ $species->authority }}</x-slot>
    <x-slot name="subtitle">Catálogo etnobotánico de {{ $project->name }}</x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 w-full">
        <x-card class="lg:col-span-2">
            @if(count($species->photos))
            <div class="hidden md:flex md:justify-center">
                <div class="w-full">
                    <div class="swiper">
                        <div class="swiper-wrapper">
                            @foreach ($species->photos as $photo)
                            <div class="swiper-slide">
                                <img src="{{ Storage::disk('s3')->temporaryUrl('/public/images/species/' . $photo->name, now()->addMinutes(5)) }}" alt="">
                                <a href="{{ route('catalog.species.photo.delete', ['species' => $species, 'photo' => $photo]) }}" onclick="return confirm_delete()" class="btn btn-error w-full">
                                    Eliminar foto
                                </a>
                            </div>
                            @endforeach
                        </div>

                        <div class="swiper-button-prev"></div>
                        <div class="swiper-button-next"></div>
                    </div>

                </div>
            </div>

            <div class="flex justify-center md:hidden">
                <div class="w-full">
                    <div class="swiper-small">
                        <!-- Additional required wrapper -->
                        <div class="swiper-wrapper">
                            @foreach ($species->photos as $photo)
                            <div class="swiper-slide">
                                <img src="{{ App::environment() }}/storage/images/species/{{ $photo->name }}" alt="">
                                <a href="{{ route('catalog.species.photo.delete', ['species' => $species, 'photo' => $photo]) }}" onclick="return confirm_delete()" class="btn btn-error w-full">
                                    Eliminar foto
                                </a>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            @else
            <div class="text-center">
                <span class="text-2xl">No hay fotos subidas para esta especie</span>
            </div>
            @endif
        </x-card>
        <x-card>
            <h2 class="card-title">Subir fotografía</h2>
            <input type="file" class="file-input file-input-primary file-input-bordered w-full max-w-xs" />
            <div class="divider"></div>
            <h2 class="card-title">Referencias en línea</h2>
            <a href="http://www.worldfloraonline.org/search?query={{ $species->genus }}+{{ strtolower($species->name) }}" target="_blank" class="btn btn-primary">
                WorldFloraOnline
            </a>
            <a href="https://www.tropicos.org/name/Search?name={{ htmlentities($species->genus . " " . $species->name) }}" target="_blank" class="btn btn-primary">
                Tropicos
            </a>
            <a href="https://www.gbif.org/species/search?q={{ htmlentities($species->genus . " " . $species->name) }}" target="_blank" class="btn btn-primary">
                GBIF
            </a>
        </x-card>
    </div>
</x-app-layout>