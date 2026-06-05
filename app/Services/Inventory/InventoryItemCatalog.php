<?php

namespace App\Services\Inventory;

class InventoryItemCatalog
{
    /** @var list<string> */
    public const ITEM_OPTIONS = [
        'Laptop',
        'Mouse',
        'Keyboard',
        'Charger',
        'Power Cord',
        'Monitor',
        'Portable Monitor',
        'Laptop Stand',
        'Laptop Sleeve',
        'Storage Bag',
        'Bag',
        'Company Phone',
        'Table',
        'Miscellaneous',
    ];

    /** @var array<string, string> */
    public const PREFIXES = [
        'Laptop' => 'LAP-',
        'Mouse' => 'MOU-',
        'Keyboard' => 'KEY-',
        'Charger' => 'CHG-',
        'Power Cord' => 'COR-',
        'Monitor' => 'MON-',
        'Portable Monitor' => 'PMO-',
        'Laptop Stand' => 'LST-',
        'Laptop Sleeve' => 'LSL-',
        'Storage Bag' => 'STB-',
        'Bag' => 'BAG-',
        'Company Phone' => 'CPH-',
        'Table' => 'TAB-',
        'Miscellaneous' => 'MSC-',
    ];

    /** @var list<string> */
    public const CONDITIONS = [
        'Working-Active',
        'Need Repair',
        'Decom - Stock',
        'Stock',
        'Decommissioned',
    ];

    /** @var list<string> */
    public const CARD_BACKGROUNDS = [
        'from-blue-500 to-indigo-600',
        'from-emerald-500 to-teal-600',
        'from-amber-500 to-orange-600',
        'from-fuchsia-500 to-pink-600',
        'from-cyan-500 to-sky-600',
        'from-violet-500 to-purple-600',
    ];

    public static function cardIconSvg(string $itemName): string
    {
        $icons = [
            'Laptop' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5"><rect x="4" y="5" width="16" height="11" rx="1"></rect><path d="M2 19h20"></path></svg>',
            'Mouse' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5"><rect x="7" y="3" width="10" height="18" rx="5"></rect><path d="M12 7v3"></path></svg>',
            'Keyboard' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5"><rect x="2" y="6" width="20" height="12" rx="2"></rect><path d="M6 10h.01M10 10h.01M14 10h.01M18 10h.01M6 14h8"></path></svg>',
            'Charger' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5"><path d="M7 7h10v5a5 5 0 01-5 5h0a5 5 0 01-5-5V7z"></path><path d="M10 3v4M14 3v4"></path></svg>',
            'Power Cord' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5"><path d="M9 3v5M15 3v5"></path><path d="M7 8h10v2a5 5 0 01-5 5v6"></path></svg>',
            'Monitor' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5"><rect x="3" y="4" width="18" height="12" rx="2"></rect><path d="M8 20h8M12 16v4"></path></svg>',
            'Portable Monitor' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5"><rect x="5" y="3" width="14" height="18" rx="2"></rect><path d="M11 18h2"></path></svg>',
            'Laptop Stand' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5"><path d="M5 18h14"></path><path d="M7 18l5-10 5 10"></path></svg>',
            'Laptop Sleeve' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5"><rect x="4" y="6" width="16" height="12" rx="2"></rect><path d="M4 10h16"></path></svg>',
            'Storage Bag' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5"><path d="M6 8h12l-1 12H7L6 8z"></path><path d="M9 8a3 3 0 016 0"></path></svg>',
            'Bag' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5"><rect x="5" y="7" width="14" height="13" rx="2"></rect><path d="M9 7V5a3 3 0 016 0v2"></path></svg>',
            'Company Phone' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5"><rect x="7" y="2" width="10" height="20" rx="2"></rect><path d="M11 18h2"></path></svg>',
            'Table' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5"><path d="M4 8h16"></path><path d="M6 8v12M18 8v12"></path></svg>',
            'Miscellaneous' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5"><path d="M3 8l9-5 9 5-9 5-9-5z"></path><path d="M3 8v8l9 5 9-5V8"></path></svg>',
        ];

        return $icons[$itemName] ?? $icons['Miscellaneous'];
    }
}
