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

$parkingOptions = [];

$ps = mysqli_prepare($con, "
    SELECT id, name, city, location, street, price, remaining_slots
    FROM parkings
    WHERE CAST(remaining_slots AS UNSIGNED) > 0
       OR id = ?
    ORDER BY city, name
");

$currentParkingId = (int)$booking['parking_id'];

mysqli_stmt_bind_param($ps, 'i', $currentParkingId);
mysqli_stmt_execute($ps);

$parkingResult = mysqli_stmt_get_result($ps);

while ($parkingRow = mysqli_fetch_assoc($parkingResult)) {
    $parkingOptions[] = $parkingRow;
}

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Secure Payment | SmartPark</title>
<link rel="stylesheet" href="assets/css/smartpark-motion.css"><link rel="stylesheet" href="assets/css/smartpark-theme.css"><link rel="stylesheet" href="assets/css/smartpark-refresh.css"><link rel="stylesheet" href="assets/font-awesome/css/font-awesome.css">
<style>
.payment-layout{display:grid;grid-template-columns:minmax(300px,450px) 1fr;gap:24px;align-items:start}.qr-card{background:#0b0b0d;border:1px solid #25252b;border-radius:22px;padding:22px;text-align:center;color:#fff;box-shadow:0 20px 60px rgba(0,0,0,.18);position:sticky;top:20px}.merchant-qr-box{background:#fff;padding:12px;border-radius:16px;width:min(100%,260px);margin:18px auto 10px;box-shadow:0 8px 25px rgba(0,0,0,.15)}#smartparkGeneratedQr{display:grid;place-items:center;min-height:220px}.qr-caption{font-size:12px;color:#aeb0b8;margin-top:8px;line-height:1.55}.scanner-badge{display:inline-block;background:#24242a;color:#fff;border:1px solid #3b3b44;border-radius:999px;padding:6px 10px;font-size:11px;font-weight:800;letter-spacing:.05em;text-transform:uppercase}.qr-title{font-size:22px;font-weight:900;margin:0 0 6px}.qr-sub{color:#c7c7ce;margin:0}.merchant-upi{background:#17171b;border:1px solid #33333a;border-radius:14px;padding:13px;margin-top:14px;text-align:left}.merchant-upi span{display:block;color:#aaa;font-size:12px;text-transform:uppercase;letter-spacing:.08em}.merchant-upi strong{font-size:17px;word-break:break-all}.amount-big{font-size:34px;font-weight:900;margin:8px 0}.method-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin:18px 0}.method-option{position:relative}.method-option input{position:absolute;opacity:0}.method-option label{display:block;border:2px solid #e1e6ed;border-radius:16px;padding:16px;cursor:pointer;background:#fff;transition:.2s;height:100%;box-sizing:border-box}.method-option input:checked+label{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.12);background:#f8fbff}.method-icon{font-size:25px;margin-bottom:8px}.method-title{font-weight:800}.method-desc{font-size:12px;color:#64748b;margin-top:4px}.payment-panel{border:1px solid #e1e6ed;border-radius:16px;padding:18px;margin-top:12px}.pay-note{font-size:12px;line-height:1.6;color:#64748b}.hidden{display:none!important}.success-actions{display:flex;gap:10px;flex-wrap:wrap}.secure{font-size:12px;color:#64748b;margin-top:12px}.spinner{display:none}.processing .spinner{display:inline-block}.processing .pay-label{display:none}.disabled{opacity:.45;pointer-events:none}.scanner-shell{background:#0f172a;border-radius:16px;padding:12px}.scanner-shell #qrReader{width:100%;overflow:hidden;border-radius:12px}.qr-scan-status{margin-top:10px;padding:10px 12px;border-radius:12px;font-size:12px;line-height:1.5;background:#eff6ff;color:#1d4ed8}.qr-scan-status.success{background:#ecfdf5;color:#047857}.qr-scan-status.error{background:#fef2f2;color:#b91c1c}.qr-scan-status.info{background:#eff6ff;color:#1d4ed8}.scan-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px}.qr-verification{padding:11px 13px;border-radius:12px;margin-top:12px;font-weight:700;font-size:13px;background:#f8fafc;color:#475569;border:1px solid #e2e8f0}.qr-verification.verified{background:#ecfdf5;color:#047857;border-color:#a7f3d0}.qr-verification.rejected{background:#fef2f2;color:#b91c1c;border-color:#fecaca}.scan-details{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:10px}.scan-detail{padding:10px 12px;background:#f8fafc;border-radius:12px;border:1px solid #e2e8f0}.scan-detail span{display:block;font-size:10px;text-transform:uppercase;letter-spacing:.08em;color:#64748b}.scan-detail strong{display:block;margin-top:4px;font-size:13px;word-break:break-word}.amount-field{font-size:26px;font-weight:900}.qr-warning{font-size:12px;padding:11px 13px;border-radius:12px;background:#fff7ed;color:#9a3412;margin-top:12px}.upi-actions{display:flex;gap:8px;flex-wrap:wrap}.upi-actions .btn{flex:1;min-width:180px}@media(max-width:850px){.payment-layout{grid-template-columns:1fr}.qr-card{position:static}.method-grid{grid-template-columns:1fr}.scan-details{grid-template-columns:1fr}}

#upiQrCard,
#upiPanel {
    display: none !important;
}

</style>
</head>
<body>
<header class="topbar"><a class="brand" href="home.php"><i class="fa fa-car"></i>Smart<b>Park</b></a><nav class="navlinks"><a href="home.php">Dashboard</a><a href="customer_profile.php">My Profile</a><a href="javascript:history.back()">Back</a><a class="btn btn-light" href="logout.php">Logout</a></nav></header>
<main class="dashboard"><div class="container narrow">

<div class="card" style="margin-bottom:20px">

    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px">

        <h1 style="margin:0">Secure Payment</h1>

        <?php if (
            strtolower($booking['status'] ?? '') === 'requested' &&
            strtolower($booking['payment_status'] ?? 'unpaid') === 'unpaid'
        ): ?>
            <button
                type="button"
                onclick="toggleReservationEditor()"
                style="border:0;background:none;color:#111827;font:inherit;font-weight:600;text-decoration:underline;cursor:pointer;padding:8px"
            >
                Edit
            </button>
        <?php endif; ?>

    </div>

    <p class="muted">
        Reservation #<?=e($booking['id'])?> ·
        <?=e($booking['parking_name'] ?? 'Parking')?>
    </p>

    <div style="font-size:30px;font-weight:900">
        ₹<?=number_format($amount,2)?>
    </div>

    <p class="muted">
        <?=e($booking['hours'])?> hour(s) ·
        <?=e($booking['city'] ?? $booking['location'] ?? '')?>
    </p>

</div>



<?php if (
    strtolower($booking['status'] ?? '') === 'requested' &&
    strtolower($booking['payment_status'] ?? 'unpaid') === 'unpaid'
): ?>

<div class="card" id="durationEditor" style="margin-bottom:20px;display:none">

    <h3>Edit Reservation</h3>

    <p class="muted">
        Change your parking location or duration before completing payment.
    </p>

    <div style="margin-bottom:16px">

        <label for="newParking">
            <strong>Parking location</strong>
        </label>

        <select
            id="newParking"
            class="form-control"
            style="width:100%;margin-top:7px"
        >

            <?php foreach ($parkingOptions as $option): ?>

                <option
                    value="<?= (int)$option['id'] ?>"
                    data-price="<?=e($option['price'])?>"
                    <?=((int)$option['id'] === $currentParkingId) ? 'selected' : ''?>
                >
                    <?=e($option['name'])?> -
                    <?=e($option['city'] ?? $option['location'])?>
                    (₹<?=number_format((float)$option['price'],2)?>/hour,
                    <?= (int)$option['remaining_slots'] ?> spaces available)
                </option>

            <?php endforeach; ?>

        </select>

    </div>

    <div style="margin-bottom:16px">

        <label for="newDuration">
            <strong>Parking duration (hours)</strong>
        </label>

        <input
            type="number"
            id="newDuration"
            class="form-control"
            min="1"
            max="24"
            step="1"
            value="<?= (int)$booking['hours'] ?>"
        >

    </div>

    <p class="muted">
        New estimated total:
        <strong id="newDurationTotal">
            ₹<?=number_format($amount,2)?>
        </strong>
    </p>

    <div style="display:flex;gap:10px;flex-wrap:wrap">

        <button
            type="button"
            class="btn btn-primary"
            id="updateDurationBtn"
            onclick="updateDuration()"
        >
            SAVE CHANGES
        </button>

        <button
            type="button"
            class="btn btn-light"
            onclick="toggleReservationEditor()"
        >
            CANCEL
        </button>

    </div>

    <div id="durationMessage" style="margin-top:12px"></div>

</div>

<?php endif; ?>


<div class="payment-layout">

<div class="card form-card">
<h2>Choose Payment Method</h2><div id="message"></div>
<?php if(($booking['payment_status']??'unpaid')==='paid'):?>
<div class="alert alert-success">This reservation is already paid.</div><div class="success-actions"><a class="btn btn-primary" href="receipt.php?id=<?=e($id)?>"><i class="fa fa-file-text-o"></i> View Payment Receipt</a><a class="btn btn-light" href="home.php">Dashboard</a></div>
<?php else:?>
<div class="method-grid">
<div class="method-option"><input type="radio" id="methodUpi" name="payment_method" value="UPI" checked><label for="methodUpi"><div class="method-icon"><i class="fa fa-qrcode"></i></div><div class="method-title">UPI / QR</div><div class="method-desc">Pay using UPI apps through Razorpay Checkout</div></label></div>
<div class="method-option"><input type="radio" id="methodOnline" name="payment_method" value="Online"><label for="methodOnline"><div class="method-icon"><i class="fa fa-credit-card"></i></div><div class="method-title">Card / Net Banking</div><div class="method-desc">Pay using cards, net banking or wallets</div></label></div>
<div class="method-option"><input type="radio" id="methodCash" name="payment_method" value="Cash"><label for="methodCash"><div class="method-icon"><i class="fa fa-money"></i></div><div class="method-title">Cash</div><div class="method-desc">Pay at parking location</div></label></div>
</div>

<div id="onlinePanel" class="payment-panel hidden">
<h3><i class="fa fa-lock"></i> Pay securely</h3><p>Your exact reservation amount is sent securely to Razorpay. Choose UPI, card, net banking or an available wallet. SmartPark verifies your payment on the server before marking your reservation as paid.</p>
<button id="payBtn" type="button" class="btn btn-primary" style="width:100%" onclick="startPayment()"><span class="pay-label"><i class="fa fa-credit-card"></i> PAY ₹<?=number_format($amount,2)?> NOW</span><span class="spinner"><i class="fa fa-spinner fa-spin"></i> Creating secure payment...</span></button>
</div>

<div id="cashPanel" class="payment-panel hidden" style="background:#fff8e8"><h3><i class="fa fa-money"></i> Cash on Payment</h3><p>Your reservation stays active with payment status <strong>Pending</strong> until the attendant collects the cash.</p><button type="button" class="btn btn-primary" style="width:100%" onclick="cashPayment()">CONFIRM CASH PAYMENT</button></div>
<?php if (strtolower($booking['status'] ?? '') === 'requested'): ?>

<div style="margin-top:18px;text-align:center;">
    <form method="POST"
          action="customer_cancel.php"
          onsubmit="return confirm('Are you sure you want to cancel this reservation?');">

        <input type="hidden" name="id" value="<?=e($id)?>">

        <button type="submit"
                class="btn btn-danger"
                style="width:100%;padding:14px;">
            <i class="fa fa-times-circle"></i>
            CANCEL RESERVATION
        </button>

    </form>
</div>

<?php endif; ?>~
<?php endif;?>
</div></div></div></main>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
const requestId=<?=json_encode($id)?>;

const upi=document.getElementById('methodUpi'), online=document.getElementById('methodOnline'), cash=document.getElementById('methodCash');
const onlinePanel = document.getElementById('onlinePanel');
const cashPanel = document.getElementById('cashPanel');
function updateUI() {
    const selected = document.querySelector(
        'input[name="payment_method"]:checked'
    )?.value;

    const useRazorpay =
        selected === 'UPI' || selected === 'Online';

    onlinePanel?.classList.toggle('hidden', !useRazorpay);
    cashPanel?.classList.toggle('hidden', selected !== 'Cash');
}

[upi, online, cash].forEach(element => {
    element?.addEventListener('change', updateUI);
});

updateUI();


// Reservation editing: location and duration

const durationInput = document.getElementById('newDuration');
const parkingInput = document.getElementById('newParking');
const durationTotal = document.getElementById('newDurationTotal');

function toggleReservationEditor() {
    const editor = document.getElementById('durationEditor');

    if (!editor) return;

    editor.style.display =
        editor.style.display === 'none' ? 'block' : 'none';

    editor.scrollIntoView({
        behavior: 'smooth',
        block: 'center'
    });
}

function getSelectedHourlyPrice() {
    const selectedOption = parkingInput?.selectedOptions[0];

    return Number(selectedOption?.dataset.price || 0);
}

function updateEstimatedTotal() {
    if (!durationInput || !parkingInput || !durationTotal) return;

    const hours = Number(durationInput.value);
    const price = getSelectedHourlyPrice();

    if (
        !Number.isInteger(hours) ||
        hours < 1 ||
        hours > 24 ||
        price < 0
    ) {
        durationTotal.textContent = 'Invalid selection';
        return;
    }

    durationTotal.textContent =
        '₹' + (hours * price).toFixed(2);
}

durationInput?.addEventListener('input', updateEstimatedTotal);
parkingInput?.addEventListener('change', updateEstimatedTotal);

async function updateDuration() {

    const button = document.getElementById('updateDurationBtn');
    const message = document.getElementById('durationMessage');

    const hours = Number(durationInput?.value);
    const parkingId = Number(parkingInput?.value);

    if (!Number.isInteger(hours) || hours < 1 || hours > 24) {
        message.textContent = 'Please enter between 1 and 24 hours.';
        return;
    }

    if (!Number.isInteger(parkingId) || parkingId < 1) {
        message.textContent = 'Please select a valid parking location.';
        return;
    }

    if (!confirm('Save the new parking location and duration?')) {
        return;
    }

    button.disabled = true;
    button.textContent = 'SAVING...';
    message.textContent = '';

    try {

        const fd = new FormData();

        fd.append('request_id', requestId);
        fd.append('parking_id', parkingId);
        fd.append('hours', hours);

        const response = await fetch(
            '../backend/api/update_reservation_duration.php',
            {
                method: 'POST',
                body: fd
            }
        );

        const data = await response.json();

        if (!data.ok) {
            throw new Error(data.message || 'Could not update reservation.');
        }

        message.textContent =
            'Reservation updated successfully. New total: ₹' + data.cost;

        // Reload the same reservation with the updated location and price.
        window.location.href = 'payment.php?id=' + requestId;

    } catch (error) {

        message.textContent = error.message;

        button.disabled = false;
        button.textContent = 'SAVE CHANGES';
    }
}

updateEstimatedTotal();


async function startPayment(){
 const btn = document.getElementById('payBtn');

 if (btn?.disabled) return;

 if (btn) {
     btn.disabled = true;
     btn.classList.add('processing');
 }
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
  },modal: {
    ondismiss: function () {
        if (btn) {
            btn.classList.remove('processing');
            btn.disabled = false;
        }
    }
},method:{upi:true,card:true,netbanking:true,wallet:true}};
  new Razorpay(options).open();
 } catch (e) {
    document.getElementById('message').innerHTML =
        '<div class="alert alert-error">' + escapeHtml(e.message) + '</div>';

    if (btn) {
        btn.classList.remove('processing');
        btn.disabled = false;
    }
}
}
async function cashPayment(){try{const fd=new FormData();fd.append('request_id',requestId);const r=await fetch('../backend/api/mark_cash_payment.php',{method:'POST',body:fd});const d=await r.json();if(d.ok)location.reload();else document.getElementById('message').innerHTML='<div class="alert alert-error">'+escapeHtml(d.message)+'</div>';}catch(e){document.getElementById('message').innerHTML='<div class="alert alert-error">Could not update cash status.</div>';}}
function escapeHtml(s){return String(s??'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]))}
</script>

</body></html>
