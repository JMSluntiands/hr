<?php
/**
 * Inner body for "View form" on decommission admin tables.
 * Expects: $row (decommission request + joined fields), $detailsHrefPrefix (e.g. '../')
 */
if (!isset($row) || !is_array($row)) {
    return;
}
$detailsHrefPrefix = isset($detailsHrefPrefix) ? (string)$detailsHrefPrefix : '../';
?>
<?php if (trim((string)($row['company_name'] ?? '')) !== ''): ?>
    <div><span class="font-semibold text-slate-700">Company:</span> <?php echo inventory_decommission_html_escape((string)$row['company_name']); ?></div>
<?php endif; ?>
<div><span class="font-semibold text-slate-700">Employee on form:</span> <?php echo inventory_decommission_html_escape((string)($row['request_employee_name'] ?? '')); ?></div>
<div><span class="font-semibold text-slate-700">Type:</span> <?php echo inventory_decommission_html_escape(trim((string)($row['equipment_type'] ?? '')) !== '' ? (string)$row['equipment_type'] : '—'); ?></div>
<div><span class="font-semibold text-slate-700">Description (from inventory):</span> <?php echo nl2br(inventory_decommission_html_escape(trim((string)($row['equipment_description'] ?? '')) !== '' ? (string)$row['equipment_description'] : '—')); ?></div>
<div><span class="font-semibold text-slate-700">Remarks (from inventory):</span> <?php echo inventory_decommission_html_escape(trim((string)($row['serial_number'] ?? '')) !== '' ? (string)$row['serial_number'] : '—'); ?></div>
<div><span class="font-semibold text-slate-700">Brand:</span> <?php echo inventory_decommission_html_escape(trim((string)($row['brand_manufacturer'] ?? '')) !== '' ? (string)$row['brand_manufacturer'] : '—'); ?></div>
<div><span class="font-semibold text-slate-700">Reason:</span> <?php echo nl2br(inventory_decommission_html_escape((string)($row['reason_decommissioning'] ?? ''))); ?></div>
<?php for ($ti = 1; $ti <= 3; $ti++): ?>
    <?php
    $tn = (string)($row['test_' . $ti . '_notes'] ?? '');
    $td = (string)($row['test_' . $ti . '_date'] ?? '');
    $tpj = (string)($row['test_' . $ti . '_attachment_paths'] ?? '');
    $tpaths = inventory_decommission_decode_attachment_paths_json($tpj);
    if (trim($tn) === '' && trim($td) === '' && $tpaths === []) {
        continue;
    }
    ?>
    <div class="pt-2 mt-1 border-t border-slate-100 space-y-1">
        <div class="text-xs font-semibold text-slate-600 uppercase tracking-wide">Test <?php echo $ti; ?></div>
        <?php if (trim($tn) !== '' || trim($td) !== ''): ?>
            <div><span class="font-semibold text-slate-700">Notes:</span> <?php echo nl2br(inventory_decommission_html_escape(trim($tn) !== '' ? $tn : '—')); ?></div>
            <div><span class="font-semibold text-slate-700">Date of test:</span> <?php echo inventory_decommission_html_escape(inventory_decommission_format_date_manila($td)); ?></div>
        <?php endif; ?>
        <?php if ($tpaths !== []): ?>
            <div><span class="font-semibold text-slate-700">Images:</span></div>
            <div class="pl-0 space-y-0.5"><?php echo inventory_decommission_format_attachments_html_from_paths($tpaths, $detailsHrefPrefix); ?></div>
        <?php endif; ?>
    </div>
<?php endfor; ?>
<?php
$att = trim((string)($row['attachment_path'] ?? ''));
if ($att !== ''): ?>
    <div class="pt-1"><a class="text-[#FA9800] underline" href="<?php echo inventory_decommission_html_escape($detailsHrefPrefix . $att); ?>" target="_blank" rel="noopener">Attachment proof</a></div>
<?php endif; ?>
