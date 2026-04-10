<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class SubcategoryController extends Controller
{
    /**
     * Display a listing of subcategories.
     */
    public function index()
    {
        return view('admin.subcategories.index');
    }

    /**
     * Show the form for creating a new subcategory.
     */
    public function create()
    {
        return view('admin.subcategories.create');
    }

    /**
     * Display the specified subcategory.
     */
    public function show(string $id)
    {
        return view('admin.subcategories.show', ['subcategoryId' => $id]);
    }

    /**
     * Show the form for editing the specified subcategory.
     */
    public function edit(string $id)
    {
        return view('admin.subcategories.edit', ['subcategoryId' => $id]);
    }
}
