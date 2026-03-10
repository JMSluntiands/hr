<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

if (strtolower((string)($_SESSION['role'] ?? '')) !== 'admin') {
    header('Location: ../employee/index.php');
    exit;
}

include __DIR__ . '/../database/db.php';
require_once __DIR__ . '/database/item-config.php';
require_once __DIR__ . '/database/setup_inventory_items_table.php';
require_once __DIR__ . '/database/setup_inventory_item_allocations_table.php';

$adminName = $_SESSION['name'] ?? 'Admin User';
$role = $_SESSION['role'] ?? 'admin';

ensureInventoryItemsTable($conn);
ensureInventoryItemAllocationsTable($conn);

$itemOptions = getInventoryItemOptions();
$itemCounts = [];
foreach ($itemOptions as $itemName) {
    $itemCounts[$itemName] = 0;
}

$cardBackgrounds = [
    'from-blue-500 to-indigo-600',
    'from-emerald-500 to-teal-600',
    'from-amber-500 to-orange-600',
    'from-fuchsia-500 to-pink-600',
    'from-cyan-500 to-sky-600',
    'from-violet-500 to-purple-600',
];

function getInventoryCardIconSvg(string $itemName): string
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

$result = $conn->query("
    SELECT item_name, COUNT(*) AS total_count
    FROM inventory_items
    GROUP BY item_name
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $name = (string)$row['item_name'];
        $count = (int)$row['total_count'];
        if (array_key_exists($name, $itemCounts)) {
            $itemCounts[$name] = $count;
        }
    }
}

$appealUnreadCount = 0;
$appealMessages = [];

$unreadAppealResult = $conn->query("
    SELECT COUNT(*) AS total_unread
    FROM inventory_item_allocations
    WHERE employee_appeal IS NOT NULL
      AND TRIM(employee_appeal) <> ''
      AND admin_viewed_at IS NULL
");
if ($unreadAppealResult && $row = $unreadAppealResult->fetch_assoc()) {
    $appealUnreadCount = (int)($row['total_unread'] ?? 0);
}

$appealListResult = $conn->query("
    SELECT
        ia.id,
        ia.employee_appeal,
        ia.employee_appeal_at,
        ia.admin_viewed_at,
        e.full_name,
        e.employee_id AS employee_code,
        ii.item_id,
        ii.item_name
    FROM inventory_item_allocations ia
    JOIN employees e ON e.id = ia.employee_id
    JOIN inventory_items ii ON ii.id = ia.inventory_item_id
    WHERE ia.employee_appeal IS NOT NULL
      AND TRIM(ia.employee_appeal) <> ''
    ORDER BY
      CASE WHEN ia.admin_viewed_at IS NULL THEN 0 ELSE 1 END ASC,
      ia.employee_appeal_at DESC,
      ia.id DESC
    LIMIT 5
");
if ($appealListResult) {
    while ($row = $appealListResult->fetch_assoc()) {
        $appealMessages[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { inter: ['Inter', 'sans-serif'] }
                }
            }
        };
    </script>
</head>
<body class="font-inter bg-[#f1f5f9] min-h-screen">
    <?php include __DIR__ . '/include/sidebar-inventory.php'; ?>

    <main class="min-h-screen overflow-y-auto p-4 pt-16 md:pt-8 md:ml-64 md:p-8">
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-2xl font-semibold text-slate-800">Inventory Management</h1>
        </div>

        <section class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
            <h2 class="text-lg font-semibold text-slate-800 mb-2">Inventory Overview</h2>
            <p class="text-slate-600 text-sm mb-6">Total count per item category.</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <?php $cardIndex = 0; ?>
                <?php foreach ($itemCounts as $itemName => $count): ?>
                    <?php
                        $bgClass = $cardBackgrounds[$cardIndex % count($cardBackgrounds)];
                        $iconSvg = getInventoryCardIconSvg($itemName);
                        $cardIndex++;
                    ?>
                    <div class="rounded-xl bg-gradient-to-br <?php echo $bgClass; ?> p-4 text-white shadow-sm relative overflow-hidden">
                        <div class="absolute -right-4 -top-4 w-16 h-16 rounded-full bg-white/20"></div>
                        <div class="flex items-start justify-between mb-3">
                            <div class="text-sm font-semibold"><?php echo htmlspecialchars($itemName); ?></div>
                            <div class="w-9 h-9 rounded-lg bg-white/20 flex items-center justify-center text-sm">
                                <?php echo $iconSvg; ?>
                            </div>
                        </div>
                        <div class="text-3xl font-bold leading-none"><?php echo (int)$count; ?></div>
                        <div class="text-xs text-white/90 mt-1">Total Count</div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 mt-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-800">Messages</h2>
                    <p class="text-slate-600 text-sm">Employee appeals about wrong item allocation.</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center rounded-full bg-amber-100 text-amber-800 text-xs font-semibold px-3 py-1">
                        Unread: <?php echo (int)$appealUnreadCount; ?>
                    </span>
                    <a href="messages.php" class="px-4 py-2 rounded-lg text-sm font-medium bg-[#FA9800] text-white hover:opacity-90">
                        Open Messages
                    </a>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Employee</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Item</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Appeal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($appealMessages)): ?>
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-slate-500 text-sm">No employee appeals yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($appealMessages as $appeal): ?>
                                <?php
                                $employeeLabel = (string)$appeal['full_name'] . ' (' . (string)$appeal['employee_code'] . ')';
                                $itemLabel = (string)$appeal['item_id'] . ' - ' . (string)$appeal['item_name'];
                                $isUnread = empty($appeal['admin_viewed_at']);
                                ?>
                                <tr>
                                    <td class="px-4 py-3">
                                        <?php if ($isUnread): ?>
                                            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">New</span>
                                        <?php else: ?>
                                            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">Read</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars($employeeLabel); ?></td>
                                    <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars($itemLabel); ?></td>
                                    <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars((string)$appeal['employee_appeal']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script src="../admin/include/sidebar-dropdown.js"></script>
</body>
</html>
