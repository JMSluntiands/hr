@php
    $record = $record ?? [];
    $mode = ($mode ?? 'create') === 'update' ? 'update' : 'create';
    $extraHiddenHtml = $extraHiddenHtml ?? '';
    $typeDescriptions = $typeDescriptions ?? [];
    $incidentTypes = array_keys($typeDescriptions);
    $selectedIncidentType = (string) ($record['incident_type'] ?? '');
    $injuredYes = ((string) ($record['anyone_injured'] ?? 'No')) === 'Yes';
    $val = fn (string $key, string $default = '') => e((string) ($record[$key] ?? $default));
    $sel = fn (string $key, string $value) => ((string) ($record[$key] ?? '') === $value) ? ' selected' : '';
    $inputBase = 'w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 shadow-sm placeholder:text-slate-400 transition focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/25';
    $labelBase = 'mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-600';
    $sectionTitle = 'mb-4 flex items-center gap-2 text-sm font-semibold text-slate-800';
@endphp
<div class="w-full min-w-0 overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-lg shadow-slate-200/50 ring-1 ring-slate-100">
    <div class="relative border-b border-slate-100 bg-gradient-to-r from-amber-50 via-white to-orange-50/40 px-6 py-5 md:px-8 md:py-6">
        <div class="absolute left-0 top-0 h-full w-1 rounded-l-2xl bg-[#FA9800]"></div>
        <div class="pl-3">
            <div class="flex flex-wrap items-center gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#FA9800] text-white shadow-md shadow-amber-600/20">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </span>
                <div>
                    <h2 class="text-lg font-bold tracking-tight text-slate-900 md:text-xl">Incident Report</h2>
                    <p class="mt-0.5 text-sm text-slate-500">Complete all required fields. Information is kept confidential per HR policy.</p>
                </div>
            </div>
        </div>
    </div>

    <form method="post" action="{{ $formAction }}" enctype="multipart/form-data" class="px-6 py-6 md:px-8 md:py-8">
        @csrf
        <input type="hidden" name="action" value="{{ $mode === 'update' ? 'update' : 'create' }}">
        @if($mode === 'update')
            <input type="hidden" name="report_id" value="{{ (int) ($record['id'] ?? 0) }}">
        @endif
        {!! $extraHiddenHtml !!}

        <div class="space-y-8">
            <section>
                <h3 class="{{ $sectionTitle }}">
                    <span class="h-px flex-1 max-w-[2rem] rounded-full bg-amber-400/80"></span>
                    <span>Basic information</span>
                </h3>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="{{ $labelBase }}" for="ir_company_name">Company name <span class="font-normal normal-case text-red-500">*</span></label>
                        <input id="ir_company_name" type="text" name="company_name" value="{{ $val('company_name') }}" required maxlength="255" placeholder="Organization or site name" class="{{ $inputBase }}">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="{{ $labelBase }}" for="ir_employee_name">Employee name <span class="font-normal normal-case text-red-500">*</span></label>
                        <input id="ir_employee_name" type="text" name="employee_name" value="{{ $val('employee_name') }}" required maxlength="255" placeholder="Full name as it should appear on the report" class="{{ $inputBase }}">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="{{ $labelBase }}" for="ir_location">Specific area of location <span class="font-normal normal-case text-red-500">*</span></label>
                        <input id="ir_location" type="text" name="location_area" value="{{ $val('location_area') }}" required maxlength="255" placeholder="Building, floor, zone, or exact area" class="{{ $inputBase }}">
                    </div>
                </div>
            </section>

            <section>
                <h3 class="{{ $sectionTitle }}">
                    <span class="h-px flex-1 max-w-[2rem] rounded-full bg-amber-400/80"></span>
                    <span>When &amp; what happened</span>
                </h3>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="{{ $labelBase }}" for="ir_incident_date">Date of incident <span class="font-normal normal-case text-red-500">*</span></label>
                        <input id="ir_incident_date" type="date" name="incident_date" value="{{ $val('incident_date') }}" required class="{{ $inputBase }}">
                    </div>
                    <div>
                        <label class="{{ $labelBase }}" for="ir_incident_time">Time of incident <span class="font-normal normal-case text-red-500">*</span></label>
                        <input id="ir_incident_time" type="time" name="incident_time" value="{{ $val('incident_time') }}" required class="{{ $inputBase }}">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="{{ $labelBase }}" for="ir_incident_type">Incident type <span class="font-normal normal-case text-red-500">*</span></label>
                        <select id="ir_incident_type" name="incident_type" required class="{{ $inputBase }} cursor-pointer bg-white">
                            <option value="">Select incident type…</option>
                            @foreach($incidentTypes as $t)
                                <option value="{{ $t }}"@selected($selectedIncidentType === $t)>{{ $t }}</option>
                            @endforeach
                        </select>
                        <div id="ir_incident_type_description" class="mt-3 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 {{ $selectedIncidentType === '' ? 'hidden' : '' }}">
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Description</p>
                            <ul id="ir_incident_type_description_list" class="list-disc space-y-1 pl-5"></ul>
                        </div>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="{{ $labelBase }}" for="ir_incident_details">Details of incident <span class="font-normal normal-case text-red-500">*</span></label>
                        <textarea id="ir_incident_details" name="incident_details" rows="5" required placeholder="Describe what happened, sequence of events, and any immediate context." class="{{ $inputBase }} min-h-[8rem] resize-y">{{ $record['incident_details'] ?? '' }}</textarea>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="{{ $labelBase }}" for="ir_witness">Witness of the incident</label>
                        <input id="ir_witness" type="text" name="witness_name" value="{{ $val('witness_name') }}" maxlength="255" placeholder="Name and contact if available (optional)" class="{{ $inputBase }}">
                    </div>
                </div>
            </section>

            <section>
                <h3 class="{{ $sectionTitle }}">
                    <span class="h-px flex-1 max-w-[2rem] rounded-full bg-amber-400/80"></span>
                    <span>Safety &amp; injuries</span>
                </h3>
                <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4 md:p-5">
                    <label class="{{ $labelBase }} mb-2" for="anyone_injured">Was anyone injured? <span class="font-normal normal-case text-red-500">*</span></label>
                    <select name="anyone_injured" id="anyone_injured" required class="{{ $inputBase }} max-w-md cursor-pointer bg-white">
                        <option value="No"@selected(($record['anyone_injured'] ?? 'No') === 'No')>No</option>
                        <option value="Yes"@selected(($record['anyone_injured'] ?? '') === 'Yes')>If Yes</option>
                    </select>
                    <p id="injury_hint" class="mt-3 hidden rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900 md:text-sm" role="status">
                        Please describe the <strong>type of injuries</strong> and add any <strong>additional details</strong> in the fields below.
                    </p>
                </div>

                <div id="injury_block" class="mt-4 space-y-4 rounded-xl border border-amber-200/80 bg-gradient-to-b from-amber-50/90 to-white p-4 md:p-5 transition-all{{ $injuredYes ? '' : ' hidden' }}">
                    <div class="flex items-start gap-2 text-xs text-amber-900 md:text-sm">
                        <svg class="mt-0.5 h-4 w-4 shrink-0 text-amber-600" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        <span>Injury information is used for safety follow-up and compliance only.</span>
                    </div>
                    <div>
                        <label class="{{ $labelBase }} text-amber-950/80" for="injury_types">Types of injuries</label>
                        <input type="text" name="injury_types" id="injury_types" value="{{ $val('injury_types') }}" maxlength="500" placeholder="e.g. minor cut, sprain, first aid administered" class="{{ $inputBase }} border-amber-200/80 focus:border-amber-500 focus:ring-amber-500/25">
                    </div>
                    <div>
                        <label class="{{ $labelBase }} text-amber-950/80" for="injury_details">Additional injury details</label>
                        <textarea name="injury_details" id="injury_details" rows="3" placeholder="Medical response, hospital visit, restrictions, etc." class="{{ $inputBase }} min-h-[5rem] resize-y border-amber-200/80 focus:border-amber-500 focus:ring-amber-500/25">{{ $record['injury_details'] ?? '' }}</textarea>
                    </div>
                </div>
            </section>

            <section>
                <h3 class="{{ $sectionTitle }}">
                    <span class="h-px flex-1 max-w-[2rem] rounded-full bg-amber-400/80"></span>
                    <span>Report filing</span>
                </h3>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="{{ $labelBase }}" for="ir_report_date">Date of report <span class="font-normal normal-case text-red-500">*</span></label>
                        <input id="ir_report_date" type="date" name="report_date" value="{{ $val('report_date', now()->format('Y-m-d')) }}" required class="{{ $inputBase }}">
                    </div>
                    <div>
                        <label class="{{ $labelBase }}" for="ir_report_time">Time of report <span class="font-normal normal-case text-red-500">*</span></label>
                        <input id="ir_report_time" type="time" name="report_time" value="{{ $val('report_time') }}" required class="{{ $inputBase }}">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="{{ $labelBase }}" for="ir_action_taken">Action taken</label>
                        <textarea id="ir_action_taken" name="action_taken" rows="4" placeholder="Immediate response, containment, notifications, or corrective steps." class="{{ $inputBase }} min-h-[6rem] resize-y">{{ $record['action_taken'] ?? '' }}</textarea>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="{{ $labelBase }}" for="ir_attachment">Attachment</label>
                        <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50/80 px-4 py-4 transition hover:border-amber-300/80 hover:bg-amber-50/30">
                            <input id="ir_attachment" type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx" class="block w-full cursor-pointer text-sm text-slate-600 file:mr-4 file:cursor-pointer file:rounded-lg file:border-0 file:bg-[#FA9800] file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-white file:shadow-sm hover:file:bg-amber-600">
                            <p class="mt-2 text-xs text-slate-500">Optional. PDF, images, Word, or Excel — max 5MB.</p>
                            <p class="mt-2 text-xs text-slate-500">PDF, Word, or images up to 5 MB.</p>
                        </div>
                        @if(!empty($record['attachment_path']) && !empty($record['id']))
                            <p class="mt-2 text-xs text-slate-600">Current file: <a class="font-medium text-amber-700 underline decoration-amber-300 underline-offset-2 hover:text-amber-800" href="{{ route('admin.incident-reports.attachment', $record['id']) }}" target="_blank" rel="noopener">Open attachment</a><span class="text-slate-500"> — leave empty above to keep this file.</span></p>
                        @elseif(!empty($record['attachment_path']))
                            <p class="mt-2 text-xs text-slate-600">Current file on record — save to keep or replace using the field above.</p>
                        @endif
                    </div>
                </div>
            </section>
        </div>

        <div class="mt-8 flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:items-center sm:justify-between">
            <a href="{{ $cancelUrl }}" class="inline-flex justify-center rounded-xl px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 sm:justify-start">Cancel</a>
            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#FA9800] px-6 py-3 text-sm font-semibold text-white shadow-md shadow-amber-600/25 transition hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                {{ $submitLabel ?? 'Save report' }}
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
(function () {
    var incidentTypeSelect = document.getElementById('ir_incident_type');
    var incidentTypeDescription = document.getElementById('ir_incident_type_description');
    var incidentTypeDescriptionList = document.getElementById('ir_incident_type_description_list');
    var incidentTypeMap = @json($typeDescriptions);

    function renderIncidentTypeDescription(type) {
        if (!incidentTypeSelect || !incidentTypeDescription || !incidentTypeDescriptionList) return;
        var lines = Array.isArray(incidentTypeMap[type]) ? incidentTypeMap[type] : [];
        incidentTypeDescriptionList.innerHTML = '';
        if (!lines.length) {
            incidentTypeDescription.classList.add('hidden');
            return;
        }
        for (var i = 0; i < lines.length; i += 1) {
            var li = document.createElement('li');
            li.textContent = lines[i];
            incidentTypeDescriptionList.appendChild(li);
        }
        incidentTypeDescription.classList.remove('hidden');
    }

    if (incidentTypeSelect) {
        renderIncidentTypeDescription(incidentTypeSelect.value);
        incidentTypeSelect.addEventListener('change', function () {
            renderIncidentTypeDescription(incidentTypeSelect.value);
        });
    }

    var sel = document.getElementById('anyone_injured');
    var block = document.getElementById('injury_block');
    var hint = document.getElementById('injury_hint');
    if (!sel || !block) return;
    var prev = sel.value;
    sel.addEventListener('change', function () {
        var now = sel.value;
        if (now === 'Yes' && prev !== 'Yes') {
            if (hint) hint.classList.remove('hidden');
        } else if (now !== 'Yes' && hint) {
            hint.classList.add('hidden');
        }
        prev = now;
        block.classList.toggle('hidden', now !== 'Yes');
    });
    if (sel.value === 'Yes' && hint) hint.classList.remove('hidden');
})();
</script>
@endpush
