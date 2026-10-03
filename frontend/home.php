<?php
require_once '../backend/db_connect.php'; require_once '../backend/auth.php'; require_customer();
$email=$_SESSION['driver_email']; $name=$_SESSION['driver_name']??$email; $q=trim($_GET['q']??'');
if($q!==''){
  $s=mysqli_prepare($con,"SELECT * FROM parkings WHERE remaining_slots>0 AND (name LIKE ? OR location LIKE ? OR street LIKE ? OR city LIKE ?) ORDER BY name");
  $like="%$q%"; mysqli_stmt_bind_param($s,'ssss',$like,$like,$like,$like); mysqli_stmt_execute($s); $res=mysqli_stmt_get_result($s);
}else $res=mysqli_query($con,'SELECT * FROM parkings WHERE remaining_slots>0 ORDER BY name');
$myStmt=mysqli_prepare($con,"SELECT r.*,p.name parking_name,p.location FROM requests r LEFT JOIN parkings p ON p.id=r.parking_id WHERE r.customer=? ORDER BY r.id DESC LIMIT 10");
mysqli_stmt_bind_param($myStmt,'s',$email); mysqli_stmt_execute($myStmt); $my=mysqli_stmt_get_result($myStmt);
$available=mysqli_fetch_row(mysqli_query($con,'SELECT COUNT(*) FROM parkings WHERE remaining_slots>0'))[0]??0;
$spaces=mysqli_fetch_row(mysqli_query($con,'SELECT COALESCE(SUM(remaining_slots),0) FROM parkings'))[0]??0;
?>
<!doctype html><html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>SmartPark — <?=e($name)?></title>
<link rel="stylesheet" href="assets/css/smartpark-motion.css"><link rel="stylesheet" href="assets/css/smartpark-theme.css"><link rel="stylesheet" href="assets/css/smartpark-refresh.css"><link rel="stylesheet" href="assets/font-awesome/css/font-awesome.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<link rel="stylesheet" href="assets/css/smartpark-ai.css">
<link rel="stylesheet" href="assets/css/smartpark-map.css">
<style>
.logout-modal {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 99999;
    background: rgba(10, 15, 25, 0.55);
    backdrop-filter: blur(8px);
    justify-content: center;
    align-items: center;
    padding: 20px;
}

.logout-modal.show {
    display: flex;
    animation: logoutFade 0.25s ease;
}

.logout-box {
    width: 100%;
    max-width: 420px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 24px;
    padding: 35px 28px;
    text-align: center;
    box-shadow: 0 25px 80px rgba(0,0,0,0.2);
    animation: logoutPop 0.3s ease;
}

.logout-icon {
    width: 65px;
    height: 65px;
    border-radius: 50%;
    background: #fff1f1;
    color: #dc2626;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 27px;
    margin: 0 auto 20px;
}

.logout-box h2 {
    color: #111418;
    font-size: 25px;
    margin-bottom: 12px;
}

.logout-box p {
    color: #64748b;
    line-height: 1.7;
    margin-bottom: 28px;
}

.logout-actions {
    display: flex;
    gap: 12px;
}

.logout-cancel,
.logout-confirm {
    flex: 1;
    padding: 14px 12px;
    border-radius: 30px;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    text-align: center;
    text-decoration: none;
    border: none;
    transition: 0.25s ease;
}

.logout-cancel {
    background: #f1f5f9;
    color: #111827;
}

.logout-cancel:hover {
    background: #e2e8f0;
}

.logout-confirm {
    background: #111418;
    color: #ffffff;
}

.logout-confirm:hover {
    background: #dc2626;
    color: #ffffff;
}

@keyframes logoutFade {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes logoutPop {
    from {
        opacity: 0;
        transform: translateY(15px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.city-cluster{border:1px solid #e2e8f0;border-radius:20px;background:#fff;margin-top:14px;overflow:hidden;box-shadow:0 8px 28px rgba(15,23,42,.05)}
.city-cluster-head{width:100%;display:flex;align-items:center;justify-content:space-between;gap:16px;border:0;background:#f8fafc;padding:16px 18px;cursor:pointer;text-align:left}
.city-cluster-title{display:flex;align-items:center;gap:12px}.city-cluster-title strong{display:block;font-size:18px;color:#111827}.city-cluster-title small{display:block;margin-top:3px;color:#64748b;font-size:12px}.city-cluster-icon{width:38px;height:38px;border-radius:12px;background:#e0f2fe;color:#0284c7;display:grid;place-items:center}.city-cluster-meta{display:flex;align-items:center;gap:10px}.city-space-badge{background:#ecfdf5;color:#047857;border:1px solid #a7f3d0;padding:6px 10px;border-radius:999px;font-size:11px;font-weight:800}.city-chevron{color:#64748b;transition:transform .25s}.city-cluster-body{display:none;padding:16px}.city-cluster.is-open .city-cluster-body{display:block}.city-cluster.is-open .city-chevron{transform:rotate(180deg)}.reservation-card{height:100%}.empty-state{text-align:center;padding:48px 20px;color:#64748b}.empty-state i{font-size:40px;color:#0ea5e9}.empty-state h3{color:#111827;margin:10px 0 6px}.booking-location-pill{display:flex;align-items:center;gap:9px;padding:10px 12px;border-radius:12px;background:#f0f9ff;color:#0369a1;border:1px solid #bae6fd;font-size:13px;font-weight:700;margin:10px 0 14px}.form-control{box-sizing:border-box}@media(max-width:700px){.city-cluster-head{align-items:flex-start}.city-space-badge{display:none}.city-cluster-title strong{font-size:16px}}
</style>
</head><body>
<header class="topbar"><a class="brand" href="home.php"><i class="fa fa-car"></i><span>Smart<b>Park</b></span></a>
<nav class="navlinks"><a href="home.php">HOME</a><a href="#map">LIVE MAP</a><a href="#locations">FIND SPACE</a><a href="#cars">EXPERIENCE</a><a href="#bookings">RESERVATIONS</a><span class="sp-admin-chip">Hi, <?=e($name)?></span><a class="btn btn-light" href="customer_profile.php">PROFILE</a></nav></header>
<button type="button" class="btn btn-light" id="logoutBtn">
    LOGOUT
</button>
<main class="sp-landing">
<section class="sp-dashboard-hero container">
<div class="sp-hero-nav"><div class="sp-logo-mark">SP<span>•</span></div><div class="mini-links"><a href="#map">LIVE MAP</a><a href="#bookings">RESERVATIONS</a><a href="#cars">EXPERIENCE</a></div><div style="display:flex;gap:8px;flex-wrap:wrap"><a class="btn btn-light btn-sm" href="home.php"><i class="fa fa-home"></i> HOME</a><a class="btn btn-light btn-sm" href="javascript:history.back()"><i class="fa fa-arrow-left"></i> BACK</a><a class="btn btn-light btn-sm" href="javascript:history.forward()"><i class="fa fa-arrow-right"></i> NEXT</a></div></div>
<div class="sp-hero-main">
<div class="sp-copy"><div class="sp-eyebrow"><span class="sp-live-dot"></span>CUSTOMER CONTROL / LIVE SESSION</div>
<h1>YOUR<br><span>PARKING</span></h1>
<p>Welcome back, <?=e($name)?>. Find a space, reserve it in seconds and keep your entire parking journey in one place.</p>
<div class="sp-actions"><a class="btn btn-primary" href="#locations">FIND PARKING <i class="fa fa-arrow-right"></i></a><a class="btn btn-light" href="#bookings">MY RESERVATIONS</a></div>
<div class="sp-stats-row"><div class="sp-stat"><strong><?=e($spaces)?></strong><span>SPACES AVAILABLE</span></div><div class="sp-stat"><strong><?=e($available)?></strong><span>OPEN LOCATIONS</span></div><div class="sp-stat"><strong>LIVE</strong><span>NETWORK</span></div></div>
</div>
<div class="sp-video-wrap"><video autoplay muted loop playsinline preload="metadata" poster="assets/cars/smartpark-f1-reference.jpg"><source src="assets/video/parking-automation-showcase.mp4" type="video/mp4"></video><div class="sp-video-label"><span class="sp-live-dot"></span> SMARTPARK / LIVE</div></div>
</div>
</section>


<section class="section section-dark sp-map-section" id="map"><div class="container">
<div class="section-title reveal"><div class="sp-eyebrow">SMARTPARK NETWORK</div><h2>Your live parking map.</h2><p>See your live GPS position, choose any parking destination, calculate a road route, view distance and ETA, and open turn-by-turn navigation.</p></div>
<div class="sp-map-card reveal" data-tilt>
<div class="sp-map-head"><strong><i class="fa fa-map-marker" style="color:var(--sp-blue)"></i> LIVE PARKING MAP</strong><span>LIVE GPS + ROAD ROUTING</span></div>
<div class="sp-map-tools">
  <div>
    <label class="muted" style="display:block;font-size:11px;text-transform:uppercase;letter-spacing:.08em;margin-bottom:6px">Parking destination</label>
    <select id="parkingDestination" class="form-control"><option value="">Loading available parking…</option></select>
  </div>
  <div class="sp-route-points">
    <div class="sp-route-point"><strong>From</strong><span id="routeFromValue">Not selected</span></div>
    <div class="sp-route-point"><strong>To</strong><span id="routeToValue">Not selected</span></div>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:end">
    <button id="useMyLocationBtn" class="btn btn-primary btn-sm" type="button"><i class="fa fa-location-arrow"></i> MY LOCATION</button>
    <button id="setFromBtn" class="btn btn-light btn-sm" type="button">SET FROM</button>
    <button id="setToBtn" class="btn btn-light btn-sm" type="button">SET TO</button>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap">
    <button id="calculateRouteBtn" class="btn btn-primary btn-sm" type="button">GET ROAD ROUTE</button>
    <button id="clearRouteBtn" class="btn btn-light btn-sm" type="button">CLEAR</button>
    <button id="navigateBtn" class="btn btn-light btn-sm" type="button">OPEN NAVIGATION</button>
  </div>
  <div id="mapStatus" class="map-status info">Starting live map…</div>
  <div class="sp-route-point"><strong>Your live GPS</strong><span id="locationCoords">Waiting…</span> · <span id="locationAccuracy">—</span></div>
</div>
<div id="smartpark-map" class="smartpark-map sp-main-map"></div>
<div id="routeSummary" class="route-summary hidden">
  <div class="route-metric"><span>Distance</span><strong id="routeDistance">—</strong><small>road distance</small></div>
  <div class="route-metric"><span>ETA</span><strong id="routeTime">—</strong><small>estimated drive time</small></div>
  <div class="route-metric"><span>Provider</span><strong id="routeProvider" style="font-size:14px">—</strong><small>routing source</small></div>
</div>
<div class="sp-map-footer"><span><i class="fa fa-circle"></i> Available</span><span><i class="fa fa-circle"></i> Limited</span><span><i class="fa fa-circle"></i> Full</span><a class="btn btn-primary btn-sm" href="#locations">JUMP TO RESERVE LIST</a></div></div>
</div></section>

<section class="section" id="locations"><div class="container">
<div class="section-title reveal"><div class="sp-eyebrow">PARKING NETWORK</div><h2>Choose where you want to park.</h2><p>Search by city or parking name, then open a location to reserve your space.</p></div>
<div class="card reveal" style="padding:22px">
<form method="get" style="display:flex;gap:10px;max-width:760px;margin-bottom:18px;flex-wrap:wrap"><input class="form-control" style="flex:1;min-width:240px" name="q" value="<?=e($q)?>" placeholder="Search city, location or parking name"><button class="btn btn-primary" type="submit">SEARCH</button><?php if($q!==''):?><a class="btn btn-light" href="home.php#locations">CLEAR</a><?php endif;?></form>
<?php
$cityGroups=[];
while($p=mysqli_fetch_assoc($res)){
  $city=trim((string)($p['city']??''));
  if($city==='') $city=trim((string)($p['location']??'Other'));
  if($city==='') $city='Other';
  $cityGroups[$city][]=$p;
}
uksort($cityGroups,function($a,$b){return strcasecmp($a,$b);});
?>
<div class="reservation-network">
<?php if(!$cityGroups):?>
  <div class="empty-state"><i class="fa fa-map-marker"></i><h3>No parking spaces found</h3><p>Try another city or parking name.</p></div>
<?php else:?>
<?php $cityIndex=0; foreach($cityGroups as $city=>$parks): $cityIndex++; ?>
<section class="city-cluster <?= $cityIndex===1?'is-open':'' ?>" data-city="<?=e(strtolower($city))?>">
  <button type="button" class="city-cluster-head" onclick="toggleCityCluster(this)" aria-expanded="<?= $cityIndex===1?'true':'false' ?>">
    <span class="city-cluster-title"><span class="city-cluster-icon"><i class="fa fa-map-marker"></i></span><span><strong><?=e($city)?></strong><small><?=count($parks)?> parking <?=count($parks)===1?'location':'locations'?> available</small></span></span>
    <span class="city-cluster-meta"><span class="city-space-badge"><?=array_sum(array_map(fn($x)=>(int)$x['remaining_slots'],$parks))?> spaces</span><i class="fa fa-chevron-down city-chevron"></i></span>
  </button>
  <div class="city-cluster-body">
    <div class="sp-feature-grid">
    <?php foreach($parks as $p):?>
      <div class="sp-feature reservation-card" data-parking-city="<?=e(strtolower($city))?>">
        <div class="icon"><i class="fa fa-car"></i></div>
        <h3><?=e($p['name'])?></h3>
        <p><?=e($p['street']??$p['location']??'') ?> · <?=e($p['remaining_slots'])?> spaces · ₹<?=e($p['price'])?>/hour</p>
        <p class="muted" style="margin:-6px 0 12px;font-size:12px"><i class="fa fa-building-o"></i> <?=e($p['parking_type']??'Open Air')?><?php if((int)($p['ev_charging']??0)===1):?> · <i class="fa fa-bolt"></i> EV charging<?php endif;?><?php if(!empty($p['contact_phone'])):?> · <i class="fa fa-phone"></i> <?=e($p['contact_phone'])?><?php endif;?></p>
        <div style="display:flex;gap:8px;flex-wrap:wrap"><button class="btn btn-primary btn-sm" onclick="openBooking(<?= (int)$p['id'] ?>,<?= (float)$p['price'] ?>,<?= htmlspecialchars(json_encode($p['name'], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>,<?= htmlspecialchars(json_encode($city, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>)">RESERVE</button><button type="button" class="btn btn-light btn-sm" onclick="focusParkingOnMap(<?= (int)$p['id'] ?>)"><i class="fa fa-map-marker"></i> MAP</button></div>
      </div>
    <?php endforeach;?>
    </div>
  </div>
</section>
<?php endforeach;?>
<?php endif;?>
</div></div></div></section>

<section class="section section-dark" id="cars"><div class="container">
<div class="section-title reveal"><div class="sp-eyebrow">DRIVING EXPERIENCE</div><h2>A cleaner way to move.</h2><p>SmartPark combines live parking data with a modern automotive experience.</p></div>
<div class="sp-feature-grid">
<div class="sp-feature reveal"><div class="icon"><i class="fa fa-bolt"></i></div><h3>Fast arrival</h3><p>Know where you're going before you enter the parking area.</p></div>
<div class="sp-feature reveal"><div class="icon"><i class="fa fa-map"></i></div><h3>Live navigation</h3><p>Use the interactive map to locate available spaces across the network.</p></div>
<div class="sp-feature reveal"><div class="icon"><i class="fa fa-credit-card"></i></div><h3>Simple payment</h3><p>Complete reservation payment and track your status from the dashboard.</p></div>
</div></div></section>

<section class="section" id="bookings"><div class="container">
<div class="section-title"><div class="sp-eyebrow">YOUR ACCOUNT</div><h2>Reservation control.</h2><p>Recent bookings, payment status and cancellation controls.</p></div>
<div class="card table-wrap"><table class="table"><tr><th>Parking</th><th>Hours</th><th>Cost</th><th>Status</th><th>Payment</th><th>Action</th></tr>
<?php while($r=mysqli_fetch_assoc($my)):?>
<tr><td><?=e($r['parking_name']??'Parking')?><br><small class="muted"><?=e($r['location']??'')?></small></td><td><?=e($r['hours'])?></td><td>₹<?=e($r['cost'])?></td><td><span class="badge <?=strtolower($r['status'])==='requested'?'':'red'?>"><?=e($r['status'])?></span></td>
<td><?php if(($r['payment_status']??'unpaid')!=='paid' && strtolower($r['status'])==='requested'):?><a class="btn btn-primary btn-sm" href="payment.php?id=<?=e($r['id'])?>">CHOOSE PAYMENT</a><?php elseif(($r['payment_status']??'unpaid')==='pending'):?><span class="badge">CASH — PENDING</span><?php else:?><a class="btn btn-primary btn-sm" href="receipt.php?id=<?=e($r['id'])?>">RECEIPT</a><?php endif;?></td>
<td><?php if(strtolower($r['status'])==='requested'):?><form method="post" action="customer_cancel.php" onsubmit="return confirm('Cancel this reservation?')"><input type="hidden" name="id" value="<?=e($r['id'])?>"><button class="btn btn-danger btn-sm">CANCEL</button></form><?php else:?>—<?php endif;?></td></tr>
<?php endwhile;?></table></div></div></section>
</main>

<div class="modal-back" id="modal"><div class="modal"><div style="display:flex;justify-content:space-between"><h2 id="modalTitle">Reserve</h2><button class="btn btn-light" onclick="closeBooking()">×</button></div><div class="booking-location-pill"><i class="fa fa-map-marker"></i><span id="bookingLocation">Select a parking location</span></div><p class="muted">Price: <strong id="price"></strong> / hour</p><input id="parkingId" type="hidden"><div class="form-group"><label>Number of hours</label><input class="form-control" id="hours" type="number" min="1" max="24" value="1" oninput="calc()"></div><div class="card"><strong>Total: ₹<span id="total">0</span></strong></div><div id="bookMsg"></div><button class="btn btn-primary" style="width:100%;margin-top:15px" onclick="book()">CONFIRM RESERVATION</button></div></div>
<!-- LOGOUT CONFIRMATION MODAL -->

<div id="logoutModal" class="logout-modal">

    <div class="logout-box">

        <div class="logout-icon">
            <i class="fa fa-sign-out"></i>
        </div>

        <h2>Confirm Logout?</h2>

        <p>
            Are you sure you want to logout from your ParkSmart account?
        </p>

        <div class="logout-actions">

            <button type="button" id="cancelLogout" class="logout-cancel">
                Cancel
            </button>

            <a href="logout.php" class="logout-confirm">
                Confirm Logout
            </a>

        </div>

    </div>

</div>
<footer class="footer">SmartPark customer control · <?=date('Y')?></footer>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script><script src="assets/js/smartpark-motion.js"></script>
<script>
let price=0;
function openBooking(id,p,n,city){price=Number(p);document.getElementById('parkingId').value=id;document.getElementById('modalTitle').textContent='RESERVE '+n;document.getElementById('bookingLocation').textContent=(city?city+' · ':'')+n;document.getElementById('price').textContent='₹'+price;document.getElementById('modal').classList.add('show');calc()}
function toggleCityCluster(button){const cluster=button.closest('.city-cluster');if(!cluster)return;const open=cluster.classList.toggle('is-open');button.setAttribute('aria-expanded',open?'true':'false')}
function focusParkingOnMap(id){window.location.hash='map';if(window.smartParkFocusParkingById){window.smartParkFocusParkingById(id)}else{setTimeout(()=>window.smartParkFocusParkingById&&window.smartParkFocusParkingById(id),250)}}
function closeBooking(){document.getElementById('modal').classList.remove('show')}
function calc(){let h=Math.max(1,Number(document.getElementById('hours').value)||1);document.getElementById('total').textContent=price*h}
function book(){let fd=new FormData();fd.append('parking_id',document.getElementById('parkingId').value);fd.append('hours',document.getElementById('hours').value);fetch('../backend/parking/book_slot.php',{method:'POST',body:fd}).then(r=>r.json()).then(x=>{document.getElementById('bookMsg').innerHTML='<div class="alert '+(x.ok?'alert-success':'alert-error')+'">'+escapeHtml(x.message)+'</div>';if(x.ok)setTimeout(()=>location.href=x.payment_url||'home.php',700)}).catch(()=>document.getElementById('bookMsg').innerHTML='<div class="alert alert-error">Server error.</div>')}
// Live map/routing is provided by assets/js/smartpark-map.js.
function escapeHtml(s){return String(s??'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));}
window.SMARTPARK_MAP_CONFIG={centerLat:19.7515,centerLng:75.7139};
</script><script src="assets/js/smartpark-map.js"></script><script src="assets/js/smartpark-ai.js"></script>
<script>
const logoutBtn = document.getElementById('logoutBtn');
const logoutModal = document.getElementById('logoutModal');
const cancelLogout = document.getElementById('cancelLogout');

logoutBtn.addEventListener('click', function () {
    logoutModal.classList.add('show');
});

cancelLogout.addEventListener('click', function () {
    logoutModal.classList.remove('show');
});

logoutModal.addEventListener('click', function (event) {
    if (event.target === logoutModal) {
        logoutModal.classList.remove('show');
    }
});

document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        logoutModal.classList.remove('show');
    }
});
</script>
</body></html>
