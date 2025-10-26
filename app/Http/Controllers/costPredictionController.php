<?php

namespace App\Http\Controllers;

use App\Models\FarmingOperation;
use Illuminate\Http\Request;

class costPredictionController extends Controller
{
    //

    public function index()
    {
        // operations
        $operations = FarmingOperation::where('deleted_at', null)->paginate(5);


        return view('pages.prediction_cost')->with(['operations' => $operations]);
    }

    public function showAnalisis()
    {

            $operations = FarmingOperation::where('deleted_at', null)->paginate(5);

            return view('pages.analyse_cost')->with(['operations' => $operations]);
    }
}
