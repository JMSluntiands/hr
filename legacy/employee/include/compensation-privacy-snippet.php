<?php
/**
 * Reusable View/Hide control for compensation sections.
 * @param string $targetId Body element id
 * @param string $placeholderId Placeholder element id
 */
$compPrivacyTarget = $compPrivacyTarget ?? '';
$compPrivacyPlaceholder = $compPrivacyPlaceholder ?? '';
?>
<button type="button"
        class="js-comp-privacy-view inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-slate-200 text-sm font-medium text-[#c2410c] bg-white hover:bg-orange-50 transition-colors"
        data-target="<?php echo htmlspecialchars($compPrivacyTarget); ?>"
        data-placeholder="<?php echo htmlspecialchars($compPrivacyPlaceholder); ?>"
        aria-label="View details">
    <svg class="w-4 h-4 js-eye-open" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7S3.732 16.057 2.458 12z"/>
    </svg>
    <svg class="w-4 h-4 js-eye-closed hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.27-2.943-9.543-7a9.965 9.965 0 012.11-3.592M6.223 6.223A9.956 9.956 0 0112 5c4.478 0 8.27 2.943 9.543 7a9.97 9.97 0 01-4.132 5.411M15 12a3 3 0 00-4.2-2.8M9.88 9.88A3 3 0 0014.12 14.12M3 3l18 18"/>
    </svg>
    <span class="js-comp-privacy-label">View</span>
</button>
