<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class SubcategoryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Subcategories/Index');
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Subcategories/Create');
    }

    public function show(string $id): Response
    {
        return Inertia::render('Admin/Subcategories/Show', ['subcategoryId' => (int) $id]);
    }

    public function edit(string $id): Response
    {
        return Inertia::render('Admin/Subcategories/Edit', ['subcategoryId' => (int) $id]);
    }
}
