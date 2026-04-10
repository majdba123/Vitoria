<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactMessageViewController extends Controller
{
    /**
     * Display the contact messages index with initial data (server-rendered).
     */
    public function index(Request $request): View
    {
        $query = ContactMessage::query()
            ->with('user:id,name,email')
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        $perPage = 15;
        $messages = $query->paginate($perPage);

        $items = $messages->getCollection()->map(function (ContactMessage $m) {
            return [
                'id' => $m->id,
                'user_id' => $m->user_id,
                'user' => $m->user ? [
                    'id' => $m->user->id,
                    'name' => $m->user->name,
                    'email' => $m->user->email,
                ] : null,
                'name' => $m->name,
                'email' => $m->email,
                'message' => $m->message,
                'status' => $m->status,
                'admin_reply' => $m->admin_reply,
                'replied_at' => $m->replied_at?->toIso8601String(),
                'created_at' => $m->created_at?->toIso8601String(),
            ];
        })->all();

        return view('admin.contact-messages.index', [
            'initialMessages' => $items,
            'meta' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
            ],
            'filterStatus' => $request->input('status', ''),
        ]);
    }
}
