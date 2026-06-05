<?php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . (defined('HR_LEGACY_EMBEDDED') && HR_LEGACY_EMBEDDED ? '/login' : '../index.php'));
    exit;
}
require_once __DIR__ . '/../controller/session_timeout.php';

if (strtolower((string)($_SESSION['role'] ?? '')) !== 'admin') {
    header('Location: ' . (defined('HR_LEGACY_EMBEDDED') && HR_LEGACY_EMBEDDED ? '/' : '../employee/index.php'));
    exit;
}

$_SESSION['admin_module'] = 'permission';
$adminName = $_SESSION['name'] ?? 'Admin User';
$role = $_SESSION['role'] ?? 'admin';

require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../include/admin-permissions.php';

ensureDepartmentPermissionsTable($conn);

$permissionModules = adminPermissionModules();
$groupOrder = ['sidebar', 'card', 'actions'];
$departments = [];

if ($conn) {
    $deptRes = $conn->query('SELECT id, name FROM departments ORDER BY name');
    if ($deptRes) {
        while ($row = $deptRes->fetch_assoc()) {
            $departments[] = $row;
        }
    }
}

$flashMessage = $_SESSION['permission_msg'] ?? '';
unset($_SESSION['permission_msg']);

$moduleMeta = [
    'inventory' => [
        'iconBg' => 'bg-teal-50 border border-teal-100',
        'iconText' => 'text-teal-600',
        'badge' => 'bg-teal-50 text-teal-700 border-teal-100',
        'headerBg' => 'bg-gradient-to-r from-teal-50/90 to-white',
        'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
    ],
    'hr' => [
        'iconBg' => 'bg-orange-50 border border-orange-100',
        'iconText' => 'text-[#c2410c]',
        'badge' => 'bg-orange-50 text-[#c2410c] border-orange-100',
        'headerBg' => 'bg-gradient-to-r from-orange-50/90 to-white',
        'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department Permissions</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { inter: ['Inter', 'sans-serif'] } } } };
    </script>
    <style>
        .select2-container--default .select2-selection--single {
            height: 42px !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 0.5rem !important;
            background: #fff !important;
            display: flex !important;
            align-items: center !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 40px !important;
            padding-left: 12px;
            font-size: 14px;
            color: #334155 !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow { height: 40px !important; }
        .select2-dropdown { border-color: #e2e8f0; border-radius: 0.5rem; }
        .select2-container--default .select2-results__option--highlighted[aria-selected] { background: #FA9800; }
        .perm-check { width: 1.125rem; height: 1.125rem; accent-color: #FA9800; cursor: pointer; flex-shrink: 0; }
        .dept-tab { border: 1px solid #e2e8f0; background: #fff; color: #475569; }
        .dept-tab:hover { border-color: #fdba74; color: #c2410c; background: #fff7ed; }
        .dept-tab.active { background: #FA9800; color: #fff; border-color: #FA9800; box-shadow: 0 1px 2px rgba(250, 152, 0, 0.25); }
        .perm-group-card { transition: box-shadow 0.15s ease, border-color 0.15s ease; }
        .perm-group-card:hover { box-shadow: 0 4px 12px rgba(15, 23, 42, 0.06); }
        .perm-item-label:hover { background: #fff7ed; border-color: #fed7aa; }
        .perm-item-label:has(input:checked) { background: #fff7ed; border-color: #fdba74; }
    </style>
</head>
<body class="font-inter bg-[#f1f5f9] min-h-screen">
    <?php include __DIR__ . '/include/sidebar-permission.php'; ?>

    <main class="min-h-screen overflow-y-auto p-4 pt-16 md:pt-8 md:ml-64 md:p-8">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-slate-800">Department Permissions</h1>
            <p class="text-sm text-slate-500 mt-1 max-w-2xl">Piliin ang department, tapos i-set ang approval permissions para sa buong department na iyon. <strong>Admin lang</strong> ang may access dito.</p>
        </div>

        <?php if ($flashMessage): ?>
        <div class="mb-4 px-4 py-3 rounded-lg text-sm font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
            <?php echo htmlspecialchars($flashMessage); ?>
        </div>
        <?php endif; ?>

        <div id="statusAlert" class="hidden mb-4 px-4 py-3 rounded-lg text-sm font-medium"></div>

        <!-- Editor panel -->
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="h-1 bg-gradient-to-r from-[#FA9800] via-amber-400 to-orange-300"></div>

            <!-- Top bar -->
            <div class="px-4 py-4 md:px-6 md:py-5 border-b border-slate-100 flex flex-wrap items-end justify-between gap-4 bg-slate-50/50">
                <div class="text-sm space-y-1">
                    <div class="text-xs font-medium text-slate-500 uppercase tracking-wide">Permission editor</div>
                    <div class="text-slate-600">Your role: <span class="font-medium text-slate-800"><?php echo htmlspecialchars($role); ?></span></div>
                    <div id="editingLabel" class="text-slate-500">Department: <span class="text-slate-400">— pumili ng department —</span></div>
                </div>
                <div class="w-full md:w-auto md:min-w-[300px]">
                    <label for="deptSelect" class="block text-sm font-medium text-slate-700 mb-1.5">Select department</label>
                    <select id="deptSelect" class="w-full">
                        <option value="">— Select department —</option>
                        <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo (int)$dept['id']; ?>">
                            <?php echo htmlspecialchars($dept['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <p id="legacyNote" class="hidden mx-4 md:mx-6 mt-4 text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2.5">
                Walang saved permissions para sa department na ito — <strong>full access</strong> ang lahat ng admin. I-check ang permissions, tapos Save.
            </p>

            <div id="permissionsPanel" class="hidden p-4 md:p-6 space-y-6">
                <?php if (empty($departments)): ?>
                <p class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-4 py-3">
                    Walang departments. <a href="/admin/departments" class="text-[#c2410c] font-medium underline">Mag-add muna</a>.
                </p>
                <?php else: ?>

                <div class="rounded-lg border border-slate-100 bg-slate-50/80 px-4 py-3">
                    <p class="text-xs text-slate-500">
                        Ang mga checkbox sa baba ay para sa <strong id="activeDeptLabel" class="text-slate-700">napiling department</strong>.
                        I-save para ma-apply ang approval access sa lahat ng admin para sa department na ito.
                    </p>
                </div>

                <?php foreach ($permissionModules as $moduleKey => $module):
                    $meta = $moduleMeta[$moduleKey] ?? $moduleMeta['hr'];
                    $routeCount = adminPermissionModuleRouteCount($module);
                ?>
                <section class="perm-module rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden" data-module="<?php echo htmlspecialchars($moduleKey); ?>">
                    <header class="px-4 py-4 border-b border-slate-100 flex items-start gap-3 <?php echo $meta['headerBg']; ?>">
                        <div class="w-11 h-11 rounded-xl <?php echo $meta['iconBg']; ?> flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 <?php echo $meta['iconText']; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo $meta['icon']; ?>"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-[10px] uppercase tracking-widest text-slate-500 font-semibold"><?php echo htmlspecialchars($module['subtitle']); ?></p>
                            <div class="flex flex-wrap items-center gap-2 mt-0.5">
                                <h2 class="text-lg font-bold text-slate-800"><?php echo htmlspecialchars($module['label']); ?></h2>
                                <span class="text-xs font-medium px-2 py-0.5 rounded-full border <?php echo $meta['badge']; ?>">
                                    <?php echo (int)$routeCount; ?> permissions
                                </span>
                            </div>
                        </div>
                    </header>

                    <div class="p-4 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 bg-slate-50/40">
                        <?php foreach ($groupOrder as $groupKey):
                            $group = $module['groups'][$groupKey] ?? null;
                            if (!$group) continue;
                        ?>
                        <div class="perm-group-card rounded-xl border border-slate-200 bg-white flex flex-col min-h-[220px]"
                             data-module="<?php echo htmlspecialchars($moduleKey); ?>"
                             data-group="<?php echo htmlspecialchars($groupKey); ?>">
                            <div class="px-3 py-3 border-b border-slate-100 flex items-center justify-between gap-2 bg-slate-50/60 rounded-t-xl">
                                <span class="text-sm font-semibold text-slate-800"><?php echo htmlspecialchars($group['label']); ?></span>
                                <label class="flex items-center gap-1.5 text-xs font-medium text-[#c2410c] cursor-pointer select-none shrink-0 hover:text-[#9a3412]">
                                    <input type="checkbox" class="group-check-all perm-check" data-scope="group-all">
                                    <span>Check all</span>
                                </label>
                            </div>
                            <ul class="p-2 space-y-1 flex-1 overflow-y-auto max-h-[300px]">
                                <?php foreach ($group['items'] as $permKey => $item): ?>
                                <li>
                                    <label class="perm-item-label flex items-start gap-2.5 px-2.5 py-2 rounded-lg border border-transparent cursor-pointer transition-colors">
                                        <input type="checkbox"
                                            class="perm-check perm-item mt-0.5"
                                            data-perm="<?php echo htmlspecialchars($permKey); ?>"
                                            data-module="<?php echo htmlspecialchars($moduleKey); ?>"
                                            data-group="<?php echo htmlspecialchars($groupKey); ?>">
                                        <span class="text-sm text-slate-600 leading-snug"><?php echo htmlspecialchars($item['label']); ?></span>
                                    </label>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endforeach; ?>

                <div class="flex flex-wrap justify-between items-center gap-3 pt-4 border-t border-slate-100">
                    <p class="text-xs text-slate-500">Isang save per department.</p>
                    <div class="flex flex-wrap gap-3">
                        <button type="button" id="clearDeptPermsBtn" class="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 text-sm bg-white">
                            Clear department (full access)
                        </button>
                        <button type="button" id="savePermsBtn" class="px-5 py-2 bg-[#FA9800] text-white rounded-lg hover:bg-[#e88a00] text-sm font-medium shadow-sm">
                            Save Permissions
                        </button>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div id="emptyState" class="px-6 py-12 text-center">
                <div class="inline-flex w-14 h-14 rounded-full bg-orange-50 text-[#FA9800] items-center justify-center mb-3">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </div>
                <p class="text-sm text-slate-500">Pumili ng <strong>department</strong> sa taas para i-set ang permissions.</p>
            </div>
        </div>
    </main>

    <script src="../admin/include/sidebar-dropdown.js"></script>
    <script>
    (function () {
        var activeDeptId = 0;
        var permissionKeys = [];

        $('#deptSelect').select2({
            placeholder: 'Select department…',
            allowClear: true,
            width: '100%',
            dropdownParent: $('body')
        });

        function updateEditingLabel() {
            var editing = document.getElementById('editingLabel');
            var deptOpt = $('#deptSelect').find('option:selected');
            var deptName = deptOpt.val() ? deptOpt.text() : '';
            editing.innerHTML = deptName
                ? 'Department: <span class="font-medium text-slate-800">' + deptName + '</span>'
                : 'Department: <span class="text-slate-400">— pumili ng department —</span>';
        }

        function updateActiveDeptLabel() {
            var label = document.getElementById('activeDeptLabel');
            if (!label) return;
            var deptOpt = $('#deptSelect').find('option:selected');
            label.textContent = deptOpt.val() ? deptOpt.text() : 'napiling department';
        }

        function syncPanelVisibility() {
            var panel = document.getElementById('permissionsPanel');
            var empty = document.getElementById('emptyState');
            var legacy = document.getElementById('legacyNote');
            if (activeDeptId > 0) {
                panel.classList.remove('hidden');
                empty.classList.add('hidden');
                legacy.classList.remove('hidden');
                return;
            }
            panel.classList.add('hidden');
            empty.classList.remove('hidden');
            legacy.classList.add('hidden');
        }

        function showStatus(msg, ok) {
            var el = document.getElementById('statusAlert');
            el.textContent = msg;
            el.className = 'mb-4 px-4 py-3 rounded-lg text-sm font-medium ' +
                (ok ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-red-50 text-red-700 border border-red-200');
            el.classList.remove('hidden');
        }

        function collectPermissionKeys() {
            var keys = [];
            document.querySelectorAll('.perm-item:checked').forEach(function (cb) {
                keys.push(cb.getAttribute('data-perm'));
            });
            return keys;
        }

        function applyPermissionsToUI() {
            document.querySelectorAll('.perm-item').forEach(function (cb) { cb.checked = false; });
            permissionKeys.forEach(function (key) {
                var cb = document.querySelector('.perm-item[data-perm="' + key + '"]');
                if (cb) cb.checked = true;
            });
            syncGroupCheckAllStates();
        }

        function syncGroupCheckAllStates() {
            document.querySelectorAll('.perm-group-card').forEach(function (card) {
                var toggle = card.querySelector('.group-check-all[data-scope="group-all"]');
                if (!toggle) return;
                var items = card.querySelectorAll('.perm-item');
                var allOn = items.length > 0;
                items.forEach(function (cb) { if (!cb.checked) allOn = false; });
                toggle.checked = allOn;
            });
        }

        function loadDepartmentPermissions() {
            if (!activeDeptId) return;
            fetch('/permission/action?action=load&department_id=' + activeDeptId, { credentials: 'same-origin' })
                .then(function (r) {
                    if (!r.ok) throw new Error('Server returned ' + r.status);
                    return r.json();
                })
                .then(function (data) {
                    if (!data.ok) {
                        showStatus(data.message || 'Failed to load', false);
                        return;
                    }
                    permissionKeys = Array.isArray(data.permissions) ? data.permissions : [];
                    applyPermissionsToUI();
                    document.getElementById('legacyNote').classList.toggle('hidden', !!data.configured);
                })
                .catch(function (err) { showStatus(err.message || 'Could not load permissions.', false); });
        }

        document.querySelectorAll('.perm-item').forEach(function (cb) {
            cb.addEventListener('change', syncGroupCheckAllStates);
        });

        document.querySelectorAll('.group-check-all[data-scope="group-all"]').forEach(function (toggle) {
            toggle.addEventListener('change', function () {
                var card = toggle.closest('.perm-group-card');
                if (!card) return;
                card.querySelectorAll('.perm-item').forEach(function (cb) {
                    cb.checked = toggle.checked;
                });
            });
        });

        $('#deptSelect').on('change', function () {
            activeDeptId = parseInt($(this).val(), 10) || 0;
            document.getElementById('statusAlert').classList.add('hidden');
            permissionKeys = [];
            updateEditingLabel();
            updateActiveDeptLabel();
            syncPanelVisibility();
            if (activeDeptId > 0) {
                loadDepartmentPermissions();
            } else {
                applyPermissionsToUI();
            }
        });

        document.getElementById('savePermsBtn')?.addEventListener('click', function () {
            if (!activeDeptId) {
                showStatus('Pumili muna ng department.', false);
                return;
            }
            var fd = new FormData();
            fd.append('action', 'save');
            fd.append('department_id', String(activeDeptId));
            fd.append('permissions', JSON.stringify(collectPermissionKeys()));

            fetch('/permission/action', { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function (r) {
                    if (!r.ok) throw new Error('Server returned ' + r.status);
                    return r.json();
                })
                .then(function (data) {
                    showStatus(data.message || (data.ok ? 'Saved.' : 'Error'), !!data.ok);
                    if (data.ok) {
                        permissionKeys = collectPermissionKeys();
                        document.getElementById('legacyNote').classList.add('hidden');
                    }
                })
                .catch(function (err) { showStatus(err.message || 'Save failed.', false); });
        });

        document.getElementById('clearDeptPermsBtn')?.addEventListener('click', function () {
            if (!activeDeptId) {
                showStatus('Pumili muna ng department.', false);
                return;
            }
            if (!confirm('Alisin lahat ng restrictions para sa department na ito? Full access ulit ang lahat ng admin.')) return;
            var fd = new FormData();
            fd.append('action', 'save');
            fd.append('department_id', String(activeDeptId));
            fd.append('permissions', JSON.stringify([]));

            fetch('/permission/action', { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function (r) {
                    if (!r.ok) throw new Error('Server returned ' + r.status);
                    return r.json();
                })
                .then(function (data) {
                    showStatus(data.message || (data.ok ? 'Cleared.' : 'Error'), !!data.ok);
                    if (data.ok) {
                        permissionKeys = [];
                        applyPermissionsToUI();
                        document.getElementById('legacyNote').classList.remove('hidden');
                    }
                })
                .catch(function (err) { showStatus(err.message || 'Save failed.', false); });
        });
    })();
    </script>
</body>
</html>
