<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories.
     */
    public function index(): Response
    {
        return Inertia::render('Admin/Categories/Index');
    }

    /**
     * Show the form for creating a new category.
     */
    public function create(): Response
    {
        return Inertia::render('Admin/Categories/Create');
    }

    /**
     * Display the specified category.
     */
    public function show(string $id): Response
    {
        return Inertia::render('Admin/Categories/Show', ['categoryId' => (int) $id]);
    }

    /**
     * Show the form for editing the specified category.
     */
    public function edit(string $id): Response
    {
        return Inertia::render('Admin/Categories/Edit', ['categoryId' => (int) $id]);
    }
}
