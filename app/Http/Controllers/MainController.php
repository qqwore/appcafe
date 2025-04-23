<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MainController extends Controller
{
    public function index()
    {
        return Inertia::render('Welcome',[
            'products' => Product::where('type','Еда')->limit(4)->get(),
        ]);
    }
}
