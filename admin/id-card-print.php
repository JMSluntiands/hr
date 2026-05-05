<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: id-creation.php');
    exit;
}

include '../database/db.php';

$emp = null;
if ($conn) {
    // SELECT * avoids fatal errors when optional columns (e.g. profile_picture) were never migrated
    $stmt = $conn->prepare('SELECT * FROM employees WHERE id = ? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $emp = $result->fetch_assoc();
        $stmt->close();
    }
}

if (!$emp) {
    header('Location: id-creation.php');
    exit;
}

$ecName = trim((string)($emp['emergency_contact_name'] ?? ''));
$ecRelationship = trim((string)($emp['emergency_contact_relationship'] ?? ''));
$ecPhone = trim((string)($emp['emergency_contact_phone'] ?? ''));
$hasEmergencyContact = $ecName !== '' || $ecRelationship !== '' || $ecPhone !== '';

$photoPath = '';
if (!empty($emp['profile_picture']) && file_exists(__DIR__ . '/../uploads/' . $emp['profile_picture'])) {
    $photoPath = '../uploads/' . $emp['profile_picture'];
}

$companyName = 'Luntiands';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ID Card - <?php echo htmlspecialchars($emp['full_name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
        }
        /* Standard ID size: ~54mm x 86mm (CR80 portrait) = 204px x 323px at 96dpi */
        .id-card {
            width: 204px;
            height: 323px;
            min-height: 323px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }
        .id-card-photo {
            width: 76px;
            height: 76px;
            border: 2px solid white;
            box-shadow: 0 1px 4px rgba(0,0,0,0.12);
        }
        .id-card-back {
            width: 204px;
            height: 323px;
            min-height: 323px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }
        .id-card-back-scroll {
            flex: 1;
            min-height: 0;
            overflow: hidden;
        }
        .id-cards-row {
            display: flex;
            flex-wrap: wrap;
            gap: 24px;
            justify-content: center;
            align-items: flex-start;
        }
        @media print {
            .id-cards-row { gap: 20px; }
        }
    </style>
</head>
<body class="font-sans bg-slate-200 p-6 flex flex-col items-center">
    <div class="no-print mb-6 flex gap-4 items-center">
        <a href="id-creation.php" class="px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700">← Back</a>
        <button type="button" onclick="window.print()" class="px-4 py-2 bg-[#FA9800] text-white rounded-lg hover:bg-[#d97706]">Print ID Card</button>
    </div>

    <div class="id-cards-row">
    <div class="id-card bg-white flex flex-col">
        <!-- Top: Logo + Company Name -->
        <div class="bg-white pt-3 pb-2 px-3 text-center">
            <div class="h-6 flex items-center justify-center text-[#1e1e2d] text-[10px] font-semibold tracking-wide">LOGO</div>
            <div class="text-[#1e1e2d] text-xs font-bold"><?php echo htmlspecialchars($companyName); ?></div>
        </div>

        <!-- Middle: Photo + Orange shapes -->
        <div class="relative flex-1 min-h-[120px] flex flex-col items-center">
            <div class="absolute inset-0 overflow-hidden pointer-events-none">
                <div class="absolute -left-4 top-3 w-28 h-20 bg-[#ffedd5] transform -rotate-[-25deg] opacity-90"></div>
                <div class="absolute right-0 top-2 w-14 h-24 bg-[#fff7ed] transform rotate-[15deg] opacity-90"></div>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-16 bg-[#FA9800]" style="clip-path: polygon(0 40%, 100% 0%, 100% 100%, 0% 100%);"></div>
            <div class="absolute bottom-0 left-0 right-0 h-16 bg-[#d97706] opacity-95" style="clip-path: polygon(0 50%, 100% 10%, 100% 100%, 0% 100%);"></div>
            <div class="relative z-10 mt-1 flex justify-center">
                <div class="id-card-photo rounded overflow-hidden bg-slate-200 flex items-center justify-center">
                    <?php if ($photoPath): ?>
                    <img src="<?php echo htmlspecialchars($photoPath); ?>" alt="" class="w-full h-full object-cover">
                    <?php else: ?>
                    <svg class="w-8 h-8 text-slate-400" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                    <?php endif; ?>
                </div>
            </div>
            <div class="relative z-10 mt-8 text-center px-2">
                <div class="text-black font-bold text-sm leading-tight"><?php echo htmlspecialchars($emp['full_name']); ?></div>
                <div class="text-black font-semibold text-[10px] mt-0.5"><?php echo htmlspecialchars($emp['position'] ?? '—'); ?></div>
            </div>
        </div>

        <!-- Bottom: ID Number -->
        <div class="bg-white pt-2 pb-3 px-3">
            <div class="border-t-2 border-[#FA9800] pt-1.5 text-center">
                <span class="text-[#1e1e2d] font-semibold text-[10px]">ID No: </span>
                <span class="text-[#1e1e2d] font-mono font-bold text-[10px]"><?php echo htmlspecialchars($emp['employee_id'] ?? '—'); ?></span>
            </div>
        </div>
    </div>

    <!-- Back of ID Card -->
    <div class="id-card-back bg-white flex flex-col">
        <div class="bg-[#FA9800] text-white py-1.5 px-3 text-center">
            <div class="text-xs font-bold"><?php echo htmlspecialchars($companyName); ?></div>
        </div>
        <div class="p-2.5 flex-1 flex flex-col min-h-0">
            <div class="id-card-back-scroll">
            <div class="mb-1.5">
                <div class="text-[9px] font-bold text-[#FA9800] uppercase tracking-wide mb-1 border-b border-slate-200 pb-0.5">Details</div>
                <div class="space-y-0.5 text-[9px] text-slate-800">
                    <div class="flex"><span class="w-14 text-slate-500 shrink-0">Name:</span><span class="font-medium truncate min-w-0"><?php echo htmlspecialchars($emp['full_name'] ?? '—'); ?></span></div>
                    <div class="flex"><span class="w-14 text-slate-500 shrink-0">ID No:</span><span class="font-mono truncate min-w-0"><?php echo htmlspecialchars($emp['employee_id'] ?? '—'); ?></span></div>
                    <div class="flex"><span class="w-14 text-slate-500 shrink-0">Position:</span><span class="truncate min-w-0"><?php echo htmlspecialchars($emp['position'] ?? '—'); ?></span></div>
                    <div class="flex"><span class="w-14 text-slate-500 shrink-0">Dept:</span><span class="truncate min-w-0"><?php echo htmlspecialchars($emp['department'] ?? '—'); ?></span></div>
                    <div class="flex"><span class="w-14 text-slate-500 shrink-0">Hired:</span><span><?php echo !empty($emp['date_hired']) ? date('M d, Y', strtotime($emp['date_hired'])) : '—'; ?></span></div>
                    <div class="flex"><span class="w-14 text-slate-500 shrink-0">Phone:</span><span class="truncate min-w-0"><?php echo htmlspecialchars($emp['phone'] ?? '—'); ?></span></div>
                    <div class="flex"><span class="w-14 text-slate-500 shrink-0">Email:</span><span class="truncate break-all min-w-0"><?php echo htmlspecialchars($emp['email'] ?? '—'); ?></span></div>
                    <div class="flex gap-1 min-w-0"><span class="w-14 text-slate-500 shrink-0">Primary:</span><span class="min-w-0 break-words"><?php echo htmlspecialchars($emp['address'] ?? '—'); ?></span></div>
                    <?php if (!empty($emp['secondary_workplace'])): ?>
                    <div class="flex gap-1 min-w-0"><span class="w-14 text-slate-500 shrink-0">Secondary:</span><span class="min-w-0 break-words"><?php echo htmlspecialchars($emp['secondary_workplace']); ?></span></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="mb-1.5">
                <div class="text-[9px] font-bold text-[#FA9800] uppercase tracking-wide mb-1 border-b border-slate-200 pb-0.5">Emergency Contact</div>
                <div class="space-y-0.5 text-[9px] text-slate-800">
                    <?php if ($hasEmergencyContact): ?>
                    <div class="flex gap-1 min-w-0"><span class="w-14 text-slate-500 shrink-0">Name:</span><span class="min-w-0 break-words font-medium"><?php echo htmlspecialchars($ecName !== '' ? $ecName : '—'); ?></span></div>
                    <div class="flex"><span class="w-14 text-slate-500 shrink-0">Relation:</span><span class="truncate min-w-0"><?php echo htmlspecialchars($ecRelationship !== '' ? $ecRelationship : '—'); ?></span></div>
                    <div class="flex"><span class="w-14 text-slate-500 shrink-0">Phone:</span><span class="truncate min-w-0"><?php echo htmlspecialchars($ecPhone !== '' ? $ecPhone : '—'); ?></span></div>
                    <?php else: ?>
                    <p class="text-[9px] text-slate-500">No emergency contact on file.</p>
                    <?php endif; ?>
                </div>
            </div>
            </div>
            <div class="shrink-0 pt-2 border-t border-slate-200">
                <p class="text-[7px] text-slate-600 leading-tight text-center">
                    This ID card is the property of <?php echo htmlspecialchars($companyName); ?>. If found, please return to the company office. Unauthorized use is strictly prohibited.
                </p>
            </div>
        </div>
    </div>
    </div>
</body>
</html>
