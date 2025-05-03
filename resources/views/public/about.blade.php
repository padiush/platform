<x-public-layout>
    <div class="py-12 bg-base-300 text-base-content">
        <h1 class="text-3xl md:text-5xl font-bold text-center">Sobre nosotros</h1>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2">
        <div>
            <img src="{{ Storage::disk('s3')->temporaryUrl('about1.webp', now()->addMinutes(10)) }}" alt="" class="h-full w-full object-cover">
        </div>
        <div class="p-12 bg-white text-black">
            <div class="flex justify-center pb-12 text-primary">
                <x-application-full-logo class="block h-auto w-[80vw] md:w-[50vw] lg:w-[25vw] fill-current" />
            </div>
            <div class="text-center text-2xl font-bold">
                <p>¡Bienvenido a Padiush!</p>
            </div>
            <div class="text-justify lg:text-xl">
                <p>Una plataforma de software con la meta de agilizar la investigación etnobotánica. Desde 2021, nos esforzamos por brindar herramientas que simplifican la recolección y el análisis de datos a los investigadores.</p>
            </div>
        </div>
    </div>

    @if(false)
    <div class="p-12">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
            <div>
                <span class="text-2xl md:text-3xl">Misión</span>
                <div>
                    Facilitar las investigaciones científicas en el campo de la etnobotánica creando herramientas de software accesibles, colaborativas e intuitivas.
                </div>
            </div>
            <div>
                <span class="text-2xl md:text-3xl">Visión</span>
                <div>
                    Crear una plataforma que transforme la forma en que los investigadores recolectan, analizan y comparten sus datos, fortaleciendo la conservación de los conocimientos tradicionales en la región.                    
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="p-12">
        <div class="text-center text-2xl md:text-3xl">
            ¿Por qué <span class="italic">Padiush</span>?
        </div>
        <div class="flex justify-center">
            <div class="text-justify lg:max-w-[50vw]">
                <p class="pt-4">
                    El nombre <span class="italic">Padiush</span>, que en náhuat significa 'gracias', sirve como constante recordatorio de la profunda gratitud que debemos hacia las comunidades que nos comparten su sabiduría. Este agradecimiento trasciende las meras palabras y se traduce en un compromiso para devolver y proteger dichos conocimientos de la explotación, asegurando su uso de manera respetuosa y sostenible.
                </p>
                <p class="pt-4">
                    Padiush es un proyecto que nace en el corazón de la historia salvadoreña, una que ha presenciado las terribles masacres de sus pueblos originarios en 1832 y 1932.  Nuestra visión se basa en fomentar el respeto y la valoración del conocimiento ancestral de nuestros pueblos. Creamos Padiush no solo como un recurso para facilitar la investigación etnobotánica, sino también como un enlace que conecta a investigadores y comunidades en un espíritu de reconocimiento y respeto mutuo.
                </p>
            </div>
        </div>
    </div>

    <div class="p-12 bg-primary text-primary-content">
        <div class="text-center">
            <span class="text-2xl md:text-3xl">Nuestro equipo</span>
        </div>
        <div class="flex justify-center pt-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-8 lg:gap-12 md:max-w-[70vw] lg:max-w-[50vw]">
                <div class="grid grid-cols-1 gap-4">
                    <img class="mask mask-circle w-full h-auto" src="{{ Storage::disk('s3')->temporaryUrl('Mercedes.webp', now()->addMinutes(10)) }}" />
                    <div class="text-center">
                        <span class="text-xl md:text-2xl">Mercedes Menéndez</span>
                        <div class="text-lg md:text-xl">Bióloga</div>
                        <div class="text-xl">
                            <a href="https://www.linkedin.com/in/mercedes-men%C3%A9ndez-5209381b9/" target="_blank" class="link link-hover">
                                <i class="fa-brands fa-linkedin"></i>
                            </a>
                            <a href="mailto:mercedes@padiushbio.com" target="_blank" class="link link-hover">
                                <i class="fa-solid fa-envelope"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-4">
                    <img class="mask mask-circle w-full h-auto" src="{{ Storage::disk('s3')->temporaryUrl('Rodrigo.webp', now()->addMinutes(10)) }}" />
                    <div class="text-center">
                        <span class="text-xl md:text-2xl">Rodrigo Arévalo</span>
                        <div class="text-lg md:text-xl">Desarrollador</div>
                        <div class="text-xl">
                            <a href="https://www.linkedin.com/in/raarevalo96/" target="_blank" class="link link-hover">
                                <i class="fa-brands fa-linkedin"></i>
                            </a>
                            <a href="https://github.com/raarevalo96" target="_blank" class="link link-hover">
                                <i class="fa-brands fa-github"></i>
                            </a>
                            <a href="mailto:rodrigo@padiushbio.com" target="_blank" class="link link-hover">
                                <i class="fa-solid fa-envelope"></i>	
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-public-layout>