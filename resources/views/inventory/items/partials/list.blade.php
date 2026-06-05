<section class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-4">
        <h2 class="text-lg font-semibold text-slate-800">Items List</h2>
        <div class="w-full md:w-72">
            <label for="itemNameFilter" class="block text-sm text-slate-600 mb-1">Filter by Item Name</label>
            <select id="itemNameFilter" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                <option value="">All items</option>
            </select>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table id="itemsTable" class="display stripe hover w-full text-sm">
            <thead>
                <tr>
                    <th>Item ID</th>
                    <th>Item Name</th>
                    <th>Description</th>
                    <th>Type</th>
                    <th>Allocated To</th>
                    <th>Item Condition</th>
                    <th>Remarks</th>
                    <th>Date Arrived</th>
                    <th>Brand / Manufacturer</th>
                    <th>Pictures</th>
                    <th>Print Label</th>
                    <th>ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $item)
                @php
                    $imgPaths = $item->image_paths ?? [];
                    $imgPathsJson = htmlspecialchars($item->image_paths_json ?? '[]', ENT_QUOTES, 'UTF-8');
                    $allocName = trim((string) ($item->allocated_to_name ?? ''));
                @endphp
                <tr>
                    <td>{{ $item->item_id }}</td>
                    <td>{{ $item->item_name }}</td>
                    <td>{{ $item->description }}</td>
                    <td>{{ $item->type }}</td>
                    <td>{{ $allocName !== '' ? $allocName : 'NA' }}</td>
                    <td>{{ $item->item_condition }}</td>
                    <td>{{ $item->remarks }}</td>
                    <td>{{ $item->date_arrived }}</td>
                    <td>{{ $item->brand_manufacturer }}</td>
                    <td>
                        @if ($imgPaths !== [])
                            @foreach ($imgPaths as $imgIdx => $imgPath)
                                <a class="print-label-link picture-link" target="_blank" href="{{ asset($imgPath) }}">{{ $imgIdx + 1 }}</a>@if ($imgIdx < count($imgPaths) - 1)<span class="text-slate-300"> </span>@endif
                            @endforeach
                        @else
                            <span class="text-xs text-slate-400">No image</span>
                        @endif
                    </td>
                    <td class="print-label-cell">
                        <div class="print-label-buttons">
                            <a class="print-label-link print-label-qr" target="_blank" href="{{ $printStickerBase }}?id={{ (int) $item->id }}&mode=qr">QR</a>
                            <a class="print-label-link print-label-barcode" target="_blank" href="{{ $printStickerBase }}?id={{ (int) $item->id }}&mode=barcode">BARCODE</a>
                        </div>
                    </td>
                    <td class="action-cell">
                        <div class="action-buttons">
                            <button type="button" class="viewBtn action-btn action-btn-emerald"
                                data-item_id="{{ $item->item_id }}"
                                data-item_name="{{ $item->item_name }}"
                                data-description="{{ $item->description }}"
                                data-type="{{ $item->type }}"
                                data-item_condition="{{ $item->item_condition }}"
                                data-remarks="{{ $item->remarks }}"
                                data-date_arrived="{{ $item->date_arrived }}"
                                data-brand_manufacturer="{{ $item->brand_manufacturer }}"
                                data-item-image-paths="{{ $imgPathsJson }}"
                                title="View">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/></svg>
                            </button>
                            <button type="button" class="historyBtn action-btn action-btn-violet"
                                data-id="{{ (int) $item->id }}"
                                data-item_id="{{ $item->item_id }}"
                                data-item_name="{{ $item->item_name }}"
                                title="History">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 12a9 9 0 109-9" stroke="currentColor" stroke-width="2"/><path d="M3 3v6h6" stroke="currentColor" stroke-width="2"/><path d="M12 7v5l3 3" stroke="currentColor" stroke-width="2"/></svg>
                            </button>
                            <button type="button" class="editBtn action-btn action-btn-blue"
                                data-id="{{ (int) $item->id }}"
                                data-item_id="{{ $item->item_id }}"
                                data-item_name="{{ $item->item_name }}"
                                data-description="{{ $item->description }}"
                                data-type="{{ $item->type }}"
                                data-item_condition="{{ $item->item_condition }}"
                                data-remarks="{{ $item->remarks }}"
                                data-date_arrived="{{ $item->date_arrived }}"
                                data-brand_manufacturer="{{ $item->brand_manufacturer }}"
                                data-item-image-paths="{{ $imgPathsJson }}"
                                title="Edit">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 20h9" stroke="currentColor" stroke-width="2"/><path d="M16.5 3.5a2.121 2.121 0 113 3L7 19l-4 1 1-4 12.5-12.5z" stroke="currentColor" stroke-width="2"/></svg>
                            </button>
                            <form method="POST" action="{{ route('inventory.items.destroy', $item->id) }}" onsubmit="return confirm('Delete this item?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="action-btn action-btn-red" title="Delete">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 6h18" stroke="currentColor" stroke-width="2"/><path d="M8 6V4a1 1 0 011-1h6a1 1 0 011 1v2" stroke="currentColor" stroke-width="2"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6" stroke="currentColor" stroke-width="2"/><path d="M10 11v6M14 11v6" stroke="currentColor" stroke-width="2"/></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>

@push('modals')
@include('inventory.items.partials.list-modals')
@endpush

@push('scripts')
@include('inventory.items.partials.list-scripts')
@endpush
