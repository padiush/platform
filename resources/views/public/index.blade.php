<x-public-layout>
    <div class="hero min-h-[calc(100vh-4rem)]" style="background-image: url({{ Storage::disk('s3')->temporaryUrl('hero.jpg', now()->addMinutes(10)) }});">
        <div class="hero-overlay bg-opacity-60"></div>
        <div class="hero-content text-center text-neutral-content">
            <div class="max-w-md lg:max-w-[60vw]">
                <h1 class="mb-5 text-5xl font-bold">La Herramienta Definitiva para Investigaciones Etnobotánicas</h1>
                <p class="mb-5">Simplifica la recolección y el análisis de tus datos con nuestra plataforma intuitiva y personalizable.</p>
                @if(Auth::user())
                <a href="{{ route('dashboard') }}" class="btn btn-primary">Ingresar a la plataforma</a>
                @else
                <a href="{{ route('register') }}" class="btn btn-primary">¡Prueba Padiush Ahora!</a>
                @endif
            </div>
        </div>
    </div>
    <div class="p-12">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 w-full">
            <x-feature-card image="{{ Storage::disk('s3')->temporaryUrl('collab.png', now()->addMinutes(10)) }}" title="Ambiente colaborativo">
                Invita a varios investigadores a unirse a un proyecto con diferentes niveles de permisos. Selecciona entre administrador, usuario técnico o usuario solo lectura.
            </x-feature-card>
            <x-feature-card image="{{ Storage::disk('s3')->temporaryUrl('custom.png', now()->addMinutes(10)) }}" title="Entrevistas personalizables">
                Nuestro diseñador de entrevistas es sumamente flexible y se puede adaptar a cualquier metodología, con secciones únicas y repetitivas que pueden contener campos de texto, números, fechas, de selección única y de selección múltiple.
            </x-feature-card>
            <x-feature-card image="{{ Storage::disk('s3')->temporaryUrl('placeholder.png', now()->addMinutes(10)) }}" title="Entrevistas personalizables" title="Catálogo de especies">
                Añade tantas especies identificadas como necesites, con toda su información taxonómica (familia, género, nombre de la especie, variante y autoridad).
            </x-feature-card>
            <x-feature-card image="{{ Storage::disk('s3')->temporaryUrl('placeholder.png', now()->addMinutes(10)) }}" title="Entrevistas personalizables" title="Enlaza información">
                Enlaza rápida y fácilmente los datos de las entrevistas de campo (usos) a los datos taxonómicos del catálogo de especies.
            </x-feature-card>
            <x-feature-card image="{{ Storage::disk('s3')->temporaryUrl('placeholder.png', now()->addMinutes(10)) }}" title="Entrevistas personalizables" title="Exportación de datos">
                Los datos se pueden exportar directamente para usarse con el paquete ethnobotanyR, o se puede generar un informe personalizado de Excel en cualquier momento con todos los datos de investigación.
                <div class="card-actions justify-end">
                    <a class="btn btn-primary" href="https://cran.r-project.org/web/packages/ethnobotanyR/vignettes/ethnobotanyr_vignette.html">Conoce más sobre ethnobotanyR <i class="fa-solid fa-arrow-up-right-from-square pl-2"></i></a>
                </div>
            </x-feature-card>
            <x-feature-card image="{{ Storage::disk('s3')->temporaryUrl('placeholder.png', now()->addMinutes(10)) }}" title="Entrevistas personalizables" title="Comunidad">
                Si el autor principal lo permite al final de la investigación, una selección limitada de datos se puede compartir con la comunidad para ayudar a otros investigadores y generar discusión.
            </x-feature-card>
        </div>
    </div>
    <div class="bg-primary text=primary-content p-12">
        <div class="flex justify-center text-3xl font-bold">
            ¿Cómo funciona?
        </div>
        <div class="flex justify-center pt-4">
            <iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen class="w-full h-[40vh] lg:h-[calc(100vh-4rem)]"></iframe>
        </div>
    </div>
    @if(false)
    <div class="p-12">
        <div class="flex justify-center text-3xl font-bold">
            Testimonios
        </div>
    </div>
    <div class="p-12">
        <div class="flex justify-center text-3xl font-bold">
            Investigaciones destacadas
        </div>
    </div>
    @endif
</x-public-layout>