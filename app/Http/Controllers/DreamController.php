<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DreamController extends Controller
{
    // Ruya arayüzü fonksiyonu
    public function index()
    {
        return view('admin.dream.analyze');
    }
}
