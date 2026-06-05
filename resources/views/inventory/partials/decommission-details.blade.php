@php use App\Services\Inventory\InventoryDecommissionService; @endphp
@if (!isset($row) || !is_object($row))
@else

@if (trim((string) ($row->company_name ?? '')) !== '')
    <div><span class="font-semibold text-slate-700">Company:</span> {{ $row->company_name }}</div>
@endif
<div><span class="font-semibold text-slate-700">Employee on form:</span> {{ $row->request_employee_name ?? '' }}</div>
<div><span class="font-semibold text-slate-700">Type:</span> {{ trim((string) ($row->equipment_type ?? '')) !== '' ? $row->equipment_type : '—' }}</div>
<div><span class="font-semibold text-slate-700">Description (from inventory):</span> {!! nl2br(e(trim((string) ($row->equipment_description ?? '')) !== '' ? $row->equipment_description : '—')) !!}</div>
<div><span class="font-semibold text-slate-700">Remarks (from inventory):</span> {{ trim((string) ($row->serial_number ?? '')) !== '' ? $row->serial_number : '—' }}</div>
<div><span class="font-semibold text-slate-700">Brand:</span> {{ trim((string) ($row->brand_manufacturer ?? '')) !== '' ? $row->brand_manufacturer : '—' }}</div>
<div><span class="font-semibold text-slate-700">Reason:</span> {!! nl2br(e($row->reason_decommissioning ?? '')) !!}</div>

@for ($ti = 1; $ti <= 3; $ti++)
    @php
        $tn = (string) ($row->{'test_'.$ti.'_notes'} ?? '');
        $td = (string) ($row->{'test_'.$ti.'_date'} ?? '');
        $tpj = (string) ($row->{'test_'.$ti.'_attachment_paths'} ?? '');
        $tpaths = InventoryDecommissionService::decodeAttachmentPaths($tpj);
    @endphp
    @if (trim($tn) === '' && trim($td) === '' && $tpaths === [])
        @continue
    @endif
    <div class="pt-2 mt-1 border-t border-slate-100 space-y-1">
        <div class="text-xs font-semibold text-slate-600 uppercase tracking-wide">Test {{ $ti }}</div>
        @if (trim($tn) !== '' || trim($td) !== '')
            <div><span class="font-semibold text-slate-700">Notes:</span> {!! nl2br(e(trim($tn) !== '' ? $tn : '—')) !!}</div>
            <div><span class="font-semibold text-slate-700">Date of test:</span> {{ \App\Support\HrDateTime::formatDate($td) }}</div>
        @endif
        @if ($tpaths !== [])
            <div><span class="font-semibold text-slate-700">Images:</span></div>
            <div class="pl-0 space-y-0.5">
                @foreach ($tpaths as $i => $path)
                    <div>
                        <a class="text-[#FA9800] underline font-medium" href="{{ asset($path) }}" target="_blank" rel="noopener">
                            Image {{ $i + 1 }} — {{ basename($path) }}
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endfor

@php $att = trim((string) ($row->attachment_path ?? '')); @endphp
@if ($att !== '')
    <div class="pt-1">
        <a class="text-[#FA9800] underline" href="{{ asset($att) }}" target="_blank" rel="noopener">Attachment proof</a>
    </div>
@endif
@endif
