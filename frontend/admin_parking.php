<?php require_once '../backend/db_connect.php'; require_once '../backend/auth.php'; require_admin(); $msg='';$error='';$action=$_GET['action']??'list';$id=(int)($_GET['id']??0); if ($_SERVER['REQUEST_METHOD']==='POST'){
    $mode=$_POST['mode']??''; if ($mode==='delete'){
        $id=(int)$_POST['id'];$s=mysqli_prepare($con,'SELECT remaining_slots,slot FROM parkings WHERE id=?');mysqli_stmt_bind_param($s,'i',$id);mysqli_stmt_execute($s);$p=mysqli_fetch_assoc(mysqli_stmt_get_result($s));$has=mysqli_query($con,"SELECT id FROM requests WHERE parking_id=".$id." LIMIT 1");if ($p&&(!$has||mysqli_num_rows($has)===0)){
            $s=mysqli_prepare($con,'DELETE FROM parkings WHERE id=?');mysqli_stmt_bind_param($s,'i',$id);mysqli_stmt_execute($s);$msg='Parking location deleted.';}
        else $error='This parking cannot be deleted because it has reservation history.';}
    if ($mode==='save'){
        $id=(int)$_POST['id'];$name=trim($_POST['name']);$location=trim($_POST['location']);$street=trim($_POST['street']);$slot=max(1,(int)$_POST['slot']);$remaining=max(0,min($slot,(int)$_POST['remaining_slots']));$price=max(0,(float)$_POST['price']);$latitude=trim($_POST['latitude']??'');$longitude=trim($_POST['longitude']??'');$attendant='';
        $parkingType=trim($_POST['parking_type']??'Open Air');if(!in_array($parkingType,['Open Air','Covered','Multi-Level'],true))$parkingType='Open Air';
        $evCharging=isset($_POST['ev_charging'])?1:0;$contactPhone=trim($_POST['contact_phone']??'');
        if ($latitude!=='' && (!is_numeric($latitude)||$latitude<-90||$latitude>90)) $error='Latitude must be between -90 and 90.';
        else if ($longitude!=='' && (!is_numeric($longitude)||$longitude<-180||$longitude>180)) $error='Longitude must be between -180 and 180.'; if ($name===''||$location===''||$street==='')$error='Please complete all required fields.';else if ($id){
            $s=mysqli_prepare($con,'UPDATE parkings SET name=?,location=?,street=?,slot=?,remaining_slots=?,price=?,attendant=?,latitude=?,longitude=?,parking_type=?,ev_charging=?,contact_phone=? WHERE id=?');$rs=(int)$remaining;$pr=(float)$price;$lat=($latitude==='')?null:(float)$latitude;$lng=($longitude==='')?null:(float)$longitude;mysqli_stmt_bind_param($s,'sssiidsddsisi',$name,$location,$street,$slot,$rs,$pr,$attendant,$lat,$lng,$parkingType,$evCharging,$contactPhone,$id);if (mysqli_stmt_execute($s))$msg='Parking location updated successfully.';else$error='Could not update parking.';}
        else{
            $rs=(int)$remaining;$pr=(float)$price;$lat=($latitude==='')?null:(float)$latitude;$lng=($longitude==='')?null:(float)$longitude;$s=mysqli_prepare($con,'INSERT INTO parkings(name,location,street,slot,remaining_slots,price,attendant,latitude,longitude,parking_type,ev_charging,contact_phone) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)');mysqli_stmt_bind_param($s,'sssiidsddsis',$name,$location,$street,$slot,$rs,$pr,$attendant,$lat,$lng,$parkingType,$evCharging,$contactPhone);if (mysqli_stmt_execute($s)){
                $msg='Parking location added successfully.';$action='list';}
            else$error='Could not add parking.';}
    }
}
$editing=null;if ($action==='edit'&&$id){
    $s=mysqli_prepare($con,'SELECT * FROM parkings WHERE id=?');mysqli_stmt_bind_param($s,'i',$id);mysqli_stmt_execute($s);$editing=mysqli_fetch_assoc(mysqli_stmt_get_result($s));if (!$editing){
        $action='list';$error='Parking not found.';}
}
$list=mysqli_query($con,'SELECT * FROM parkings ORDER BY id DESC'); ?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Manage Parking | ParkSmart</title>
<link rel="stylesheet" href="assets/css/smartpark-motion.css">
<link rel="stylesheet" href="assets/css/smartpark-theme.css"><link rel="stylesheet" href="assets/css/smartpark-refresh.css">
<link rel="stylesheet" href="assets/font-awesome/css/font-awesome.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
</head>
<body>
<header class="topbar">
<a class="brand" href="admin.php">
<i class="fa fa-car">
</i>ParkSmart Admin</a>
<nav class="navlinks">
<a href="admin.php">Dashboard</a>
<a href="admin_customers.php">Customers</a>
<a href="admin_requests.php">Requests</a>
<a href="admin_admins.php">Admins</a>
<a href="admin_password.php">Password</a>
<a class="btn btn-light" href="admin.php">Home</a><a class="btn btn-light" href="javascript:history.back()">Back</a><a class="btn btn-light" href="javascript:history.forward()">Next</a><a class="btn btn-light" href="logout.php">Logout</a>
</nav>
</header>
<main class="dashboard">
<div class="container">
<div class="dashboard-head">
<div>
<h1>Parking management</h1>
<p class="muted">Add, edit and maintain parking locations.</p>
</div>
<a class="btn btn-primary" href="admin_parking.php?action=add">
<i class="fa fa-plus">
</i> Add parking</a>
</div>
<?php if ($msg):?>
<div class="alert alert-success">
<?=e($msg)?>
</div>
<?php endif;?>
<?php if ($error):?>
<div class="alert alert-error">
<?=e($error)?>
</div>
<?php endif;?>
<?php if ($action==='add'||$action==='edit'): $p=$editing?:['id'=>0,'name'=>'','location'=>'','street'=>'','slot'=>1,'remaining_slots'=>1,'price'=>0,'attendant'=>'','latitude'=>'','longitude'=>'','parking_type'=>'Open Air','ev_charging'=>0,'contact_phone'=>'']; ?>
<div class="card form-card">
<h2>
<?=$p['id']?'Update':'Add'?>
parking location</h2>
<form method="post">
<input type="hidden" name="mode" value="save">
<input type="hidden" name="id" value="
<?=e($p['id'])?>
">
<div class="form-grid">
<div class="form-group">
<label>Parking name</label>
<input class="form-control" name="name" value="
<?=e($p['name'])?>
" required>
</div>
<div class="form-group">
<label>Location / city</label>
<input class="form-control" name="location" value="
<?=e($p['location'])?>
" required>
</div>
<div class="form-group">
<label>Street</label>
<input class="form-control" name="street" value="
<?=e($p['street'])?>
" required>
</div>
<div class="form-group">
<label>Total slots</label>
<input class="form-control" type="number" min="1" name="slot" value="
<?=e($p['slot'])?>
" required>
</div>
<div class="form-group">
<label>Available slots</label>
<input class="form-control" type="number" min="0" name="remaining_slots" value="
<?=e($p['remaining_slots'])?>
" required>
</div>
<div class="form-group">
<label>Price / hour</label>
<input class="form-control" type="number" min="0" step="0.01" name="price" value="
<?=e($p['price'])?>
" required>
</div>
<div class="form-group">
<label>Parking type</label>
<select class="form-control" name="parking_type">
<?php foreach(['Open Air','Covered','Multi-Level'] as $type):?>
<option value="<?=e($type)?>"<?=$p['parking_type']===$type?' selected':''?>><?=e($type)?></option>
<?php endforeach;?>
</select>
</div>
<div class="form-group">
<label>Contact phone</label>
<input class="form-control" name="contact_phone" value="
<?=e($p['contact_phone'])?>
" placeholder="e.g. +91 98220 11001">
</div>
<div class="form-group" style="align-self:end">
<label style="display:flex;align-items:center;gap:8px;cursor:pointer">
<input type="checkbox" name="ev_charging" value="1"<?=((int)$p['ev_charging']===1)?' checked':''?>>
EV charging available
</label>
</div>
<div class="form-group full-width location-picker-wrap">
<label>Map location <span class="muted">(click the map to place the parking marker)</span></label>
<div class="map-picker-toolbar">
<button type="button" class="btn btn-light btn-sm" id="useLocationBtn"><i class="fa fa-crosshairs"></i> Use my location</button>
<span id="mapPickerStatus" class="muted">Select a point on the map.</span>
</div>
<div id="adminLocationMap" class="admin-location-map"></div>
<div class="coordinate-grid">
<div class="form-group"><label>Latitude</label><input class="form-control" id="latitude" name="latitude" type="number" step="0.0000001" min="-90" max="90" value="<?=e($p['latitude']??'')?>" placeholder="e.g. 19.8762000"></div>
<div class="form-group"><label>Longitude</label><input class="form-control" id="longitude" name="longitude" type="number" step="0.0000001" min="-180" max="180" value="<?=e($p['longitude']??'')?>" placeholder="e.g. 75.3433000"></div>
</div>
</div>
</div>
<button class="btn btn-primary"><i class="fa fa-save"></i> Save location</button>
<a class="btn btn-light" href="admin_parking.php">Cancel</a>
</form>
</div>
<?php endif;?>
<div class="card table-wrap">
<table class="table">
<tr>
<th>Name</th>
<th>Location</th>
<th>Slots</th>
<th>Available</th>
<th>Price</th>
<th>Type</th>
<th>EV</th>
<th>Map</th>
<th>Actions</th>
</tr>
<?php while ($p=mysqli_fetch_assoc($list)):?>
<tr>
<td>
<?=e($p['name'])?>
</td>
<td>
<?=e($p['location'])?>
,
<?=e($p['street'])?>
</td>
<td>
<?=e($p['slot'])?>
</td>
<td>
<?=e($p['remaining_slots'])?>
</td>
<td>₹
<?=e($p['price'])?>
</td>
<td>
<?=e($p['parking_type']??'Open Air')?>
</td>
<td><?=((int)($p['ev_charging']??0)===1)?'<span class="status-pill status-success"><i class="fa fa-bolt"></i> Yes</span>':'<span class="status-pill">No</span>'?></td>
<td><?=($p['latitude']!==null && $p['longitude']!==null)?'<span class="status-pill status-success"><i class="fa fa-map-marker"></i> Pinned</span>':'<span class="status-pill">Not set</span>'?></td>
<td class="actions">
<a class="btn btn-light btn-sm" href="admin_parking.php?action=edit&id=
<?=$p['id']?>
">Edit</a>
<form method="post" onsubmit="return confirm('Delete this parking location?')">
<input type="hidden" name="mode" value="delete">
<input type="hidden" name="id" value="
<?=$p['id']?>
">
<button class="btn btn-danger btn-sm">Delete</button>
</form>
</td>
</tr>
<?php endwhile;?>
</table>
</div>
</div>
</main>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function(){
 const mapEl=document.getElementById('adminLocationMap'); if(!mapEl) return;
 const latInput=document.getElementById('latitude'), lngInput=document.getElementById('longitude'), status=document.getElementById('mapPickerStatus');
 const defaultLat=Number(latInput.value)||20.5937, defaultLng=Number(lngInput.value)||78.9629, zoom=(latInput.value&&lngInput.value)?15:5;
 const map=L.map(mapEl,{scrollWheelZoom:true}).setView([defaultLat,defaultLng],zoom);
 L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,attribution:'&copy; OpenStreetMap contributors'}).addTo(map);
 let marker=null;
 function setPoint(lat,lng,pan){ lat=Number(lat).toFixed(7); lng=Number(lng).toFixed(7); latInput.value=lat; lngInput.value=lng; if(marker) marker.setLatLng([lat,lng]); else marker=L.marker([lat,lng],{draggable:true}).addTo(map); marker.on('dragend',function(){const p=marker.getLatLng();setPoint(p.lat,p.lng,false)}); if(pan) map.setView([lat,lng],15); status.textContent='Pinned: '+lat+', '+lng; }
 if(latInput.value&&lngInput.value) setPoint(latInput.value,lngInput.value,false);
 map.on('click',e=>setPoint(e.latlng.lat,e.latlng.lng,true));
 [latInput,lngInput].forEach(el=>el.addEventListener('change',()=>{const a=Number(latInput.value),b=Number(lngInput.value);if(Number.isFinite(a)&&Number.isFinite(b))setPoint(a,b,true)}));
 document.getElementById('useLocationBtn')?.addEventListener('click',()=>{ if(!navigator.geolocation){status.textContent='Geolocation is not supported by this browser.';return;} status.textContent='Detecting your location…'; navigator.geolocation.getCurrentPosition(pos=>setPoint(pos.coords.latitude,pos.coords.longitude,true),()=>status.textContent='Could not access your location. Click the map instead.',{enableHighAccuracy:true,timeout:10000}); });
 setTimeout(()=>map.invalidateSize(),300);
})();
</script>
<script src="assets/js/smartpark-motion.js"></script></body>
</html>
