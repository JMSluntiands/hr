<style id="hr-sidebar-styles">
/* Sidebar shell */
#admin-sidebar,
#employee-sidebar,
#inventory-sidebar {
    z-index: 40;
    background: linear-gradient(180deg, #ffb347 0%, #fa9800 42%, #e8870a 100%);
    box-shadow: 4px 0 24px rgba(30, 30, 45, 0.12);
}

#admin-sidebar::before,
#employee-sidebar::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(90deg, rgba(255, 255, 255, 0.08) 0%, transparent 35%);
    pointer-events: none;
    z-index: 0;
}

#admin-sidebar > *,
#employee-sidebar > * {
    position: relative;
    z-index: 1;
}

/* Hide ugly OS scrollbar — scroll still works (wheel / touch) */
.hr-sidebar-nav {
    scrollbar-width: none;
    -ms-overflow-style: none;
    -webkit-overflow-scrolling: touch;
    overscroll-behavior: contain;
}

.hr-sidebar-nav::-webkit-scrollbar {
    width: 0;
    height: 0;
    display: none;
}

/* Nav links */
.hr-sidebar-link {
    transition: background-color 0.15s ease, transform 0.15s ease, box-shadow 0.15s ease;
}

.hr-sidebar-link:hover {
    background-color: rgba(255, 255, 255, 0.12);
}

.hr-sidebar-link.is-active {
    background-color: rgba(255, 255, 255, 0.22);
    box-shadow: inset 3px 0 0 rgba(255, 255, 255, 0.85);
}

.hr-sidebar-sublink {
    transition: background-color 0.15s ease;
    border-radius: 0.5rem;
}

.hr-sidebar-sublink:hover {
    background-color: rgba(255, 255, 255, 0.1);
}

.hr-sidebar-sublink.is-active {
    background-color: rgba(255, 255, 255, 0.18);
}

.hr-sidebar-dropdown-btn {
    transition: background-color 0.15s ease;
}

.hr-sidebar-dropdown-btn:hover {
    background-color: rgba(255, 255, 255, 0.1);
}

.hr-sidebar-dropdown-btn.is-open {
    background-color: rgba(255, 255, 255, 0.14);
}

.hr-sidebar-footer {
    background: rgba(0, 0, 0, 0.06);
    backdrop-filter: blur(4px);
}
</style>
