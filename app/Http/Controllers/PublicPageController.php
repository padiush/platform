<?php

namespace App\Http\Controllers;

use App\Notifications\ContactFormNotification;
use Artesaos\SEOTools\Facades\SEOTools;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Spatie\Honeypot\Honeypot;

class PublicPageController extends Controller
{
    public function index()
    {
        SEOTools::setTitle('Padiush | Del cuaderno de campo a datos listos para publicar');
        SEOTools::setDescription('Plataforma de investigación etnobotánica: entrevistas en campo sin señal, reconciliación de nombres locales con taxones aceptados e índices cuantitativos con sus fórmulas citadas.');
        SEOTools::opengraph()->setUrl(config('app.url'));
        SEOTools::setCanonical(config('app.url'));

        // The landing page draws its own visuals in markup, so it no longer
        // pulls a set of presigned image URLs that nothing could cache.
        return Inertia::render('Public/Index');
    }

    public function about()
    {
        SEOTools::setTitle('Sobre nosotros');
        SEOTools::setDescription('Por qué construimos Padiush, cómo trabajamos y quiénes lo hacemos.');
        SEOTools::opengraph()->setUrl(config('app.url').'/acerca');
        SEOTools::setCanonical(config('app.url').'/acerca');

        return Inertia::render('Public/About', [
            'images' => [
                'mercedes' => Storage::disk('s3')->temporaryUrl('Mercedes.webp', now()->addMinutes(10)),
                'rodrigo' => Storage::disk('s3')->temporaryUrl('Rodrigo.webp', now()->addMinutes(10)),
            ],
        ]);
    }

    public function contact(Honeypot $honeypot)
    {
        SEOTools::setTitle('Contacto');
        SEOTools::setDescription('Contáctanos para cualquier duda o sugerencia.');
        SEOTools::opengraph()->setUrl(config('app.url').'/contacto');
        SEOTools::setCanonical(config('app.url').'/contacto');

        return Inertia::render('Public/Contact', [
            'honeypot' => $honeypot,
        ]);
    }

    public function handleContactRequest(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'message' => 'required|string',
        ]);

        Notification::route('mail', config('padiush.contact_email'))->notify(new ContactFormNotification($request->name, $request->email, $request->message));

        return redirect()
            ->route('public.contact')
            ->with('message', 'public.contact_success')
            ->with('message_type', 'success');
    }

    public function privacy()
    {
        SEOTools::setTitle('Política de Privacidad');
        SEOTools::setDescription('Conoce la Política de Privacidad de Padiush. Esta Política te explicará cómo recopilamos, usamos, protegemos y gestionamos tus datos.');
        SEOTools::opengraph()->setUrl(config('app.url').'/privacidad');
        SEOTools::setCanonical(config('app.url').'/privacidad');

        return Inertia::render('Public/Privacy');
    }

    public function terms()
    {
        SEOTools::setTitle('Términos y Condiciones');
        SEOTools::setDescription('Conoce los Términos y Condiciones de Padiush. Estos Términos te explicarán cómo puedes usar nuestra plataforma.');
        SEOTools::opengraph()->setUrl(config('app.url').'/terminos');
        SEOTools::setCanonical(config('app.url').'/terminos');

        return Inertia::render('Public/Terms');
    }
}
