<?php

namespace App\Http\Controllers;

use App\Notifications\ContactFormNotification;
use Artesaos\SEOTools\Facades\SEOTools;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Spatie\Honeypot\Honeypot;

class PublicPageController extends Controller
{
    /**
     * The landing and about pages are a separate site, so the root only
     * sends visitors into the application.
     */
    public function root(): RedirectResponse
    {
        return redirect()->route(auth()->check() ? 'dashboard' : 'login');
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

    /**
     * A legal document is only servable if this deployment wrote one. They are
     * deployment configuration, not source — the repository ships none, so
     * without this guard a fork would render its own chrome around an empty
     * body, and a privacy policy that says nothing is worse than an honest 404.
     */
    private function abortUnlessLegalDocumentsPublished(): void
    {
        $path = public_path(config('padiush.legal_documents_path'));

        abort_if(glob($path.'/*.json') === [], 404);
    }

    public function privacy()
    {
        $this->abortUnlessLegalDocumentsPublished();

        SEOTools::setTitle('Política de Privacidad');
        SEOTools::setDescription('Conoce la Política de Privacidad de Padiush. Esta Política te explicará cómo recopilamos, usamos, protegemos y gestionamos tus datos.');
        SEOTools::opengraph()->setUrl(config('app.url').'/privacidad');
        SEOTools::setCanonical(config('app.url').'/privacidad');

        return Inertia::render('Public/Privacy');
    }

    public function terms()
    {
        $this->abortUnlessLegalDocumentsPublished();

        SEOTools::setTitle('Términos y Condiciones');
        SEOTools::setDescription('Conoce los Términos y Condiciones de Padiush. Estos Términos te explicarán cómo puedes usar nuestra plataforma.');
        SEOTools::opengraph()->setUrl(config('app.url').'/terminos');
        SEOTools::setCanonical(config('app.url').'/terminos');

        return Inertia::render('Public/Terms');
    }
}
