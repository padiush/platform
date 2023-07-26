<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Artesaos\SEOTools\Facades\SEOTools;

class PublicPageController extends Controller
{
    public function index(){
        SEOTools::setTitle('Padiush | La Herramienta Definitiva para Investigaciones Etnobotánicas');
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
}
