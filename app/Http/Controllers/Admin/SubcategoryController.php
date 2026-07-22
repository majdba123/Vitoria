<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class SubcategoryController extends Controller
{
    public function index()
    {
        return view('admin.subcategories.index');
    }

    public function create()
    {
        return view('admin.subcategories.create');
    }

    public function show(string $id)
    {
        return view('admin.subcategories.show', ['subcategoryId' => $id]);
    }

    public function edit(string $id)
    {
        return view('admin.subcategories.edit', ['subcategoryId' => $id]);
    }
}
