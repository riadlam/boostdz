@php
    /** @var \App\Models\CatalogCategory|null $record */
    $record = $getRecord();
    $selected = $getState();
@endphp

<select
    wire:key="featured-service-{{ $record?->id }}"
    wire:change="updateFeaturedService({{ $record?->id }}, $event.target.value)"
    class="fi-select-input block w-full min-w-[18rem] max-w-full rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-sm text-gray-950 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
>
    <option value="" @selected(blank($selected))>— None —</option>
    @foreach ($options as $value => $label)
        <option value="{{ $value }}" @selected((string) $selected === (string) $value)>{{ $label }}</option>
    @endforeach
</select>
