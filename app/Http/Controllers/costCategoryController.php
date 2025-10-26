<?php

namespace App\Http\Controllers;

use App\Models\CostCategory as ModelsCostCategory;
use Illuminate\Http\Request;

class costCategoryController extends Controller
{
    //

    public function show()
    {
        $cost_categories = ModelsCostCategory::where('deleted_at', null)->get();

        return view('pages.costcategory', compact('cost_categories'));
    }
}
