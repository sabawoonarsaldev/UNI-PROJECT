@props(['status'])

@php
    $map = [
        'draft' => ['Draft', 'bg-gray-100 text-gray-700'],
        'submitted' => ['Waiting for supervisor', 'bg-amber-100 text-amber-800'],
        'revision' => ['Revision requested', 'bg-red-100 text-red-700'],
        'supervisor_approved' => ['Waiting for admin approval', 'bg-blue-100 text-blue-800'],
        'approved' => ['Approved', 'bg-green-100 text-green-800'],
    ];
    [$label, $class] = $map[$status] ?? [$status, 'bg-gray-100 text-gray-700'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {$class}"]) }}>{{ $label }}</span>