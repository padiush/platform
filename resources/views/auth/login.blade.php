@php
$url = Storage::disk('s3')->temporaryUrl('public/bg.jpg', now()->addMinutes(5));
@endphp

<x-guest-layout>
    <section class="min-h-screen flex items-stretch text-base-content">
        <div class="lg:flex w-1/2 hidden bg-base-100 bg-no-repeat bg-cover relative items-center" style="background-image: url({{ $url }});">
            <div class="absolute bg-black opacity-60 inset-0 z-0"></div>
            <div class="w-full px-24 z-10">
                <h1 class="text-5xl font-bold text-left tracking-wide">Padiush</h1>
                <p class="text-3xl my-4">Sistema bioinformático que facilita la recolección y análisis de datos etnobotánicos</p>
            </div>
        </div>
        <div class="lg:w-1/2 w-full flex items-center justify-center text-center md:px-16 px-0 z-0 bg-base-100">
            <div class="absolute lg:hidden z-10 inset-0 bg-black bg-no-repeat bg-cover items-center" style="background-image: url({{ $url }});">
                <div class="absolute bg-base-100 opacity-60 inset-0 z-0"></div>
            </div>
            <div class="w-full py-6 z-20">
                <div class="flex justify-center">
                    <x-application-isotype class="w-48 pb-4"/>
                </div>
                <p class="text-base-content">
                    Ingresa tus credenciales a continuación
                </p>
                <form method="POST" action="{{ route('login') }}" class="sm:w-2/3 w-full px-4 lg:px-0 mx-auto pt-4">
                    @csrf
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Correo electrónico</span>
                        </label>
                        <input type="email" name="email" id="email" class="input input-bordered w-full">
                    </div>
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Contraseña</span>
                        </label>
                        <input class="input input-bordered w-full" type="password" name="password" id="password">
                    </div>
                    <div class="form-control">
                        <label class="label cursor-pointer">
                            <span class="label-text">Mantener mi sesión activa en este navegador</span>
                            <input id="remember_me" type="checkbox" class="checkbox checkbox-primary" name="remember">
                        </label>
                    </div>
                    <div class="px-4 pb-2 pt-4">
                        <button class="btn btn-primary">Iniciar sesión</button>
                    </div>
                </form>
                @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="link link-hover text-sm">¿Olvidaste tu contraseña?</a>
                @endif
            </div>
        </div>
    </section>
</x-guest-layout>