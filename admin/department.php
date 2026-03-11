<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$adminName = $_SESSION['name'] ?? 'Admin User';
$role      = $_SESSION['role'] ?? 'admin';

include '../database/db.php';

// Fetch departments
$departments = [];
if ($conn) {
    $result = $conn->query("SELECT id, name, created_at FROM departments ORDER BY name");
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $departments[] = $row;
        }
    }
}

$flashMessage = $_SESSION['department_msg'] ?? '';
unset($_SESSION['department_msg']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department Management - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { inter: ['Inter', 'sans-serif'] },
                    colors: {
                        luntianBlue: '#FA9800',
                        luntianLight: '#f3f4ff'
                    }
                }
            }
        }
    </script>
</head>
<body class="font-inter bg-[#f1f5f9] min-h-screen">
<?php include __DIR__ . '/include/sidebar-admin.php'; ?>

<main class="min-h-screen overflow-y-auto p-4 pt-16 md:pt-8 md:ml-64 md:p-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-slate-800">Departments</h1>
            <p class="text-sm text-slate-500 mt-1">Manage master list of departments used in employees.</p>
        </div>
    </div>

    <?php if ($flashMessage): ?>
        <div class="mb-4 px-4 py-3 rounded-lg text-sm font-medium <?php echo strpos($flashMessage, '✓') !== false ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200'; ?>">
            <?php echo htmlspecialchars($flashMessage); ?>
        </div>
    <?php endif; ?>

    <!-- Add Department -->
    <section class="mb-6 bg-white rounded-xl shadow-sm border border-slate-100 p-5">
        <h2 class="text-sm font-semibold text-slate-700 mb-3">Add Department</h2>
        <form action="department-action.php" method="POST" class="flex flex-col md:flex-row gap-3 items-stretch md:items-end">
            <input type="hidden" name="action" value="create">
            <div class="flex-1">
                <label class="block text-xs font-medium text-slate-600 mb-1">Department Name</label>
                <input type="text" name="name" required
                       placeholder="Enter department name"
                       class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#d97706]/20 focus:border-[#d97706]">
            </div>
            <button type="submit"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-[#d97706] hover:bg-[#b45309] text-white text-sm font-medium shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add
            </button>
        </form>
    </section>

    <!-- Departments List -->
    <section class="bg-white rounded-xl shadow-sm border border-slate-100">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-700">Department List</h2>
        </div>
        <div class="p-5 overflow-x-auto">
            <table id="departmentTable" class="min-w-full text-sm">
                <thead>
                <tr class="border-b border-slate-200 bg-slate-50">
                    <th class="text-left px-4 py-2 text-xs font-semibold text-slate-600 uppercase tracking-wide">Name</th>
                    <th class="text-left px-4 py-2 text-xs font-semibold text-slate-600 uppercase tracking-wide">Created</th>
                    <th class="text-right px-4 py-2 text-xs font-semibold text-slate-600 uppercase tracking-wide">Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($departments as $dept): ?>
                    <tr class="border-b border-slate-100 hover:bg-slate-50">
                        <td class="px-4 py-3 text-slate-700">
                            <span class="font-medium"><?php echo htmlspecialchars($dept['name']); ?></span>
                        </td>
                        <td class="px-4 py-3 text-slate-500">
                            <?php echo $dept['created_at'] ? date('M d, Y', strtotime($dept['created_at'])) : '—'; ?>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <button type="button"
                                        class="edit-dept-btn p-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white transition-colors text-xs"
                                        data-id="<?php echo (int)$dept['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($dept['name']); ?>">
                                    Edit
                                </button>
                                <form action="department-action.php" method="POST"
                                      onsubmit="return confirm('Delete this department? This cannot be undone.');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo (int)$dept['id']; ?>">
                                    <button type="submit"
                                            class="p-2 rounded-lg bg-red-600 hover:bg-red-700 text-white transition-colors text-xs">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<!-- Edit Modal -->
<div id="editDeptModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div class="bg-white rounded-xl shadow-xl max-w-md w-full overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-200 flex items-center justify-between">
            <h3 class="text-base font-semibold text-slate-800">Edit Department</h3>
            <button type="button" id="closeEditDeptModal" class="p-1 rounded-md hover:bg-slate-100">
                <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form action="department-action.php" method="POST" class="px-5 py-4 space-y-4">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="editDeptId">
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Department Name</label>
                <input type="text" name="name" id="editDeptName" required
                       class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#d97706]/20 focus:border-[#d97706]">
            </div>
            <div class="flex justify-end gap-2 pt-1">
                <button type="button" id="cancelEditDept"
                        class="px-4 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 rounded-lg text-sm font-medium bg-[#d97706] hover:bg-[#b45309] text-white">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Sidebar dropdowns handled by sidebar-dropdown.js

        const modal = document.getElementById('editDeptModal');
        const closeBtn = document.getElementById('closeEditDeptModal');
        const cancelBtn = document.getElementById('cancelEditDept');
        const idInput = document.getElementById('editDeptId');
        const nameInput = document.getElementById('editDeptName');

        document.querySelectorAll('.edit-dept-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const id = this.dataset.id;
                const name = this.dataset.name;
                idInput.value = id;
                nameInput.value = name;
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            });
        });

        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        closeBtn.addEventListener('click', closeModal);
        cancelBtn.addEventListener('click', function (e) {
            e.preventDefault();
            closeModal();
        });
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                closeModal();
            }
        });

        // Initialize DataTable for Department list
        if (window.jQuery && $('#departmentTable').length) {
            $('#departmentTable').DataTable({
                pageLength: 10,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                order: [[0, 'asc']],
                language: {
                    search: "",
                    searchPlaceholder: "Search departments...",
                    emptyTable: "No departments found."
                },
                dom: '<"flex justify-between items-center mb-4"<"flex gap-2"l><"flex gap-2"f>>rt<"flex justify-between items-center mt-4"<"text-sm text-slate-600"i><"flex gap-2"p>>',
            });
        }
    });
</script>
<script src="include/sidebar-dropdown.js"></script>
</body>
</html>

