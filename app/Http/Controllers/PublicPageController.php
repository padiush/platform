<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Artesaos\SEOTools\Facades\SEOTools;
use Illuminate\Support\Facades\Notification;

use App\Notifications\ContactFormNotification;

class PublicPageController extends Controller
{
    public function index(){
        SEOTools::setTitle('Padiush | Uniendo Saberes: Sistematizando el Conocimiento Ancestral');
        SEOTools::setDescription('Simplifica la recolección y el análisis de tus datos con nuestra plataforma intuitiva y personalizable.');
        SEOTools::opengraph()->setUrl(env('APP_URL'));
        SEOTools::setCanonical(env('APP_URL'));

        return view('public.index');
    }

    public function about(){
        SEOTools::setTitle('Sobre nosotros');
        SEOTools::setDescription('Conoce más sobre el equipo detrás de Padiush.');
        SEOTools::opengraph()->setUrl(env('APP_URL').'/acerca');
        SEOTools::setCanonical(env('APP_URL').'/acerca');

        return view('public.about');
    }

    public function contact(){
        SEOTools::setTitle('Contacto');
        SEOTools::setDescription('Contáctanos para cualquier duda o sugerencia.');
        SEOTools::opengraph()->setUrl(env('APP_URL').'/contacto');
        SEOTools::setCanonical(env('APP_URL').'/contacto');

        return view('public.contact');
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

        return view('public.privacy');
    }

    public function terms(){
        SEOTools::setTitle('Términos y Condiciones');
        SEOTools::setDescription('Conoce los Términos y Condiciones de Padiush. Estos Términos te explicarán cómo puedes usar nuestra plataforma.');
        SEOTools::opengraph()->setUrl(env('APP_URL').'/terminos');
        SEOTools::setCanonical(env('APP_URL').'/terminos');

        return view('public.terms');
    }
}
