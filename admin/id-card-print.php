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
require_once __DIR__ . '/../include/ensure_employment_types_table.php';
require_once __DIR__ . '/../include/ensure_employees_employment_type_id_column.php';
if ($conn) {
    ensure_employment_types_table($conn);
    ensure_employees_employment_type_id_column($conn);
}
require_once __DIR__ . '/include/id-card-employment.php';

$emp = null;
if ($conn) {
    $tTypes = $conn->query("SHOW TABLES LIKE 'employment_types'");
    $hasTypes = $tTypes && $tTypes->num_rows > 0;
    $tComp = $conn->query("SHOW TABLES LIKE 'employee_compensation'");
    $hasComp = $tComp && $tComp->num_rows > 0;
    $fields = 'e.*';
    $join = '';
    if ($hasTypes) {
        $fields .= ', et.name AS employment_type_name';
        $join .= ' LEFT JOIN employment_types et ON e.employment_type_id = et.id';
    } else {
        $fields .= ', NULL AS employment_type_name';
    }
    if ($hasComp) {
        $fields .= ', ec.employment_type AS compensation_employment_type';
        $join .= ' LEFT JOIN employee_compensation ec ON ec.employee_id = e.id';
    } else {
        $fields .= ', NULL AS compensation_employment_type';
    }
    $sql = "SELECT $fields FROM employees e$join WHERE e.id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
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

if (!hr_employee_is_regular_for_id_card($emp)) {
    header('Location: id-creation.php');
    exit;
}

$ecName = trim((string)($emp['emergency_contact_name'] ?? ''));
$ecRelationship = trim((string)($emp['emergency_contact_relationship'] ?? ''));
$ecPhone = trim((string)($emp['emergency_contact_phone'] ?? ''));
$ecAddress = trim((string)($emp['emergency_contact_address'] ?? ''));
$hasEmergencyContact = $ecName !== '' || $ecRelationship !== '' || $ecPhone !== '' || $ecAddress !== '';

$photoPath = '';
if (!empty($emp['profile_picture']) && file_exists(__DIR__ . '/../uploads/' . $emp['profile_picture'])) {
    $photoPath = '../uploads/' . $emp['profile_picture'];
}

$companyNameLine1 = 'LUNTIAN Pty Ltd -';
$companyNameLine2 = 'COOLAI DRAFTING SERVICES';
$companyName = $companyNameLine1 . ' ' . $companyNameLine2;
$companyAddress = '9 Maharlika Hi-way, Basud, Camarines Norte 4608';
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
        <!-- Top: Company name, styled like sample -->
        <div class="bg-white pt-3 pb-2 px-3 text-center">
            <div class="text-[#FA9800] text-[11px] font-bold tracking-wide uppercase">
                <?php echo htmlspecialchars($companyNameLine1); ?>
            </div>
            <div class="text-[#FA9800] text-[11px] font-bold tracking-wide uppercase">
                <?php echo htmlspecialchars($companyNameLine2); ?>
            </div>
            <div class="mt-1 text-[7px] text-slate-600 leading-tight">
                <?php echo htmlspecialchars($companyAddress); ?>
            </div>
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
        <div class="bg-white pt-2 pb-3 px-3 space-y-1">
            <div class="border-t-2 border-[#FA9800] pt-1.5 text-center">
                <span class="text-[#1e1e2d] font-semibold text-[10px]">ID No: </span>
                <span class="text-[#1e1e2d] font-mono font-bold text-[10px]"><?php echo htmlspecialchars($emp['employee_id'] ?? '—'); ?></span>
            </div>
        </div>
    </div>

    <!-- Back of ID Card -->
    <div class="id-card-back bg-white flex flex-col">
        <div class="bg-[#FA9800] text-white py-1.5 px-3 text-center">
            <div class="text-[11px] font-bold tracking-wide uppercase">
                <?php echo htmlspecialchars($companyNameLine1); ?>
            </div>
            <div class="text-[11px] font-bold tracking-wide uppercase">
                <?php echo htmlspecialchars($companyNameLine2); ?>
            </div>
        </div>
        <div class="p-2.5 flex-1 flex flex-col min-h-0">
            <div class="id-card-back-scroll">
            <div class="mb-1.5">
                <div class="text-[9px] font-bold text-[#FA9800] uppercase tracking-wide mb-1 border-b border-slate-200 pb-0.5">Emergency Contact</div>
                <div class="space-y-0.5 text-[9px] text-slate-800">
                    <?php if ($hasEmergencyContact): ?>
                    <div class="flex gap-1 min-w-0"><span class="w-14 text-slate-500 shrink-0">Name:</span><span class="min-w-0 break-words font-medium"><?php echo htmlspecialchars($ecName !== '' ? $ecName : '—'); ?></span></div>
                    <div class="flex"><span class="w-14 text-slate-500 shrink-0">Relation:</span><span class="truncate min-w-0"><?php echo htmlspecialchars($ecRelationship !== '' ? $ecRelationship : '—'); ?></span></div>
                    <div class="flex"><span class="w-14 text-slate-500 shrink-0">Phone:</span><span class="truncate min-w-0"><?php echo htmlspecialchars($ecPhone !== '' ? $ecPhone : '—'); ?></span></div>
                    <div class="flex gap-1 min-w-0"><span class="w-14 text-slate-500 shrink-0">Address:</span><span class="min-w-0 break-words"><?php echo htmlspecialchars($ecAddress !== '' ? $ecAddress : '—'); ?></span></div>
                    <?php else: ?>
                    <p class="text-[9px] text-slate-500">No emergency contact on file.</p>
                    <?php endif; ?>
                </div>
            </div>
            </div>
            <div class="shrink-0 pt-2 border-t border-slate-200 space-y-1">
                <p class="text-[7px] text-slate-600 leading-tight text-center">
                    This ID card is the property of <?php echo htmlspecialchars($companyName); ?>. If found, please return to the company office. Unauthorized use is strictly prohibited.
                </p>
                <p class="text-[7px] text-slate-600 leading-tight text-center">
                    <?php echo htmlspecialchars($companyAddress); ?>
                </p>
            </div>
        </div>
    </div>
    </div>
</body>
</html>
