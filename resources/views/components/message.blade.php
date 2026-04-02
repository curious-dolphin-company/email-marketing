@props([
    'severity' => 'info',
])

@php
	$klass = 'rounded px-4 py-2 ';
	$klass .= match ($severity) {
		'success' => ' bg-green-100 text-green-800',
		'warn' => ' bg-orange-100 text-orange-800',
		'danger' => ' bg-red-100 text-red-800',
		'info' => ' bg-blue-100 text-blue-800',
		default => ' bg-blue-100 text-blue-800',
	};

@endphp

<div {{ $attributes->merge([ 'class' => $klass ]) }} >
    {{ $slot }}
</div>
