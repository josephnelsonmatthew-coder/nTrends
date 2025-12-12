<?php
// modules/billing/view_bill.php
require '../../config/db.php';

$phone = $_GET['phone'] ?? '';
$date = $_GET['date'] ?? '';
$time = $_GET['time'] ?? '';

if (!$phone || !$date || !$time) die("Invalid Invoice Request.");

// Fetch Data
$sql = "SELECT a.id, a.appointment_date, a.appointment_time, a.client_name, a.client_phone, 
               e.name as employee_name, s.service_name, s.price
        FROM appointments a
        LEFT JOIN employees e ON a.employee_id = e.id
        LEFT JOIN services s ON a.service_id = s.id
        WHERE a.client_phone = ? AND a.appointment_date = ? AND a.appointment_time = ? AND a.status = 'Completed'";

$stmt = $pdo->prepare($sql);
$stmt->execute([$phone, $date, $time]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($items) === 0) die("Invoice data not found.");

$clientName = $items[0]['client_name'];
$clientPhone = $items[0]['client_phone'];
$billDate = date('d/m/Y h:i A', strtotime($items[0]['appointment_date'] . ' ' . $items[0]['appointment_time']));
$billId = $items[0]['id'];
$grandTotal = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #<?php echo $billId; ?></title>
    <style>
        body { font-family: Arial, sans-serif; color: #000; padding: 20px; max-width: 400px; margin: 0 auto; border: 1px solid #ddd; }
        .header { text-align: center; margin-bottom: 20px; }
        .logo { font-size: 28px; font-weight: bold; color: #444; letter-spacing: -1px; }
        .sub-logo { font-size: 10px; text-transform: uppercase; letter-spacing: 2px; margin-top: -5px; display: block; }
        .address { font-size: 11px; margin-top: 10px; line-height: 1.4; border-bottom: 1px solid #000; padding-bottom: 10px; }
        
        .client-info { font-size: 12px; margin: 15px 0; line-height: 1.6; }
        .label { display: inline-block; width: 80px; }

        .table { width: 100%; border-collapse: collapse; font-size: 12px; margin-bottom: 15px; }
        .table th { border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 5px 0; text-align: left; }
        .table td { padding: 8px 0; }
        .text-right { text-align: right; }

        .totals { font-size: 13px; border-top: 1px solid #000; padding-top: 10px; }
        .row { display: flex; justify-content: space-between; margin-bottom: 5px; }
        .grand-row { font-weight: bold; font-size: 16px; margin-top: 10px; }

        .footer { text-align: center; font-size: 10px; margin-top: 30px; border-top: 1px solid #ccc; padding-top: 10px; }
        
        @media print {
            body { border: none; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align:right; margin-bottom:10px;">
        <button onclick="window.print()">Print / Save PDF</button>
    </div>

    <div class="header">
        <span class="logo">nTrends</span>
        <span class="sub-logo">Unisex Hair & Style Salon</span>
        <div class="address">
            NO 58, M G ROAD, BESANT NAGAR, CHENNAI<br>
            Ph: 9697178178 | GST: N/A
        </div>
    </div>

    <div class="client-info">
        <div><span class="label">Name</span> : <?php echo htmlspecialchars($clientName); ?></div>
        <div><span class="label">Phone</span> : <?php echo htmlspecialchars($clientPhone); ?></div>
        <div><span class="label">Date</span> : <?php echo $billDate; ?></div>
        <div><span class="label">Bill No</span> : <?php echo $billId; ?></div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th width="50%">PARTICULARS</th>
                <th width="15%">QTY</th>
                <th class="text-right">RATE</th>
                <th class="text-right">AMT</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): $grandTotal += $item['price']; ?>
            <tr>
                <td><?php echo htmlspecialchars($item['service_name']); ?></td>
                <td>1</td>
                <td class="text-right"><?php echo number_format($item['price'], 0); ?></td>
                <td class="text-right"><?php echo number_format($item['price'], 0); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="totals">
        <div class="row"><span>Net Amount</span><span><?php echo number_format($grandTotal, 2); ?></span></div>
        <div class="row"><span>Tax (0%)</span><span>0.00</span></div>
        <div class="row grand-row"><span>BILL AMOUNT</span><span>₹<?php echo number_format($grandTotal, 2); ?></span></div>
    </div>

    <div class="footer">
        THANK YOU. HAVE A NICE DAY.<br>
        SYSTEM GENERATED INVOICE.
    </div>
</body>
</html>