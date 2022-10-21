<div class="navbar bg-primary text-primary-content sticky top-0 z-40">
    <div class="navbar-start">
        <div class="dropdown">
            <label tabindex="0" class="btn btn-ghost lg:hidden">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h8m-8 6h16" /></svg>
            </label>
            <ul tabindex="0" class="menu menu-compact dropdown-content mt-3 p-2 shadow bg-base-100 rounded-box w-52">
                <li><a href="{{ route('projects.index') }}">Proyectos</a></li>
                @if(Auth::user()->projectAccesses->count() > 0)
                <li tabindex="0">
                    <a>
                        Entrevistas
                        <i class="fa-solid fa-chevron-right text-xs"></i>
                    </a>
                    <ul class="p-2 bg-base-200">
                        <li><a href="{{ route('designer.index') }}">Diseñar</a></li>
                        <li><a>Entrevistar</a></li>
                    </ul>
                </li>
                <li><a href="#">Catálogos</a></li>
                <li><a href="#">Datos</a></li>
                @endif
                <li><a href="#">Comunidad</a></li>
            </ul>
        </div>
        <a class="btn btn-ghost normal-case text-xl" href="{{ route('dashboard') }}">
            <x-application-logo class="block h-10 w-auto fill-current" />
        </a>
    </div>
    <div class="navbar-center hidden lg:flex">
        <ul class="menu menu-horizontal p-0">
            <li><a href="{{ route('projects.index') }}">Proyectos</a></li>
            @if(Auth::user()->projectAccesses->count() > 0)
            <li tabindex="0">
                <a>
                    Entrevistas
                    <i class="fa-solid fa-chevron-down text-xs"></i>
                </a>
                <ul class="p-2 bg-base-100">
                    <li><a href="{{ route('designer.index') }}">Diseñar</a></li>
                    <li><a>Entrevistar</a></li>
                </ul>
            </li>
            <li><a href="#">Catálogos</a></li>
            <li><a href="#">Datos</a></li>
            @endif
            <li><a href="#">Comunidad</a></li>
        </ul>
    </div>
    <div class="navbar-end">
        <a class="btn btn-ghost btn-circle" data-toggle-theme="dark,light" data-act-class="ACTIVECLASS">
            <i class="fa-solid fa-lightbulb"></i>
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <a class="btn btn-ghost btn-circle" href="{{ route('logout') }}" onclick="event.preventDefault(); this.closest('form').submit();">
                <i class="fa-solid fa-person-walking-arrow-right"></i>
            </a>
        </form>
    </div>
</div>