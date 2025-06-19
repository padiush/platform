<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Artesaos\SEOTools\Facades\SEOTools;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

use App\Notifications\ContactFormNotification;

class PublicPageController extends Controller
{
    public function index(){
        SEOTools::setTitle('Padiush | Uniendo Saberes: Sistematizando el Conocimiento Ancestral');
        SEOTools::setDescription('Simplifica la recolección y el análisis de tus datos con nuestra plataforma intuitiva y personalizable.');
        SEOTools::opengraph()->setUrl(env('APP_URL'));
        SEOTools::setCanonical(env('APP_URL'));

        return Inertia::render('Public/Index', [
            'images' => [
                'hero' => Storage::disk('s3')->temporaryUrl('hero.jpg', now()->addMinutes(10)),
                'collab' => Storage::disk('s3')->temporaryUrl('collab.png', now()->addMinutes(10)),
                'custom' => Storage::disk('s3')->temporaryUrl('custom.png', now()->addMinutes(10)),
                'catalog' => Storage::disk('s3')->temporaryUrl('catalog.png', now()->addMinutes(10)),
                'usage' => Storage::disk('s3')->temporaryUrl('usage.png', now()->addMinutes(10)),
                'data' => Storage::disk('s3')->temporaryUrl('data.png', now()->addMinutes(10)),
                'community' => Storage::disk('s3')->temporaryUrl('community.png', now()->addMinutes(10)),
            ],
        ]);
    }

    public function about(){
        SEOTools::setTitle('Sobre nosotros');
        SEOTools::setDescription('Conoce más sobre el equipo detrás de Padiush.');
        SEOTools::opengraph()->setUrl(env('APP_URL').'/acerca');
        SEOTools::setCanonical(env('APP_URL').'/acerca');

        return Inertia::render('Public/About', [
            'images' => [
                'about1' => Storage::disk('s3')->temporaryUrl('about1.webp', now()->addMinutes(10)),
                'mercedes' => Storage::disk('s3')->temporaryUrl('Mercedes.webp', now()->addMinutes(10)),
                'rodrigo' => Storage::disk('s3')->temporaryUrl('Rodrigo.webp', now()->addMinutes(10)),
            ],
        ]);
    }

    public function contact(){
        SEOTools::setTitle('Contacto');
        SEOTools::setDescription('Contáctanos para cualquier duda o sugerencia.');
        SEOTools::opengraph()->setUrl(env('APP_URL').'/contacto');
        SEOTools::setCanonical(env('APP_URL').'/contacto');

        return Inertia::render('Public/Contact');
    }

    public function handleContactRequest(Request $request){
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'message' => 'required|string'
        ]);

        Notification::route('mail', env('CONTACT_EMAIL'))->notify(new ContactFormNotification($request->name, $request->email, $request->message));

        return redirect()->route('public.contact')->with('success', 'Tu mensaje ha sido enviado. ¡Gracias por contactarnos!');
    }

    public function privacy(){
        SEOTools::setTitle('Política de Privacidad');
        SEOTools::setDescription('Conoce la Política de Privacidad de Padiush. Esta Política te explicará cómo recopilamos, usamos, protegemos y gestionamos tus datos.');
        SEOTools::opengraph()->setUrl(env('APP_URL').'/privacidad');
        SEOTools::setCanonical(env('APP_URL').'/privacidad');

        return Inertia::render('Public/Privacy', [
            'pageContent' => view('public.privacy')->render(),
        ]);
    }

    public function terms(){
        SEOTools::setTitle('Términos y Condiciones');
        SEOTools::setDescription('Conoce los Términos y Condiciones de Padiush. Estos Términos te explicarán cómo puedes usar nuestra plataforma.');
        SEOTools::opengraph()->setUrl(env('APP_URL').'/terminos');
        SEOTools::setCanonical(env('APP_URL').'/terminos');

        return Inertia::render('Public/Terms', [
            'pageContent' => view('public.terms')->render(),
        ]);
    }
}
