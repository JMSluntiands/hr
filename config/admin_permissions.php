<?php

/**
 * Sidebar permission keys and route mapping.
 * Keys must match legacy/include/admin-permissions.php sidebar groups.
 */
return [
    'inventory' => [
        'inventory_dashboard',
        'inventory_items_list',
        'inventory_items_add',
        'inventory_items_history',
        'inventory_allocation',
        'inventory_nav_requests',
        'inventory_nav_decommission',
        'inventory_report',
        'inventory_messages',
        'inventory_activity_log',
    ],

    'hr' => [
        'hr_nav_leave_requests',
        'hr_nav_documents',
        'hr_nav_document_uploads',
        'hr_nav_bank_requests',
        'hr_nav_reimbursements',
        'hr_nav_document_archive',
    ],

    'routes' => [
        'inventory.dashboard' => 'inventory_dashboard',
        'inventory.items.index' => 'inventory_items_list',
        'inventory.allocation.index' => 'inventory_allocation',
        'inventory.requests.index' => 'inventory_nav_requests',
        'inventory.decommission.index' => 'inventory_nav_decommission',
        'inventory.report.index' => 'inventory_report',
        'inventory.messages.index' => 'inventory_messages',
        'inventory.activity-log.index' => 'inventory_activity_log',
        'admin.leave-requests.index' => 'hr_nav_leave_requests',
        'admin.leaves.approve' => 'hr_nav_leave_requests',
        'admin.leaves.decline' => 'hr_nav_leave_requests',
        'admin.leave-requests.approve' => 'hr_nav_leave_requests',
        'admin.leave-requests.decline' => 'hr_nav_leave_requests',
        'admin.documents.index' => 'hr_nav_documents',
        'admin.documents.approve' => 'hr_nav_documents',
        'admin.documents.decline' => 'hr_nav_documents',
        'admin.document-uploads.index' => 'hr_nav_document_uploads',
        'admin.document-uploads.approve' => 'hr_nav_document_uploads',
        'admin.document-uploads.decline' => 'hr_nav_document_uploads',
        'admin.bank-requests.index' => 'hr_nav_bank_requests',
        'admin.bank-requests.approve' => 'hr_nav_bank_requests',
        'admin.bank-requests.decline' => 'hr_nav_bank_requests',
        'admin.reimbursements.index' => 'hr_nav_reimbursements',
        'admin.reimbursements.list.index' => 'hr_nav_reimbursements',
        'admin.reimbursements.report.index' => 'hr_nav_reimbursements',
        'admin.reimbursements.approve' => 'hr_nav_reimbursements',
        'admin.reimbursements.decline' => 'hr_nav_reimbursements',
        'admin.document-archive.index' => 'hr_nav_document_archive',
        'admin.document-archive.approve' => 'hr_nav_document_archive',
        'admin.document-archive.reject' => 'hr_nav_document_archive',
    ],

    'inventory_item_tabs' => [
        'list' => 'inventory_items_list',
        'add' => 'inventory_items_add',
        'history' => 'inventory_items_history',
    ],
];
