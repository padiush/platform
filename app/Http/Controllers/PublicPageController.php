<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Artesaos\SEOTools\Facades\SEOTools;

class PublicPageController extends Controller
{
    public function index(){

        SEOTools::setTitle('Inicio');

        return view('public.index');
    }
}
