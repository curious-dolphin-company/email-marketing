@props([
    'status'
])

@php
	$klass = 'px-2 py-1 text-sm rounded ';
	$klass .= match ($status) {
		'success', 'sent', 'active' => ' bg-green-100 text-green-800',
		'scheduled' => ' bg-yellow-100 text-yellow-800',
		'warn' => ' bg-orange-100 text-orange-800',
		'danger', 'failed' => ' bg-red-100 text-red-800',
		'info', 'sending' => ' bg-blue-100 text-blue-800',
		default => ' bg-gray-100 text-gray-800',
	};

@endphp

<span {{ $attributes->merge([ 'class' => $klass ]) }} >
    {{ $slot }}
</span>
