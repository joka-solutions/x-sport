<?php

namespace App\Http\Controllers\Acadimes;

use App\Http\Controllers\Controller;
use App\Models\Acadime;
use Illuminate\Http\Request;

class AcadimeController extends Controller
{
    public function get_acadimes(){
        $acadimes = Acadime::all();
        return response()->json($acadimes);
    }
}
