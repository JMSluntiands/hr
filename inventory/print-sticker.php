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
require_once __DIR__ . '/database/item-repository.php';

ensureInventoryItemsTable($conn);

$id = (int)($_GET['id'] ?? 0);
$mode = strtolower((string)($_GET['mode'] ?? 'both'));
if (!in_array($mode, ['qr', 'barcode', 'both'], true)) {
    $mode = 'both';
}
$item = $id > 0 ? getInventoryItemById($conn, $id) : null;

if (!$item) {
    http_response_code(404);
    echo 'Item not found.';
    exit;
}

$itemId = (string)$item['item_id'];
$itemName = (string)$item['item_name'];
$description = (string)($item['description'] ?? '');
$type = (string)($item['type'] ?? '');
$remarks = (string)($item['remarks'] ?? '');
$dateArrived = (string)($item['date_arrived'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Sticker - <?php echo htmlspecialchars($itemId); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            margin: 0;
            padding: 20px;
        }
        .actions {
            margin-bottom: 12px;
        }
        .btn {
            display: inline-block;
            background: #FA9800;
            color: #fff;
            border: 0;
            border-radius: 6px;
            padding: 8px 14px;
            font-size: 13px;
            cursor: pointer;
            text-decoration: none;
        }
        .sticker {
            width: 360px;
            background: #fff;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            padding: 12px;
        }
        .title {
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 6px;
        }
        .meta {
            font-size: 11px;
            color: #475569;
            margin-bottom: 10px;
            line-height: 1.4;
        }
        .codes {
            display: flex;
            gap: 10px;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
        }
        #qrcode {
            width: 120px;
            height: 120px;
            border: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .barcode-wrap {
            text-align: center;
        }
        .id-text {
            margin-top: 6px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
        }
        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .actions {
                display: none;
            }
            .sticker {
                border: 1px solid #000;
                border-radius: 0;
                width: 100%;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="actions">
        <button class="btn" onclick="window.print()">Print Sticker</button>
        <a class="btn" href="item.php?tab=list" style="background:#334155;">Back</a>
    </div>

    <div class="sticker">
        <div class="title"><?php echo htmlspecialchars($itemName); ?></div>
        <div class="meta">
            Item ID: <?php echo htmlspecialchars($itemId); ?><br>
            <?php if ($type !== ''): ?>Type: <?php echo htmlspecialchars($type); ?><br><?php endif; ?>
            <?php if ($dateArrived !== ''): ?>Date: <?php echo htmlspecialchars($dateArrived); ?><br><?php endif; ?>
            <?php if ($description !== ''): ?>Description: <?php echo htmlspecialchars($description); ?><br><?php endif; ?>
            <?php if ($remarks !== ''): ?>Remarks: <?php echo htmlspecialchars($remarks); ?><?php endif; ?>
        </div>
        <div class="codes">
            <?php if ($mode === 'qr' || $mode === 'both'): ?>
                <div id="qrcode"></div>
            <?php endif; ?>

            <?php if ($mode === 'barcode' || $mode === 'both'): ?>
                <div class="barcode-wrap">
                    <svg id="barcode"></svg>
                    <div class="id-text"><?php echo htmlspecialchars($itemId); ?></div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
    <script>
        const payload = <?php echo json_encode([
            'id' => $item['id'],
            'item_id' => $itemId,
            'item_name' => $itemName,
            'type' => $type,
            'date_arrived' => $dateArrived
        ]); ?>;

        const mode = <?php echo json_encode($mode); ?>;
        if (mode === 'qr' || mode === 'both') {
            const qrElem = document.getElementById('qrcode');
            if (qrElem) {
                new QRCode(qrElem, {
                    text: JSON.stringify(payload),
                    width: 116,
                    height: 116,
                    correctLevel: QRCode.CorrectLevel.M
                });
            }
        }

        if (mode === 'barcode' || mode === 'both') {
            const barcodeElem = document.getElementById('barcode');
            if (barcodeElem) {
                JsBarcode('#barcode', <?php echo json_encode($itemId); ?>, {
                    format: 'CODE128',
                    lineColor: '#111827',
                    width: 1.6,
                    height: 52,
                    displayValue: false,
                    margin: 0
                });
            }
        }
    </script>
</body>
</html>
