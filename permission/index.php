<?php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}
require_once __DIR__ . '/../controller/session_timeout.php';

if (strtolower((string)($_SESSION['role'] ?? '')) !== 'admin') {
    header('Location: ../employee/index.php');
    exit;
}

$_SESSION['admin_module'] = 'permission';
$adminName = $_SESSION['name'] ?? 'Admin User';
$role = $_SESSION['role'] ?? 'admin';

require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../include/admin-permissions.php';

ensureAdminUserPermissionsTable($conn);

$permissionPages = adminPermissionPages();
$departments = [];
$adminUsers = [];

if ($conn) {
    $deptRes = $conn->query('SELECT id, name FROM departments ORDER BY name');
    if ($deptRes) {
        while ($row = $deptRes->fetch_assoc()) {
            $departments[] = $row;
        }
    }

    $userRes = $conn->query("SELECT id, email FROM user_login WHERE LOWER(role) = 'admin' ORDER BY email");
    if ($userRes) {
        while ($row = $userRes->fetch_assoc()) {
            $adminUsers[] = $row;
        }
    }
}

$flashMessage = $_SESSION['permission_msg'] ?? '';
unset($_SESSION['permission_msg']);

$pageKeys = array_keys($permissionPages);
$firstPageKey = $pageKeys[0] ?? '';
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
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { inter: ['Inter', 'sans-serif'] }
                }
            }
        };
    </script>
    <style>
        .select2-container--default .select2-selection--single {
            height: 42px !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 0.5rem !important;
            display: flex !important;
            align-items: center !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 40px !important;
            padding-left: 12px;
            font-size: 14px;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px !important;
        }
        .select2-dropdown { border-color: #e2e8f0; border-radius: 0.5rem; }
        .perm-check {
            width: 1.125rem;
            height: 1.125rem;
            accent-color: #FA9800;
            cursor: pointer;
            flex-shrink: 0;
        }
        .page-tab.active {
            background: #fff;
            color: #c2410c;
            border-color: #FA9800;
            box-shadow: 0 1px 2px rgba(0,0,0,.06);
        }
        .page-panel { display: none; }
        .page-panel.active { display: block; }
    </style>
</head>
<body class="font-inter bg-[#f1f5f9] min-h-screen">
    <?php include __DIR__ . '/include/sidebar-permission.php'; ?>

    <main class="min-h-screen overflow-y-auto p-4 pt-16 md:pt-8 md:ml-64 md:p-8">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-slate-800">Department Permissions</h1>
            <p class="text-sm text-slate-500 mt-1">
                Piliin ang admin user, tapos i-set per HR page kung aling department ang pwedeng i-approve o i-decline.
                Walang saved permissions = full access hanggang i-save mo dito.
            </p>
        </div>

        <?php if ($flashMessage): ?>
        <div class="mb-4 px-4 py-3 rounded-lg text-sm font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
            <?php echo htmlspecialchars($flashMessage); ?>
        </div>
        <?php endif; ?>

        <div id="statusAlert" class="hidden mb-4 px-4 py-3 rounded-lg text-sm font-medium"></div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 mb-6">
            <label for="adminUserSelect" class="block text-sm font-medium text-slate-700 mb-2">Admin User</label>
            <select id="adminUserSelect" class="w-full max-w-lg">
                <option value="">— Select admin —</option>
                <?php foreach ($adminUsers as $u): ?>
                <option value="<?php echo (int)$u['id']; ?>">
                    <?php echo htmlspecialchars($u['email']); ?>
                </option>
                <?php endforeach; ?>
            </select>
            <p id="legacyNote" class="hidden mt-3 text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                Walang saved permissions ang admin na ito — may <strong>full approve access</strong> muna. I-save sa baba para i-limit per page at department.
            </p>
        </div>

        <div id="permissionsPanel" class="hidden">
            <?php if (empty($departments)): ?>
            <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 text-sm text-amber-700">
                Walang departments. Mag-add muna sa
                <a href="../admin/department.php" class="text-[#FA9800] underline">HR → Departments</a>.
            </div>
            <?php else: ?>

            <!-- Page tabs -->
            <div class="bg-white rounded-t-xl border border-slate-100 border-b-0 px-4 pt-4">
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-3">HR Pages</p>
                <div class="flex flex-wrap gap-2" id="pageTabs" role="tablist">
                    <?php foreach ($permissionPages as $permKey => $page): ?>
                    <button
                        type="button"
                        class="page-tab px-3 py-2 text-sm font-medium rounded-lg border border-transparent text-slate-600 hover:bg-slate-50 transition-colors<?php echo $permKey === $firstPageKey ? ' active' : ''; ?>"
                        data-page="<?php echo htmlspecialchars($permKey); ?>"
                        role="tab"
                        aria-selected="<?php echo $permKey === $firstPageKey ? 'true' : 'false'; ?>"
                    >
                        <?php echo htmlspecialchars($page['label']); ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- One panel per HR page -->
            <div class="bg-white rounded-b-xl shadow-sm border border-slate-100 overflow-hidden">
                <?php foreach ($permissionPages as $permKey => $page): ?>
                <section
                    class="page-panel<?php echo $permKey === $firstPageKey ? ' active' : ''; ?>"
                    id="panel-<?php echo htmlspecialchars($permKey); ?>"
                    data-page="<?php echo htmlspecialchars($permKey); ?>"
                    role="tabpanel"
                >
                    <div class="px-6 py-4 border-b border-slate-100 flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="text-base font-semibold text-slate-800"><?php echo htmlspecialchars($page['label']); ?></h2>
                            <p class="text-sm text-slate-500 mt-0.5"><?php echo htmlspecialchars($page['description']); ?></p>
                            <p class="text-xs text-slate-400 mt-1">HR path: admin/<?php echo htmlspecialchars($page['admin_path']); ?></p>
                        </div>
                        <div class="flex flex-wrap gap-2 text-xs shrink-0">
                            <button type="button" class="page-check-all px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50" data-page="<?php echo htmlspecialchars($permKey); ?>">
                                Check all departments
                            </button>
                            <button type="button" class="page-uncheck-all px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50" data-page="<?php echo htmlspecialchars($permKey); ?>">
                                Uncheck all
                            </button>
                        </div>
                    </div>

                    <div class="p-6">
                        <p class="text-sm font-medium text-slate-700 mb-3">Allowed departments for this page</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                            <?php foreach ($departments as $dept): ?>
                            <label class="flex items-center gap-3 p-3 rounded-lg border border-slate-200 hover:border-[#FA9800]/40 hover:bg-amber-50/30 cursor-pointer transition-colors">
                                <input
                                    type="checkbox"
                                    class="perm-check"
                                    data-dept="<?php echo (int)$dept['id']; ?>"
                                    data-perm="<?php echo htmlspecialchars($permKey); ?>"
                                >
                                <span class="text-sm font-medium text-slate-800"><?php echo htmlspecialchars($dept['name']); ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
                <?php endforeach; ?>

                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50 flex flex-wrap justify-between items-center gap-3">
                    <p class="text-xs text-slate-500">Isang save para sa lahat ng pages at departments ng napiling admin.</p>
                    <div class="flex flex-wrap gap-3">
                        <button type="button" id="clearUserPermsBtn" class="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-white text-sm bg-white">
                            Clear all (full access)
                        </button>
                        <button type="button" id="savePermsBtn" class="px-5 py-2 bg-[#FA9800] text-white rounded-lg hover:bg-[#d97706] text-sm font-medium">
                            Save Permissions
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="../admin/include/sidebar-dropdown.js"></script>
    <script>
    (function () {
        var selectedUserId = 0;
        var activePage = <?php echo json_encode($firstPageKey); ?>;

        $('#adminUserSelect').select2({
            placeholder: 'Search admin user…',
            allowClear: true,
            width: '100%'
        });

        function showStatus(msg, ok) {
            var el = document.getElementById('statusAlert');
            el.textContent = msg;
            el.className = 'mb-4 px-4 py-3 rounded-lg text-sm font-medium ' +
                (ok ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-red-50 text-red-700 border border-red-200');
            el.classList.remove('hidden');
        }

        function switchPage(pageKey) {
            activePage = pageKey;
            document.querySelectorAll('.page-tab').forEach(function (tab) {
                var on = tab.getAttribute('data-page') === pageKey;
                tab.classList.toggle('active', on);
                tab.setAttribute('aria-selected', on ? 'true' : 'false');
            });
            document.querySelectorAll('.page-panel').forEach(function (panel) {
                panel.classList.toggle('active', panel.getAttribute('data-page') === pageKey);
            });
        }

        document.querySelectorAll('.page-tab').forEach(function (tab) {
            tab.addEventListener('click', function () {
                switchPage(tab.getAttribute('data-page'));
            });
        });

        function clearAllChecks() {
            document.querySelectorAll('.perm-check').forEach(function (cb) { cb.checked = false; });
        }

        function applyMatrix(matrix) {
            clearAllChecks();
            Object.keys(matrix || {}).forEach(function (deptId) {
                var perms = matrix[deptId];
                var keys = Array.isArray(perms) ? perms : Object.keys(perms || {});
                keys.forEach(function (key) {
                    var cb = document.querySelector('.perm-check[data-dept="' + deptId + '"][data-perm="' + key + '"]');
                    if (cb) cb.checked = true;
                });
            });
        }

        function collectMatrix() {
            var matrix = {};
            document.querySelectorAll('.perm-check:checked').forEach(function (cb) {
                var dept = cb.getAttribute('data-dept');
                var perm = cb.getAttribute('data-perm');
                if (!matrix[dept]) matrix[dept] = [];
                if (matrix[dept].indexOf(perm) === -1) matrix[dept].push(perm);
            });
            return matrix;
        }

        $('#adminUserSelect').on('change', function () {
            selectedUserId = parseInt($(this).val(), 10) || 0;
            var panel = document.getElementById('permissionsPanel');
            var legacy = document.getElementById('legacyNote');
            document.getElementById('statusAlert').classList.add('hidden');

            if (!selectedUserId) {
                panel.classList.add('hidden');
                legacy.classList.add('hidden');
                clearAllChecks();
                return;
            }

            panel.classList.remove('hidden');
            clearAllChecks();

            fetch('action.php?action=load&user_id=' + selectedUserId)
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (!data.ok) {
                        showStatus(data.message || 'Failed to load', false);
                        return;
                    }
                    applyMatrix(data.permissions || {});
                    legacy.classList.toggle('hidden', !!data.configured);
                })
                .catch(function () {
                    showStatus('Could not load permissions.', false);
                });
        });

        document.querySelectorAll('.page-check-all').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var page = btn.getAttribute('data-page');
                document.querySelectorAll('.perm-check[data-perm="' + page + '"]').forEach(function (cb) {
                    cb.checked = true;
                });
            });
        });

        document.querySelectorAll('.page-uncheck-all').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var page = btn.getAttribute('data-page');
                document.querySelectorAll('.perm-check[data-perm="' + page + '"]').forEach(function (cb) {
                    cb.checked = false;
                });
            });
        });

        document.getElementById('savePermsBtn')?.addEventListener('click', function () {
            if (!selectedUserId) return;
            var fd = new FormData();
            fd.append('action', 'save');
            fd.append('user_id', String(selectedUserId));
            fd.append('permissions', JSON.stringify(collectMatrix()));

            fetch('action.php', { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    showStatus(data.message || (data.ok ? 'Saved.' : 'Error'), !!data.ok);
                    if (data.ok) document.getElementById('legacyNote').classList.add('hidden');
                })
                .catch(function () { showStatus('Save failed.', false); });
        });

        document.getElementById('clearUserPermsBtn')?.addEventListener('click', function () {
            if (!selectedUserId) return;
            if (!confirm('Alisin lahat ng restrictions? Magkakaroon ulit ng full approve access ang admin na ito.')) return;
            var fd = new FormData();
            fd.append('action', 'save');
            fd.append('user_id', String(selectedUserId));
            fd.append('permissions', JSON.stringify({}));

            fetch('action.php', { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    showStatus(data.message || (data.ok ? 'Cleared.' : 'Error'), !!data.ok);
                    if (data.ok) {
                        clearAllChecks();
                        document.getElementById('legacyNote').classList.remove('hidden');
                    }
                });
        });
    })();
    </script>
</body>
</html>
