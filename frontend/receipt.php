<?php
require_once '../backend/db_connect.php';
require_once '../backend/auth.php';
require_customer();
$id=(int)($_GET['id']??0); $email=$_SESSION['driver_email'];
$s=mysqli_prepare($con,"SELECT r.*,p.name parking_name,p.location,p.city,p.street FROM requests r LEFT JOIN parkings p ON p.id=r.parking_id WHERE r.id=? AND r.customer=? LIMIT 1");
mysqli_stmt_bind_param($s,'is',$id,$email); mysqli_stmt_execute($s); $b=mysqli_fetch_assoc(mysqli_stmt_get_result($s));
if(!$b || ($b['payment_status']??'')!=='paid'){ http_response_code(404); exit('Paid receipt not found.'); }
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Payment Receipt | SmartPark</title><style>
body{font-family:Arial,sans-serif;background:#eef2f7;margin:0;padding:30px;color:#111827}.receipt{max-width:720px;margin:auto;background:white;border-radius:18px;padding:34px;box-shadow:0 15px 50px rgba(0,0,0,.12)}.head{display:flex;justify-content:space-between;gap:20px;border-bottom:2px solid #111827;padding-bottom:20px}.brand{font-size:28px;font-weight:900}.paid{font-weight:900;color:#15803d}.grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin:24px 0}.item{padding:14px;background:#f8fafc;border-radius:10px}.label{font-size:11px;text-transform:uppercase;color:#64748b}.value{font-weight:800;margin-top:5px}.total{display:flex;justify-content:space-between;border-top:2px solid #111827;padding-top:18px;font-size:22px}.actions{margin-top:25px;display:flex;gap:10px}.btn{padding:12px 18px;border-radius:9px;text-decoration:none;border:0;cursor:pointer;background:#111827;color:white}.btn.light{background:#e5e7eb;color:#111827}@media print{body{background:white;padding:0}.receipt{box-shadow:none}.actions{display:none}}
</style></head><body><div class="receipt">
<div class="head"><div><div class="brand">SmartPark</div><div>Parking Payment Receipt</div></div><div class="paid">✓ PAYMENT PAID</div></div>
<div class="grid">
<div class="item"><div class="label">Receipt No.</div><div class="value"><?=e($b['receipt_no'])?></div></div>
<div class="item"><div class="label">Reservation</div><div class="value">#<?=e($b['id'])?></div></div>
<div class="item"><div class="label">Customer</div><div class="value"><?=e($_SESSION['driver_name']??$email)?></div></div>
<div class="item"><div class="label">Payment Date</div><div class="value"><?=e($b['paid_at']??date('Y-m-d H:i:s'))?></div></div>
<div class="item"><div class="label">Parking</div><div class="value"><?=e($b['parking_name'])?></div></div>
<div class="item"><div class="label">Location</div><div class="value"><?=e(($b['city']??'').' · '.($b['street']??$b['location']??''))?></div></div>
<div class="item"><div class="label">Hours</div><div class="value"><?=e($b['hours'])?> hour(s)</div></div>
<div class="item"><div class="label">Payment Method</div><div class="value"><?=e($b['payment_method'])?></div></div>
<div class="item"><div class="label">Transaction ID</div><div class="value"><?=e($b['razorpay_payment_id']??'')?></div></div>
</div>
<div class="total"><span>Total Paid</span><strong>₹<?=number_format((float)$b['cost'],2)?></strong></div>
<div class="actions"><button class="btn" onclick="window.print()">Print / Save PDF</button><a class="btn light" href="home.php">Dashboard</a></div>
</div></body></html>