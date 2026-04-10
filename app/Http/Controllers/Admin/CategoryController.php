<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories.
     */
    public function index()
    {
        return view('admin.categories.index');
    }

    /**
     * Show the form for creating a new category.
     */
    public function create()
    {
        return view('admin.categories.create');
    }

    /**
     * Display the specified category.
     */
    public function show(string $id)
    {
        return view('admin.categories.show', ['categoryId' => $id]);
    }

    /**
     * Show the form for editing the specified category.
     */
    public function edit(string $id)
    {
        return view('admin.categories.edit', ['categoryId' => $id]);
    }
}
