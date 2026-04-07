@props(['id'])

<label for="{{$id}}" class="flex items-center p-3 w-full bg-layer border border-layer-line rounded-lg text-sm focus:border-primary-focus focus:ring-primary-focus">
	<input type="radio" id="{{$id}}" {{ $attributes->merge(['class' => 'shrink-0 size-4 bg-transparent border-line-3 rounded-full shadow-2xs text-primary focus:ring-0 focus:ring-offset-0 checked:bg-primary-checked checked:border-primary-checked disabled:opacity-50 disabled:pointer-events-none']) }}>
	<span class="text-sm ms-3 text-muted-foreground-1">{{ $slot }}</span>
</label>
