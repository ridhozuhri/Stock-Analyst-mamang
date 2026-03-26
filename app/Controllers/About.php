<?php

declare(strict_types=1);

namespace App\Controllers;

final class About extends BaseController
{
    public function index()
    {
        return view('about', [
            'title' => 'About',
        ]);
    }
}

