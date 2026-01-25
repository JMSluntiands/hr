// Universal sidebar dropdown functionality using event delegation
// This works across all pages and doesn't require DOMContentLoaded

(function() {
    'use strict';
    
    // Use event delegation on document level so it works even after navigation
    document.addEventListener('click', function(e) {
        // Check if clicked element is a dropdown button or inside one
        let dropdownBtn = e.target.closest('[id$="-dropdown-btn"]');
        
        // Also check if clicked on SVG or span inside the button
        if (!dropdownBtn) {
            const parent = e.target.closest('.dropdown-container');
            if (parent) {
                dropdownBtn = parent.querySelector('[id$="-dropdown-btn"]');
            }
        }
        
        if (dropdownBtn) {
            e.preventDefault();
            e.stopPropagation();
            
            const btnId = dropdownBtn.id;
            const dropdownType = btnId.replace('-dropdown-btn', '');
            const dropdown = document.getElementById(dropdownType + '-dropdown');
            const arrow = document.getElementById(dropdownType + '-arrow');
            
            if (!dropdown) return;
            
            // Close all other dropdowns
            const allDropdowns = document.querySelectorAll('[id$="-dropdown"]');
            const allArrows = document.querySelectorAll('[id$="-arrow"]');
            
            allDropdowns.forEach(function(dd) {
                if (dd !== dropdown) {
                    dd.classList.add('hidden');
                }
            });
            
            allArrows.forEach(function(arr) {
                if (arr !== arrow) {
                    arr.style.transform = 'rotate(0deg)';
                }
            });
            
            // Toggle current dropdown
            const isHidden = dropdown.classList.contains('hidden');
            dropdown.classList.toggle('hidden');
            
            if (arrow) {
                arrow.style.transform = isHidden ? 'rotate(180deg)' : 'rotate(0deg)';
            }
        }
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        const dropdownBtn = e.target.closest('[id$="-dropdown-btn"]');
        const dropdown = e.target.closest('[id$="-dropdown"]');
        const dropdownContainer = e.target.closest('.dropdown-container');
        
        if (!dropdownBtn && !dropdown && !dropdownContainer) {
            // Clicked outside, close all dropdowns
            const allDropdowns = document.querySelectorAll('[id$="-dropdown"]');
            const allArrows = document.querySelectorAll('[id$="-arrow"]');
            
            allDropdowns.forEach(function(dd) {
                dd.classList.add('hidden');
            });
            
            allArrows.forEach(function(arr) {
                arr.style.transform = 'rotate(0deg)';
            });
        }
    });
})();
