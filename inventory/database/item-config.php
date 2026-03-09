<?php

function getInventoryItemOptions(): array
{
    return [
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
}

function getInventoryItemPrefixes(): array
{
    return [
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
}
