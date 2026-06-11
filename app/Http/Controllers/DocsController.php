<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class DocsController extends Controller
{
    public function webhooks(): View
    {
        return view('docs.webhooks');
    }
}
