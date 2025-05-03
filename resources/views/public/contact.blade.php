<x-public-layout>
    <div class="py-12 bg-base-300 text-base-content">
        <h1 class="text-3xl md:text-5xl font-bold text-center">Contáctanos</h1>
    </div>
    
    <div class="flex justify-center">
        <div class="px-4 py-4 md:w-[80vw] lg:w-[60vw]">
            <x-card>
                <div>
                    <h2 class="text-2xl font-bold">¿Tienes alguna duda?</h2>
                    <p class="text-base-content">Si tienes alguna duda o comentario, puedes contactarnos a través de este formulario.</p>
                </div>
                <form action="{{ route('public.contact.handle') }}" method="post">
                    @csrf
                    @honeypot
                    <div class="form-control w-full">
                        <label class="label">
                            <span class="label-text">Nombre</span>
                        </label>
                        <input type="text" name="name" class="input input-bordered w-full">
                    </div>
                    <div class="form-control w-full">
                        <label class="label">
                            <span class="label-text">Correo electrónico</span>
                        </label>
                        <input type="email" name="email" class="input input-bordered w-full">
                    </div>
                    <div class="form-control w-full">
                        <label class="label">
                            <span class="label-text">Mensaje</span>
                        </label>
                        <textarea type="text" name="message" class="textarea textarea-bordered w-full"></textarea>
                    </div>
                    <div class="pt-4">
                        <button type="submit" class="btn btn-primary">Enviar</button>
                    </div>
                </form>
            </x-card>
        </div>    
    </div>
</x-public-layout>