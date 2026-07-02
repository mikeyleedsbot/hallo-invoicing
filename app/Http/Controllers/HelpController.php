<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HelpController extends Controller
{
    public function index()
    {
        return view('help.index');
    }

    public function show(string $topic)
    {
        $validTopics = ['dashboard', 'facturen', 'offertes', 'klanten', 'btw-tarieven'];

        if (!in_array($topic, $validTopics)) {
            abort(404);
        }

        return view('help.' . str_replace('-', '_', $topic));
    }
}
