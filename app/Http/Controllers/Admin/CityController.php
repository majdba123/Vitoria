<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class CityController extends Controller
{
    public function index()
    {
        return view('admin.cities.index');
    }

    public function create()
    {
        return view('admin.cities.create');
    }

    public function show(string $id)
    {
        return view('admin.cities.show', ['cityId' => $id]);
    }

    public function edit(string $id)
    {
        return view('admin.cities.edit', ['cityId' => $id]);
    }
}
