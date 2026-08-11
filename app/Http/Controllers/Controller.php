<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    /**
     * Enables $this->authorize() so per-record authorization runs through
     * Policies rather than being hand-rolled in each controller (decision D3).
     */
    use AuthorizesRequests;
}
