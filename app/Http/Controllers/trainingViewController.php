<?php

namespace App\Http\Controllers;

use App\Models\CostCategory;
use Illuminate\Http\Request;

class trainingViewController extends Controller
{



    public function showCategory()  {

        $categories =CostCategory::where('deleted_at',null)->paginate(5) ;


        return view('pages.categoryTraining')->with(['categories'=>$categories]);

    }
}
