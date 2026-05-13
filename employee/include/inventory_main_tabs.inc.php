<?php
/**
 * My Inventory main area by ?view= — expects: $inventoryView, $allocatedItems, $myItemRequests,
 * $myDecommissionRequests, $allDecommissionRequests, $canReviewDecommission, $employeeName, $decomAllocJson
 */
$v = $inventoryView ?? 'list';
?>
<?php if ($v === 'list'): ?>
                    <article class="rounded-2xl border border-slate-200 bg-white shadow-md ring-1 ring-slate-900/5 min-w-0 overflow-hidden">
                        <div class="flex items-center gap-2 px-4 py-3 border-b border-slate-100 bg-slate-50/90 shrink-0">
                            <svg class="h-5 w-5 text-[#FA9800] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V7a2 2 0 00-2-2h-3V3H9v2H6a2 2 0 00-2 2v6m16 0v6a2 2 0 01-2 2H6a2 2 0 01-2-2v-6m16 0H4m4 0v2m8-2v2" />
                            </svg>
                            <h2 class="text-base font-semibold text-slate-800">List of my items</h2>
                        </div>
                        <div class="px-2 md:px-4 py-3 overflow-x-auto">
                            <table id="inventoryTable" class="min-w-full text-sm display w-full">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Item ID</th>
                                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Item Name</th>
                                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Description</th>
                                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Type</th>
                                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Condition</th>
                                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Date Received</th>
                                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide no-sort">Appeal / Remarks</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php if (empty($allocatedItems)): ?>
                                        <tr>
                                            <td colspan="7" class="px-4 py-8 text-center text-slate-500 text-sm">
                                                You have no allocated items on your account yet.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($allocatedItems as $item): ?>
                                            <tr class="hover:bg-slate-50/80">
                                                <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars((string)$item['item_id']); ?></td>
                                                <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars((string)$item['item_name']); ?></td>
                                                <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars((string)$item['description']); ?></td>
                                                <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars((string)$item['type']); ?></td>
                                                <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars((string)$item['item_condition']); ?></td>
                                                <td class="px-4 py-3 text-slate-700">
                                                    <?php
                                                    $receivedDate = (string)($item['date_received'] ?? '');
                                                    echo $receivedDate !== '' ? htmlspecialchars(date('M d, Y', strtotime($receivedDate))) : '—';
                                                    ?>
                                                </td>
                                                <td class="px-4 py-3 text-slate-700 min-w-[280px]">
                                                    <?php
                                                    $hasAppeal = trim((string)($item['employee_appeal'] ?? '')) !== '';
                                                    $appealBtn = $hasAppeal ? 'Update Appeal' : 'Submit Appeal';
                                                    ?>
                                                    <?php if ($hasAppeal): ?>
                                                        <div class="mb-2 rounded-lg bg-amber-50 border border-amber-200 px-3 py-2 text-xs text-amber-800">
                                                            <div class="font-semibold">Sent to Admin</div>
                                                            <div class="mt-1"><?php echo htmlspecialchars((string)$item['employee_appeal']); ?></div>
                                                            <?php if (trim((string)($item['employee_appeal_remarks'] ?? '')) !== ''): ?>
                                                                <div class="mt-1 text-amber-700">Remarks: <?php echo htmlspecialchars((string)$item['employee_appeal_remarks']); ?></div>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <button
                                                        type="button"
                                                        class="openAppealModal px-3 py-1.5 rounded-lg text-xs font-medium bg-[#FA9800] text-white hover:opacity-90"
                                                        data-allocation-id="<?php echo (int)$item['id']; ?>"
                                                        data-item-label="<?php echo htmlspecialchars((string)$item['item_id'] . ' - ' . (string)$item['item_name']); ?>"
                                                        data-existing-appeal="<?php echo htmlspecialchars((string)($item['employee_appeal'] ?? '')); ?>"
                                                        data-existing-remarks="<?php echo htmlspecialchars((string)($item['employee_appeal_remarks'] ?? '')); ?>"
                                                    >
                                                        <?php echo $appealBtn; ?>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </article>
<?php elseif ($v === 'request'): ?>
                    <article class="rounded-2xl border border-slate-200 bg-white shadow-md ring-1 ring-slate-900/5 min-w-0 overflow-hidden">
                        <div class="flex items-center gap-2 px-4 py-3 border-b border-slate-100 bg-slate-50/90 shrink-0">
                            <svg class="h-5 w-5 text-[#FA9800] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <h2 class="text-base font-semibold text-slate-800">Request Item</h2>
                        </div>
                        <div class="px-4 pb-4 pt-4 space-y-6">
                            <div>
                                <h3 class="text-sm font-semibold text-slate-700 mb-2">Your submitted requests</h3>
                                <div class="overflow-x-auto rounded-lg border border-slate-100">
                                    <table class="min-w-full text-sm">
                                        <thead class="bg-slate-50">
                                            <tr>
                                                <th class="text-left px-3 py-2 text-xs font-semibold text-slate-500 uppercase">Item</th>
                                                <th class="text-left px-3 py-2 text-xs font-semibold text-slate-500 uppercase">Details</th>
                                                <th class="text-left px-3 py-2 text-xs font-semibold text-slate-500 uppercase">Status</th>
                                                <th class="text-left px-3 py-2 text-xs font-semibold text-slate-500 uppercase">Date</th>
                                                <th class="text-left px-3 py-2 text-xs font-semibold text-slate-500 uppercase">Admin response</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 bg-white">
                                            <?php if (empty($myItemRequests)): ?>
                                                <tr>
                                                    <td colspan="5" class="px-3 py-6 text-center text-slate-500 text-sm">
                                                        You have not submitted any requests yet.
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($myItemRequests as $req): ?>
                                                    <?php
                                                    $rs = (string)($req['status'] ?? '');
                                                    $reqCreated = (string)($req['created_at'] ?? '');
                                                    ?>
                                                    <tr class="hover:bg-slate-50/80">
                                                        <td class="px-3 py-2 text-slate-700"><?php echo htmlspecialchars((string)$req['item_name']); ?></td>
                                                        <td class="px-3 py-2 text-slate-600"><?php echo nl2br(htmlspecialchars(trim((string)($req['details'] ?? '')) !== '' ? (string)$req['details'] : '—')); ?></td>
                                                        <td class="px-3 py-2">
                                                            <?php if ($rs === 'pending'): ?>
                                                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">Pending</span>
                                                            <?php elseif ($rs === 'approved'): ?>
                                                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">Approved</span>
                                                            <?php else: ?>
                                                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">Rejected</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="px-3 py-2 text-slate-600 whitespace-nowrap">
                                                            <?php echo $reqCreated !== '' ? htmlspecialchars(date('M d, Y', strtotime($reqCreated))) : '—'; ?>
                                                        </td>
                                                        <td class="px-3 py-2 text-slate-600 text-xs">
                                                            <?php echo trim((string)($req['admin_remark'] ?? '')) !== '' ? nl2br(htmlspecialchars((string)$req['admin_remark'])) : '—'; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <form method="POST" class="rounded-xl border border-slate-100 bg-slate-50/80 p-4 space-y-3">
                                <input type="hidden" name="action" value="submit_item_request">
                                <h3 class="text-sm font-semibold text-slate-800 mb-1">New request</h3>
                                <div>
                                    <label for="requested_item_name" class="block text-sm font-medium text-slate-700 mb-1">Item name <span class="text-red-500">*</span></label>
                                    <input type="text" name="requested_item_name" id="requested_item_name" required maxlength="255" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" placeholder="e.g. Laptop, Mouse, Monitor">
                                </div>
                                <div>
                                    <label for="requested_item_details" class="block text-sm font-medium text-slate-700 mb-1">Details <span class="text-slate-400 font-normal">(optional)</span></label>
                                    <textarea name="requested_item_details" id="requested_item_details" rows="3" maxlength="2000" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" placeholder="Specifications, quantity, or reason for the request"></textarea>
                                </div>
                                <div class="flex justify-end">
                                    <button type="submit" class="px-4 py-2 rounded-lg text-sm font-medium bg-[#FA9800] text-white hover:opacity-90">
                                        Submit request
                                    </button>
                                </div>
                            </form>
                        </div>
                    </article>
<?php elseif ($v === 'decommission'): ?>
                    <article class="rounded-2xl border border-slate-200 bg-white shadow-md ring-1 ring-slate-900/5 w-full min-w-0 overflow-hidden">
                        <div class="flex items-center gap-2 px-4 py-3 border-b border-slate-100 bg-slate-50/90">
                            <svg class="h-5 w-5 text-[#FA9800] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            <h2 class="text-base font-semibold text-slate-800">Decommission Request</h2>
                        </div>
                        <div class="px-4 md:px-6 lg:px-8 pb-6 pt-5 space-y-8 w-full">
                            <script type="application/json" id="decomAllocationData"><?php echo $decomAllocJson; ?></script>
                            <div class="rounded-xl border border-slate-200 bg-white shadow-sm p-4 md:p-5">
                                <h3 class="text-sm font-semibold text-slate-800 mb-2">Decommission history</h3>
                                <p class="text-xs text-slate-500 mb-3">Who requested, who approved or declined, and when (date and time).</p>
                                <div class="overflow-x-auto rounded-lg border border-slate-100">
                                    <table class="min-w-full text-sm">
                                        <thead class="bg-slate-50">
                                            <tr>
                                                <th class="text-left px-3 py-2 text-xs font-semibold text-slate-500 uppercase">Equipment / Item ID</th>
                                                <th class="text-left px-3 py-2 text-xs font-semibold text-slate-500 uppercase">Status</th>
                                                <th class="text-left px-3 py-2 text-xs font-semibold text-slate-500 uppercase">Requested (you)</th>
                                                <th class="text-left px-3 py-2 text-xs font-semibold text-slate-500 uppercase">Reviewer</th>
                                                <th class="text-left px-3 py-2 text-xs font-semibold text-slate-500 uppercase">Resolved at</th>
                                                <th class="text-left px-3 py-2 text-xs font-semibold text-slate-500 uppercase min-w-[140px]">Form</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 bg-white">
                                            <?php if (empty($myDecommissionRequests)): ?>
                                                <tr>
                                                    <td colspan="6" class="px-3 py-6 text-center text-slate-500 text-sm">No decommission requests yet.</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($myDecommissionRequests as $dr): ?>
                                                    <?php
                                                    $drs = (string)($dr['status'] ?? '');
                                                    $dc = (string)($dr['created_at'] ?? '');
                                                    $rv = trim((string)($dr['reviewed_by_name'] ?? ''));
                                                    $ra = (string)($dr['resolved_at'] ?? '');
                                                    $idr = (int)($dr['id'] ?? 0);
                                                    ?>
                                                    <tr class="hover:bg-slate-50/80">
                                                        <td class="px-3 py-2 text-slate-700">
                                                            <div class="font-medium"><?php echo htmlspecialchars((string)$dr['equipment_name']); ?></div>
                                                            <div class="text-xs text-slate-500"><?php echo htmlspecialchars((string)$dr['item_code']); ?></div>
                                                        </td>
                                                        <td class="px-3 py-2">
                                                            <?php if ($drs === 'pending'): ?>
                                                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">Pending</span>
                                                            <?php elseif ($drs === 'approved'): ?>
                                                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">Approved</span>
                                                            <?php else: ?>
                                                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">Declined</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="px-3 py-2 text-slate-600 whitespace-nowrap text-xs"><?php echo $dc !== '' ? htmlspecialchars(date('M d, Y h:i A', strtotime($dc))) : '—'; ?></td>
                                                        <td class="px-3 py-2 text-slate-600 text-xs"><?php echo $rv !== '' ? htmlspecialchars($rv) : '—'; ?></td>
                                                        <td class="px-3 py-2 text-slate-600 whitespace-nowrap text-xs"><?php echo $ra !== '' ? htmlspecialchars(date('M d, Y h:i A', strtotime($ra))) : '—'; ?></td>
                                                        <td class="px-3 py-2 text-slate-600 text-xs align-top max-w-xs">
                                                            <details class="cursor-pointer group">
                                                                <summary class="text-[#FA9800] font-medium list-none flex items-center gap-1 [&::-webkit-details-marker]:hidden">
                                                                    <span class="border-b border-dashed border-[#FA9800]/80 group-open:border-transparent">View form</span>
                                                                </summary>
                                                                <div class="mt-2 space-y-1.5 border-t border-slate-100 pt-2 text-slate-700">
                                                                    <div class="text-[11px] text-slate-400">Request #<?php echo $idr; ?></div>
                                                                    <?php if (trim((string)($dr['company_name'] ?? '')) !== ''): ?>
                                                                        <div><span class="font-semibold text-slate-700">Company:</span> <?php echo htmlspecialchars((string)$dr['company_name']); ?></div>
                                                                    <?php endif; ?>
                                                                    <div><span class="font-semibold text-slate-700">Employee on form:</span> <?php echo htmlspecialchars((string)($dr['request_employee_name'] ?? '')); ?></div>
                                                                    <div><span class="font-semibold text-slate-700">Equipment:</span> <?php echo htmlspecialchars((string)($dr['equipment_name'] ?? '')); ?></div>
                                                                    <div><span class="font-semibold text-slate-700">Item ID:</span> <?php echo htmlspecialchars((string)($dr['item_code'] ?? '')); ?></div>
                                                                    <div><span class="font-semibold text-slate-700">Type:</span> <?php echo htmlspecialchars(trim((string)($dr['equipment_type'] ?? '')) !== '' ? (string)$dr['equipment_type'] : '—'); ?></div>
                                                                    <div><span class="font-semibold text-slate-700">Item remarks (from inventory):</span> <?php echo htmlspecialchars(trim((string)($dr['serial_number'] ?? '')) !== '' ? (string)$dr['serial_number'] : '—'); ?></div>
                                                                    <div><span class="font-semibold text-slate-700">Description:</span> <?php echo nl2br(htmlspecialchars(trim((string)($dr['equipment_description'] ?? '')) !== '' ? (string)$dr['equipment_description'] : '—')); ?></div>
                                                                    <div><span class="font-semibold text-slate-700">Brand:</span> <?php echo htmlspecialchars(trim((string)($dr['brand_manufacturer'] ?? '')) !== '' ? (string)$dr['brand_manufacturer'] : '—'); ?></div>
                                                                    <?php
                                                                    $idrRecv = trim((string)($dr['item_date_received'] ?? ''));
                                                                    $idrDecom = trim((string)($dr['date_decommissioning'] ?? ''));
                                                                    ?>
                                                                    <div><span class="font-semibold text-slate-700">Item date received:</span> <?php echo $idrRecv !== '' ? htmlspecialchars(date('M d, Y', strtotime($idrRecv))) : '—'; ?></div>
                                                                    <div><span class="font-semibold text-slate-700">Date of decommissioning:</span> <?php echo $idrDecom !== '' ? htmlspecialchars(date('M d, Y', strtotime($idrDecom))) : '—'; ?></div>
                                                                    <div><span class="font-semibold text-slate-700">Reason:</span> <?php echo nl2br(htmlspecialchars((string)($dr['reason_decommissioning'] ?? ''))); ?></div>
                                                                    <?php for ($ti = 1; $ti <= 3; $ti++): ?>
                                                                        <?php
                                                                        $tn = (string)($dr['test_' . $ti . '_notes'] ?? '');
                                                                        $td = (string)($dr['test_' . $ti . '_date'] ?? '');
                                                                        ?>
                                                                        <?php if (trim($tn) !== '' || trim($td) !== ''): ?>
                                                                            <div class="pt-1"><span class="font-semibold text-slate-700">Test <?php echo $ti; ?>:</span> <?php echo nl2br(htmlspecialchars(trim($tn) !== '' ? $tn : '—')); ?></div>
                                                                            <div><span class="font-semibold text-slate-700">Date of test (<?php echo $ti; ?>):</span> <?php echo $td !== '' ? htmlspecialchars(date('M d, Y', strtotime($td))) : '—'; ?></div>
                                                                        <?php endif; ?>
                                                                    <?php endfor; ?>
                                                                    <?php if (trim((string)($dr['resolution_remark'] ?? '')) !== ''): ?>
                                                                        <div class="pt-1 border-t border-slate-100"><span class="font-semibold text-slate-700">Reviewer remark:</span> <?php echo nl2br(htmlspecialchars((string)$dr['resolution_remark'])); ?></div>
                                                                    <?php endif; ?>
                                                                    <?php
                                                                    $att = trim((string)($dr['attachment_path'] ?? ''));
                                                                    if ($att !== ''): ?>
                                                                        <div class="pt-1"><a class="text-[#FA9800] underline font-medium" href="../<?php echo htmlspecialchars($att); ?>" target="_blank" rel="noopener">Attachment proof</a></div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </details>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50/60 shadow-inner w-full max-w-none p-4 md:p-6 lg:p-8">
                                <h3 class="text-center text-lg font-bold text-slate-900 mb-6">Equipment Decommissioning Request Form</h3>
                                <?php if (empty($allocatedItems)): ?>
                                    <p class="text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-4 py-3">
                                        You have no allocated items, so you cannot submit a decommission request here. Contact HR or your inventory administrator.
                                    </p>
                                <?php else: ?>
                                <form method="POST" enctype="multipart/form-data" class="w-full max-w-none space-y-5">
                                    <input type="hidden" name="action" value="submit_decommission">
                                    <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,220px)_1fr] gap-x-6 gap-y-4 text-sm items-start w-full">
                                        <label class="text-slate-600 font-medium lg:pt-2" for="decom_item_select">Item <span class="text-red-500">*</span></label>
                                        <div class="min-w-0">
                                            <select name="inventory_item_allocation_id" id="decom_item_select" required class="w-full min-w-0 border border-slate-300 rounded-lg px-3 py-2 bg-white">
                                                <option value="" selected>— Select or search —</option>
                                                <?php foreach ($allocatedItems as $aitem): ?>
                                                    <option value="<?php echo (int)$aitem['id']; ?>"><?php echo htmlspecialchars((string)$aitem['item_id'] . ' — ' . (string)$aitem['item_name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <p class="text-xs text-slate-500 mt-1">Only items allocated to you can be selected. Each option includes the equipment name and item ID.</p>
                                        </div>

                                        <label class="text-slate-600 font-medium lg:pt-2" for="request_employee_name">Employee name</label>
                                        <input type="text" id="request_employee_name" readonly maxlength="255" value="<?php echo htmlspecialchars($employeeName); ?>" class="w-full border border-slate-200 rounded-lg px-3 py-2 bg-slate-100 text-slate-700 cursor-not-allowed" aria-readonly="true">

                                        <label class="text-slate-600 font-medium lg:pt-2" for="item_remarks_display">Remarks <span class="text-slate-400 font-normal">(from inventory record for this item)</span></label>
                                        <textarea id="item_remarks_display" rows="3" readonly class="w-full border border-slate-200 rounded-lg px-3 py-2 bg-slate-50 text-slate-700 text-sm cursor-default resize-none" aria-readonly="true" placeholder="Select an item in the dropdown first."></textarea>

                                        <label class="text-slate-600 font-medium lg:pt-2" for="date_decommissioning">Date of decommissioning</label>
                                        <input type="date" name="date_decommissioning" id="date_decommissioning" class="w-full border border-slate-300 rounded-lg px-3 py-2 bg-white">

                                        <label class="text-slate-600 font-medium lg:pt-2" for="reason_decommissioning">Reason for decommissioning <span class="text-red-500">*</span></label>
                                        <textarea name="reason_decommissioning" id="reason_decommissioning" required rows="5" maxlength="8000" class="w-full border border-slate-300 rounded-lg px-3 py-2 bg-white" placeholder="Explain why the equipment should be decommissioned."></textarea>

                                        <label class="text-slate-600 font-medium lg:pt-2" for="test_1_notes">Test 1</label>
                                        <textarea name="test_1_notes" id="test_1_notes" rows="2" maxlength="4000" class="w-full border border-slate-300 rounded-lg px-3 py-2 bg-white" placeholder="Example: times the equipment failed to function."></textarea>
                                        <label class="text-slate-600 font-medium lg:pt-2" for="test_1_date">Date of test (Test 1)</label>
                                        <input type="date" name="test_1_date" id="test_1_date" class="w-full border border-slate-300 rounded-lg px-3 py-2 bg-white">

                                        <label class="text-slate-600 font-medium lg:pt-2" for="test_2_notes">Test 2</label>
                                        <textarea name="test_2_notes" id="test_2_notes" rows="2" maxlength="4000" class="w-full border border-slate-300 rounded-lg px-3 py-2 bg-white" placeholder="Example: times the equipment failed to function."></textarea>
                                        <label class="text-slate-600 font-medium lg:pt-2" for="test_2_date">Date of test (Test 2)</label>
                                        <input type="date" name="test_2_date" id="test_2_date" class="w-full border border-slate-300 rounded-lg px-3 py-2 bg-white">

                                        <label class="text-slate-600 font-medium lg:pt-2" for="test_3_notes">Test 3</label>
                                        <textarea name="test_3_notes" id="test_3_notes" rows="2" maxlength="4000" class="w-full border border-slate-300 rounded-lg px-3 py-2 bg-white" placeholder="Example: times the equipment failed to function."></textarea>
                                        <label class="text-slate-600 font-medium lg:pt-2" for="test_3_date">Date of test (Test 3)</label>
                                        <input type="date" name="test_3_date" id="test_3_date" class="w-full border border-slate-300 rounded-lg px-3 py-2 bg-white">

                                        <label class="text-slate-600 font-medium lg:pt-2" for="attachment_proof">Attachment proof</label>
                                        <input type="file" name="attachment_proof" id="attachment_proof" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="w-full text-sm text-slate-600">
                                    </div>
                                    <div class="flex flex-wrap gap-3 justify-end pt-4 border-t border-slate-200">
                                        <a href="inventory.php?view=decommission" class="px-4 py-2 rounded-lg text-sm font-medium bg-slate-200 text-slate-800 hover:bg-slate-300">Cancel</a>
                                        <button type="submit" class="px-4 py-2 rounded-lg text-sm font-medium bg-[#FA9800] text-white hover:opacity-90">Save</button>
                                    </div>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
<?php elseif ($v === 'decommission_review' && !empty($canReviewDecommission)): ?>
                    <article class="rounded-2xl border border-slate-200 bg-white shadow-md ring-1 ring-slate-900/5 w-full min-w-0 overflow-hidden">
                        <div class="flex items-center gap-2 px-4 py-3 border-b border-slate-100 bg-slate-50/90">
                            <svg class="h-5 w-5 text-[#FA9800] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            <h2 class="text-base font-semibold text-slate-800">Decommission requests (supervisor)</h2>
                        </div>
                        <div class="px-4 md:px-6 pb-5 pt-4 space-y-4">
                            <p class="text-xs text-slate-500">Approve or decline employee decommission requests. Actions are logged in the activity log.</p>
                            <div class="overflow-x-auto rounded-lg border border-slate-100">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="text-left px-3 py-2 text-xs font-semibold text-slate-500 uppercase">Status</th>
                                            <th class="text-left px-3 py-2 text-xs font-semibold text-slate-500 uppercase">Requester</th>
                                            <th class="text-left px-3 py-2 text-xs font-semibold text-slate-500 uppercase">Equipment</th>
                                            <th class="text-left px-3 py-2 text-xs font-semibold text-slate-500 uppercase">Submitted</th>
                                            <th class="text-left px-3 py-2 text-xs font-semibold text-slate-500 uppercase">History</th>
                                            <th class="text-left px-3 py-2 text-xs font-semibold text-slate-500 uppercase min-w-[140px]">Form</th>
                                            <th class="text-left px-3 py-2 text-xs font-semibold text-slate-500 uppercase">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        <?php if (empty($allDecommissionRequests)): ?>
                                            <tr><td colspan="7" class="px-3 py-6 text-center text-slate-500 text-sm">No requests.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($allDecommissionRequests as $ar): ?>
                                                <?php
                                                $ast = (string)($ar['status'] ?? '');
                                                $ap = $ast === 'pending';
                                                $reqName = htmlspecialchars((string)($ar['requester_full_name'] ?? ''));
                                                $reqCode = htmlspecialchars((string)($ar['requester_code'] ?? ''));
                                                $subAt = (string)($ar['created_at'] ?? '');
                                                $aid = (int)($ar['id'] ?? 0);
                                                ?>
                                                <tr class="<?php echo $ap ? 'bg-amber-50/30' : ''; ?>">
                                                    <td class="px-3 py-2 align-top">
                                                        <?php if ($ast === 'pending'): ?><span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">Pending</span>
                                                        <?php elseif ($ast === 'approved'): ?><span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">Approved</span>
                                                        <?php else: ?><span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">Declined</span><?php endif; ?>
                                                    </td>
                                                    <td class="px-3 py-2 text-slate-700 align-top text-xs"><?php echo $reqName; ?> (<?php echo $reqCode; ?>)</td>
                                                    <td class="px-3 py-2 align-top text-xs">
                                                        <div class="font-medium text-slate-800"><?php echo htmlspecialchars((string)$ar['equipment_name']); ?></div>
                                                        <div class="text-slate-500">ID: <?php echo htmlspecialchars((string)$ar['item_code']); ?></div>
                                                    </td>
                                                    <td class="px-3 py-2 text-slate-600 align-top whitespace-nowrap text-xs"><?php echo $subAt !== '' ? htmlspecialchars(date('M d, Y h:i A', strtotime($subAt))) : '—'; ?></td>
                                                    <td class="px-3 py-2 text-xs text-slate-600 align-top max-w-[200px]">
                                                        <?php if (trim((string)($ar['reviewed_by_name'] ?? '')) !== ''): ?>
                                                            <div><span class="font-semibold text-slate-700">By:</span> <?php echo htmlspecialchars((string)$ar['reviewed_by_name']); ?></div>
                                                            <div class="text-slate-500"><?php $rat = (string)($ar['resolved_at'] ?? ''); echo $rat !== '' ? htmlspecialchars(date('M d, Y h:i A', strtotime($rat))) : ''; ?></div>
                                                        <?php else: ?>—<?php endif; ?>
                                                    </td>
                                                    <td class="px-3 py-2 text-slate-600 text-xs align-top max-w-xs">
                                                        <details class="cursor-pointer group">
                                                            <summary class="text-[#FA9800] font-medium list-none flex items-center gap-1 [&::-webkit-details-marker]:hidden">
                                                                <span class="border-b border-dashed border-[#FA9800]/80 group-open:border-transparent">View form</span>
                                                            </summary>
                                                            <div class="mt-2 space-y-1.5 border-t border-slate-100 pt-2 text-slate-700">
                                                                <div class="text-[11px] text-slate-400">Request #<?php echo $aid; ?></div>
                                                                <?php if (trim((string)($ar['company_name'] ?? '')) !== ''): ?>
                                                                    <div><span class="font-semibold text-slate-700">Company:</span> <?php echo htmlspecialchars((string)$ar['company_name']); ?></div>
                                                                <?php endif; ?>
                                                                <div><span class="font-semibold text-slate-700">Employee on form:</span> <?php echo htmlspecialchars((string)($ar['request_employee_name'] ?? '')); ?></div>
                                                                <div><span class="font-semibold text-slate-700">Equipment:</span> <?php echo htmlspecialchars((string)($ar['equipment_name'] ?? '')); ?></div>
                                                                <div><span class="font-semibold text-slate-700">Item ID:</span> <?php echo htmlspecialchars((string)($ar['item_code'] ?? '')); ?></div>
                                                                <div><span class="font-semibold text-slate-700">Type:</span> <?php echo htmlspecialchars(trim((string)($ar['equipment_type'] ?? '')) !== '' ? (string)$ar['equipment_type'] : '—'); ?></div>
                                                                <div><span class="font-semibold text-slate-700">Item remarks (from inventory):</span> <?php echo htmlspecialchars(trim((string)($ar['serial_number'] ?? '')) !== '' ? (string)$ar['serial_number'] : '—'); ?></div>
                                                                <div><span class="font-semibold text-slate-700">Description:</span> <?php echo nl2br(htmlspecialchars(trim((string)($ar['equipment_description'] ?? '')) !== '' ? (string)$ar['equipment_description'] : '—')); ?></div>
                                                                <div><span class="font-semibold text-slate-700">Brand:</span> <?php echo htmlspecialchars(trim((string)($ar['brand_manufacturer'] ?? '')) !== '' ? (string)$ar['brand_manufacturer'] : '—'); ?></div>
                                                                <?php
                                                                $arRecv = trim((string)($ar['item_date_received'] ?? ''));
                                                                $arDecom = trim((string)($ar['date_decommissioning'] ?? ''));
                                                                ?>
                                                                <div><span class="font-semibold text-slate-700">Item date received:</span> <?php echo $arRecv !== '' ? htmlspecialchars(date('M d, Y', strtotime($arRecv))) : '—'; ?></div>
                                                                <div><span class="font-semibold text-slate-700">Date of decommissioning:</span> <?php echo $arDecom !== '' ? htmlspecialchars(date('M d, Y', strtotime($arDecom))) : '—'; ?></div>
                                                                <div><span class="font-semibold text-slate-700">Reason:</span> <?php echo nl2br(htmlspecialchars((string)($ar['reason_decommissioning'] ?? ''))); ?></div>
                                                                <?php for ($ti = 1; $ti <= 3; $ti++): ?>
                                                                    <?php
                                                                    $tn = (string)($ar['test_' . $ti . '_notes'] ?? '');
                                                                    $td = (string)($ar['test_' . $ti . '_date'] ?? '');
                                                                    ?>
                                                                    <?php if (trim($tn) !== '' || trim($td) !== ''): ?>
                                                                        <div class="pt-1"><span class="font-semibold text-slate-700">Test <?php echo $ti; ?>:</span> <?php echo nl2br(htmlspecialchars(trim($tn) !== '' ? $tn : '—')); ?></div>
                                                                        <div><span class="font-semibold text-slate-700">Date of test (<?php echo $ti; ?>):</span> <?php echo $td !== '' ? htmlspecialchars(date('M d, Y', strtotime($td))) : '—'; ?></div>
                                                                    <?php endif; ?>
                                                                <?php endfor; ?>
                                                                <?php if (trim((string)($ar['resolution_remark'] ?? '')) !== ''): ?>
                                                                    <div class="pt-1 border-t border-slate-100"><span class="font-semibold text-slate-700">Reviewer remark:</span> <?php echo nl2br(htmlspecialchars((string)$ar['resolution_remark'])); ?></div>
                                                                <?php endif; ?>
                                                                <?php
                                                                $attAr = trim((string)($ar['attachment_path'] ?? ''));
                                                                if ($attAr !== ''): ?>
                                                                    <div class="pt-1"><a class="text-[#FA9800] underline font-medium" href="../<?php echo htmlspecialchars($attAr); ?>" target="_blank" rel="noopener">Attachment proof</a></div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </details>
                                                    </td>
                                                    <td class="px-3 py-2 align-top min-w-[200px]">
                                                        <?php if ($ap): ?>
                                                            <form method="POST" class="space-y-1 mb-2">
                                                                <input type="hidden" name="action" value="review_decommission_request">
                                                                <input type="hidden" name="request_id" value="<?php echo (int)$ar['id']; ?>">
                                                                <input type="hidden" name="new_status" value="approved">
                                                                <textarea name="resolution_remark" rows="2" class="w-full border border-slate-300 rounded px-2 py-1 text-xs" placeholder="Optional note"></textarea>
                                                                <button type="submit" class="w-full px-2 py-1.5 rounded text-xs font-medium bg-emerald-600 text-white">Approve</button>
                                                            </form>
                                                            <form method="POST" class="space-y-1">
                                                                <input type="hidden" name="action" value="review_decommission_request">
                                                                <input type="hidden" name="request_id" value="<?php echo (int)$ar['id']; ?>">
                                                                <input type="hidden" name="new_status" value="declined">
                                                                <textarea name="resolution_remark" rows="2" class="w-full border border-slate-300 rounded px-2 py-1 text-xs" placeholder="Optional reason"></textarea>
                                                                <button type="submit" class="w-full px-2 py-1.5 rounded text-xs font-medium bg-red-600 text-white">Decline</button>
                                                            </form>
                                                        <?php else: ?>
                                                            <span class="text-slate-400">—</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </article>
<?php endif; ?>
