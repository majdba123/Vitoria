<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class CityController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Cities/Index');
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Cities/Create');
    }

    public function show(string $id): Response
    {
        return Inertia::render('Admin/Cities/Show', ['cityId' => (int) $id]);
    }

    public function edit(string $id): Response
    {
        return Inertia::render('Admin/Cities/Edit', ['cityId' => (int) $id]);
    }
}
