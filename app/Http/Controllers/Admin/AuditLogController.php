<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $categories = [
            'grade' => 'Grades',
            'user' => 'Users',
            'supervisor' => 'Supervision',
            'attendance' => 'Attendance',
            'settings' => 'Settings',
        ];

        $category = $request->query('category');

        $logs = AuditLog::with('user')
            ->when(isset($categories[$category]), fn ($query) => $query->where('action', 'like', $category . '.%'))
            ->when($request->query('q'), function ($query, $term) {
                $query->where(function ($q) use ($term) {
                    $q->where('description', 'like', "%{$term}%")
                        ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$term}%"));
                });
            })
            ->when($request->query('from'), fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($request->query('to'), fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.audit.index', compact('logs', 'categories'));
    }
}