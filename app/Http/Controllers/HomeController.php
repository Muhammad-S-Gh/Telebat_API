<?php

namespace App\Http\Controllers;

use App\Services\Home\HomeService;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __construct(private readonly HomeService $home) {}

    public function index(Request $request)
    {
        return success($this->home->dashboard($request->user()));
    }
}
