<?php
require_once '../backend/db_connect.php';
require_once '../backend/auth.php';
require_customer();
require_once '../backend/config.php';

$id=(int)($_GET['id']??0);
$email=$_SESSION['driver_email'];

$s=mysqli_prepare($con,'SELECT r.*,p.name parking_name,p.location,p.city,p.street,p.price parking_price FROM requests r LEFT JOIN parkings p ON p.id=r.parking_id WHERE r.id=? AND r.customer=? LIMIT 1');
mysqli_stmt_bind_param($s,'is',$id,$email); mysqli_stmt_execute($s);
$booking=mysqli_fetch_assoc(mysqli_stmt_get_result($s));
if(!$booking){ http_response_code(404); exit('Reservation not found.'); }
$amount=(float)$booking['cost'];
$amountString=number_format($amount,2,'.','');
$upiPayload='upi://pay?pa='.rawurlencode(SMARTPARK_MERCHANT_UPI)
    .'&pn='.rawurlencode(SMARTPARK_MERCHANT_NAME)
    .'&am='.rawurlencode($amountString)
    .'&cu=INR'
    .'&tr='.rawurlencode('SP-'.$id)
    .'&tn='.rawurlencode('SmartPark Reservation #'.$id);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Secure Payment | SmartPark</title>
<link rel="stylesheet" href="assets/css/smartpark-motion.css"><link rel="stylesheet" href="assets/css/smartpark-theme.css"><link rel="stylesheet" href="assets/css/smartpark-refresh.css"><link rel="stylesheet" href="assets/font-awesome/css/font-awesome.css">
<style>
.payment-layout{display:grid;grid-template-columns:minmax(300px,450px) 1fr;gap:24px;align-items:start}.qr-card{background:#0b0b0d;border:1px solid #25252b;border-radius:22px;padding:22px;text-align:center;color:#fff;box-shadow:0 20px 60px rgba(0,0,0,.18);position:sticky;top:20px}.merchant-qr-box{background:#fff;padding:12px;border-radius:16px;width:min(100%,260px);margin:18px auto 10px;box-shadow:0 8px 25px rgba(0,0,0,.15)}#smartparkGeneratedQr{display:grid;place-items:center;min-height:220px}.qr-caption{font-size:12px;color:#aeb0b8;margin-top:8px;line-height:1.55}.scanner-badge{display:inline-block;background:#24242a;color:#fff;border:1px solid #3b3b44;border-radius:999px;padding:6px 10px;font-size:11px;font-weight:800;letter-spacing:.05em;text-transform:uppercase}.qr-title{font-size:22px;font-weight:900;margin:0 0 6px}.qr-sub{color:#c7c7ce;margin:0}.merchant-upi{background:#17171b;border:1px solid #33333a;border-radius:14px;padding:13px;margin-top:14px;text-align:left}.merchant-upi span{display:block;color:#aaa;font-size:12px;text-transform:uppercase;letter-spacing:.08em}.merchant-upi strong{font-size:17px;word-break:break-all}.amount-big{font-size:34px;font-weight:900;margin:8px 0}.method-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin:18px 0}.method-option{position:relative}.method-option input{position:absolute;opacity:0}.method-option label{display:block;border:2px solid #e1e6ed;border-radius:16px;padding:16px;cursor:pointer;background:#fff;transition:.2s;height:100%;box-sizing:border-box}.method-option input:checked+label{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.12);background:#f8fbff}.method-icon{font-size:25px;margin-bottom:8px}.method-title{font-weight:800}.method-desc{font-size:12px;color:#64748b;margin-top:4px}.payment-panel{border:1px solid #e1e6ed;border-radius:16px;padding:18px;margin-top:12px}.pay-note{font-size:12px;line-height:1.6;color:#64748b}.hidden{display:none!important}.success-actions{display:flex;gap:10px;flex-wrap:wrap}.secure{font-size:12px;color:#64748b;margin-top:12px}.spinner{display:none}.processing .spinner{display:inline-block}.processing .pay-label{display:none}.disabled{opacity:.45;pointer-events:none}.scanner-shell{background:#0f172a;border-radius:16px;padding:12px}.scanner-shell #qrReader{width:100%;overflow:hidden;border-radius:12px}.qr-scan-status{margin-top:10px;padding:10px 12px;border-radius:12px;font-size:12px;line-height:1.5;background:#eff6ff;color:#1d4ed8}.qr-scan-status.success{background:#ecfdf5;color:#047857}.qr-scan-status.error{background:#fef2f2;color:#b91c1c}.qr-scan-status.info{background:#eff6ff;color:#1d4ed8}.scan-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px}.qr-verification{padding:11px 13px;border-radius:12px;margin-top:12px;font-weight:700;font-size:13px;background:#f8fafc;color:#475569;border:1px solid #e2e8f0}.qr-verification.verified{background:#ecfdf5;color:#047857;border-color:#a7f3d0}.qr-verification.rejected{background:#fef2f2;color:#b91c1c;border-color:#fecaca}.scan-details{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:10px}.scan-detail{padding:10px 12px;background:#f8fafc;border-radius:12px;border:1px solid #e2e8f0}.scan-detail span{display:block;font-size:10px;text-transform:uppercase;letter-spacing:.08em;color:#64748b}.scan-detail strong{display:block;margin-top:4px;font-size:13px;word-break:break-word}.amount-field{font-size:26px;font-weight:900}.qr-warning{font-size:12px;padding:11px 13px;border-radius:12px;background:#fff7ed;color:#9a3412;margin-top:12px}.upi-actions{display:flex;gap:8px;flex-wrap:wrap}.upi-actions .btn{flex:1;min-width:180px}@media(max-width:850px){.payment-layout{grid-template-columns:1fr}.qr-card{position:static}.method-grid{grid-template-columns:1fr}.scan-details{grid-template-columns:1fr}}
</style>
</head>
<body>
<header class="topbar"><a class="brand" href="home.php"><i class="fa fa-car"></i>Smart<b>Park</b></a><nav class="navlinks"><a href="home.php">Dashboard</a><a href="customer_profile.php">My Profile</a><a href="javascript:history.back()">Back</a><a class="btn btn-light" href="logout.php">Logout</a></nav></header>
<main class="dashboard"><div class="container narrow">
<div class="card" style="margin-bottom:20px"><h1>Secure Payment</h1><p class="muted">Reservation #<?=e($booking['id'])?> · <?=e($booking['parking_name']??'Parking')?></p><div style="font-size:30px;font-weight:900">₹<?=number_format($amount,2)?></div><p class="muted"><?=e($booking['hours'])?> hour(s) · <?=e($booking['city']??$booking['location']??'')?></p></div>
<div class="payment-layout">
<div class="qr-card" id="upiQrCard">
<div class="qr-title">SmartPark Exact-Amount QR</div><p class="qr-sub">This QR encodes reservation #<?=e($id)?> and ₹<?=number_format($amount,2)?> automatically.</p>
<div class="amount-big">₹<?=number_format($amount,2)?></div><span class="scanner-badge">Dynamic UPI payload</span>
<div class="merchant-qr-box"><div id="smartparkGeneratedQr"></div></div>
<p class="qr-caption">Use this QR with any compatible UPI app, or scan a parking-meter/attendant QR with SmartPark on the right.</p>
<div class="merchant-upi"><span>Merchant UPI</span><strong><?=e(SMARTPARK_MERCHANT_UPI)?></strong></div>
<a class="btn btn-primary" style="width:100%;box-sizing:border-box;margin-top:14px" href="<?=e($upiPayload)?>"><i class="fa fa-mobile"></i> OPEN UPI APP — ₹<?=number_format($amount,2)?></a>
<p class="secure">The QR amount is generated from the server-side reservation cost. Never trust an amount entered manually by the customer.</p>
</div>
<div class="card form-card">
<h2>Choose Payment Method</h2><div id="message"></div>
<?php if(($booking['payment_status']??'unpaid')==='paid'):?>
<div class="alert alert-success">This reservation is already paid.</div><div class="success-actions"><a class="btn btn-primary" href="receipt.php?id=<?=e($id)?>"><i class="fa fa-file-text-o"></i> View Payment Receipt</a><a class="btn btn-light" href="home.php">Dashboard</a></div>
<?php else:?>
<div class="method-grid">
<div class="method-option"><input type="radio" id="methodUpi" name="payment_method" value="UPI" checked><label for="methodUpi"><div class="method-icon"><i class="fa fa-qrcode"></i></div><div class="method-title">UPI / QR</div><div class="method-desc">Scan parking-meter QR and auto-fill the amount</div></label></div>
<div class="method-option"><input type="radio" id="methodOnline" name="payment_method" value="Online"><label for="methodOnline"><div class="method-icon"><i class="fa fa-credit-card"></i></div><div class="method-title">Card / Net Banking</div><div class="method-desc">Secure Razorpay checkout with server verification</div></label></div>
<div class="method-option"><input type="radio" id="methodCash" name="payment_method" value="Cash"><label for="methodCash"><div class="method-icon"><i class="fa fa-money"></i></div><div class="method-title">Cash</div><div class="method-desc">Pay at parking location</div></label></div>
</div>

<div id="onlinePanel" class="payment-panel hidden">
<h3><i class="fa fa-lock"></i> Pay securely</h3><p>Your exact reservation amount is sent to Razorpay. After payment, SmartPark verifies the signature on the server before marking the reservation paid.</p>
<button id="payBtn" type="button" class="btn btn-primary" style="width:100%" onclick="startPayment()"><span class="pay-label"><i class="fa fa-credit-card"></i> PAY ₹<?=number_format($amount,2)?> NOW</span><span class="spinner"><i class="fa fa-spinner fa-spin"></i> Creating secure payment...</span></button>
</div>

<div id="cashPanel" class="payment-panel hidden" style="background:#fff8e8"><h3><i class="fa fa-money"></i> Cash on Payment</h3><p>Your reservation stays active with payment status <strong>Pending</strong> until the attendant collects the cash.</p><button type="button" class="btn btn-primary" style="width:100%" onclick="cashPayment()">CONFIRM CASH PAYMENT</button></div>

<div id="upiPanel" class="payment-panel">
<h3><i class="fa fa-camera"></i> Scan Parking QR</h3>
<p>Scan the QR displayed by the parking meter or attendant. SmartPark will extract the merchant, currency and parking amount, then verify the amount against this reservation.</p>
<div class="scanner-shell"><div id="qrReader" class="hidden"></div></div>
<div class="scan-actions"><button id="startQrScannerBtn" type="button" class="btn btn-primary"><i class="fa fa-camera"></i> START CAMERA</button><button id="stopQrScannerBtn" type="button" class="btn btn-light">STOP</button><label class="btn btn-light" for="scanQrImage"><i class="fa fa-image"></i> SCAN IMAGE</label><input id="scanQrImage" type="file" accept="image/*" style="display:none"></div>
<div id="qrScanStatus" class="qr-scan-status info">Ready. Press START CAMERA to scan a parking QR.</div>
<div class="scan-details"><div class="scan-detail"><span>Merchant</span><strong id="scannedMerchant">—</strong></div><div class="scan-detail"><span>Merchant UPI</span><strong id="scannedUpi">—</strong></div><div class="scan-detail"><span>QR transaction reference</span><strong id="scannedRef">—</strong></div><div class="scan-detail"><span>Scanned amount</span><strong id="scannedAmount">—</strong></div></div>
<div id="qrVerification" class="qr-verification">No QR verified yet.</div>
<div class="payment-panel" style="margin-top:14px;background:#0f172a;color:#fff;border-color:#1e293b">
<label style="display:block;color:#94a3b8;font-size:11px;text-transform:uppercase;letter-spacing:.08em">Payment amount (auto-populated)</label>
<input id="paymentAmount" class="form-control amount-field" type="text" value="<?=e($amountString)?>" readonly style="margin-top:8px;background:#fff;color:#0f172a">
<p style="font-size:12px;color:#cbd5e1;line-height:1.55;margin-bottom:0">This field is read-only. It is populated from the verified QR payload; the reservation cost remains the server-side authority.</p>
</div>
<div class="upi-actions" style="margin-top:14px"><a id="upiPayBtn" class="btn btn-primary disabled" href="#"><i class="fa fa-mobile"></i> OPEN SCANNED UPI PAYMENT</a></div>
<div class="qr-warning">QR verification confirms that the QR carries the expected merchant and amount. It does not by itself prove that payment succeeded. For server-confirmed payment status, use Razorpay Online or your payment provider webhook integration.</div>
</div>
<?php endif;?>
</div></div></div></main>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
const requestId=<?=json_encode($id)?>;
const generatedUpiPayload=<?=json_encode($upiPayload)?>;
window.SMARTPARK_QR_CONFIG={requestId:requestId,generatedUpiPayload:generatedUpiPayload};
const upi=document.getElementById('methodUpi'), online=document.getElementById('methodOnline'), cash=document.getElementById('methodCash');
const upiPanel=document.getElementById('upiPanel'), onlinePanel=document.getElementById('onlinePanel'), cashPanel=document.getElementById('cashPanel'), qr=document.getElementById('upiQrCard');
function updateUI(){
 const v=document.querySelector('input[name=payment_method]:checked')?.value;
 upiPanel?.classList.toggle('hidden',v!=='UPI'); onlinePanel?.classList.toggle('hidden',v!=='Online'); cashPanel?.classList.toggle('hidden',v!=='Cash'); qr?.classList.toggle('hidden',v==='Cash');
}
[upi,online,cash].forEach(x=>x?.addEventListener('change',updateUI)); updateUI();

async function startPayment(){
 const btn=document.getElementById('payBtn'); if(btn) btn.classList.add('processing');
 document.getElementById('message').innerHTML='';
 try{
  const fd=new FormData(); fd.append('request_id',requestId);
  const r=await fetch('../backend/api/create_razorpay_order.php',{method:'POST',body:fd});
  const data=await r.json();
  if(!data.ok) throw new Error(data.message||'Could not start payment.');
  const options={key:data.key_id,amount:data.amount,currency:data.currency,name:data.name,description:data.description,order_id:data.order_id,prefill:{name:data.prefill_name,email:data.prefill_email},theme:{color:'#2563eb'},handler:async function(response){
    const vf=new FormData(); vf.append('request_id',requestId); vf.append('razorpay_order_id',response.razorpay_order_id); vf.append('razorpay_payment_id',response.razorpay_payment_id); vf.append('razorpay_signature',response.razorpay_signature);
    const vr=await fetch('../backend/api/verify_razorpay_payment.php',{method:'POST',body:vf}); const vd=await vr.json();
    if(vd.ok) window.location.href=vd.receipt_url; else {document.getElementById('message').innerHTML='<div class="alert alert-error">'+escapeHtml(vd.message||'Verification failed')+'</div>';if(btn)btn.classList.remove('processing');}
  },modal:{ondismiss:function(){if(btn)btn.classList.remove('processing');}},method:{upi:true,card:true,netbanking:true,wallet:true}};
  new Razorpay(options).open();
 }catch(e){document.getElementById('message').innerHTML='<div class="alert alert-error">'+escapeHtml(e.message)+'</div>';if(btn)btn.classList.remove('processing');}
}
async function cashPayment(){try{const fd=new FormData();fd.append('request_id',requestId);const r=await fetch('../backend/api/mark_cash_payment.php',{method:'POST',body:fd});const d=await r.json();if(d.ok)location.reload();else document.getElementById('message').innerHTML='<div class="alert alert-error">'+escapeHtml(d.message)+'</div>';}catch(e){document.getElementById('message').innerHTML='<div class="alert alert-error">Could not update cash status.</div>';}}
function escapeHtml(s){return String(s??'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]))}
</script>
<script src="assets/js/smartpark-qr.js"></script>
<script>
// When a parking QR is verified, make its exact amount and merchant available to the UPI deep link.
document.addEventListener('DOMContentLoaded',()=>{
 const btn=document.getElementById('upiPayBtn');
 const amountEl=document.getElementById('paymentAmount');
 const originalHandler=window.SmartParkQR;
 // smartpark-qr.js updates the verification UI; watch for verified state and derive a safe UPI deep link.
 const verifier=document.getElementById('qrVerification');
 const observer=new MutationObserver(()=>{
   if(!btn || !verifier || !verifier.classList.contains('verified')) return;
   const merchant=(document.getElementById('scannedUpi')?.textContent||'').trim();
   const amount=(amountEl?.value||'').trim();
   if(!merchant || !/^[^\\s@]+@[^\\s@]+$/.test(merchant) || !/^\\d+(?:\\.\\d{1,2})?$/.test(amount)) return;
   const params=new URLSearchParams({pa:merchant,pn:document.getElementById('scannedMerchant')?.textContent||'SmartPark',am:Number(amount).toFixed(2),cu:'INR',tr:document.getElementById('scannedRef')?.textContent||'',tn:'SmartPark Reservation #'+requestId});
   btn.href='upi://pay?'+params.toString();
   btn.classList.remove('disabled');
 });
 verifier && observer.observe(verifier,{attributes:true,childList:true,subtree:true});
});
</script>
</body></html>
