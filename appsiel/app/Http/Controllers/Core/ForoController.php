<?php

namespace App\Http\Controllers\Core;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

class ForoController extends Controller
{
    //index
    public function index($curso, $asignatura, $periodo)
    {
        dd($curso);
    }
}
