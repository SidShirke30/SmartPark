<?php
require_once '../backend/db_connect.php';
require_once '../backend/auth.php';
$message='';
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['login'])){
  $email=strtolower(trim($_POST['email']??'')); $password=$_POST['password']??'';
  if($email!=='' && $password!==''){
    $s=mysqli_prepare($con,'SELECT id,name,email,password FROM users WHERE LOWER(email)=? LIMIT 1');
    mysqli_stmt_bind_param($s,'s',$email); mysqli_stmt_execute($s);
    $u=mysqli_fetch_assoc(mysqli_stmt_get_result($s)); $valid=false;
    if($u){
      $valid=password_verify($password,$u['password']);
      if(!$valid && hash_equals((string)$u['password'],(string)$password)){
        $newHash=password_hash($password,PASSWORD_DEFAULT);
        $up=mysqli_prepare($con,'UPDATE users SET password=?,password_confirm=? WHERE id=?');
        mysqli_stmt_bind_param($up,'ssi',$newHash,$newHash,$u['id']); mysqli_stmt_execute($up); $valid=true;
      }
    }
    if($valid){session_regenerate_id(true);$_SESSION['driver_id']=$u['id'];$_SESSION['driver_email']=$u['email'];$_SESSION['driver_name']=$u['name'];header('Location: home.php');exit;}
  }
  $message='Invalid email or password.';
}
$parkingCount=mysqli_fetch_row(mysqli_query($con,"SELECT COUNT(*) FROM parkings"))[0]??0;
$available=mysqli_fetch_row(mysqli_query($con,"SELECT COALESCE(SUM(remaining_slots),0) FROM parkings"))[0]??0;
?>
<!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>SmartPark — Intelligent Parking</title>
<link rel="stylesheet" href="assets/css/smartpark-motion.css">
<link rel="stylesheet" href="assets/css/smartpark-theme.css"><link rel="stylesheet" href="assets/css/smartpark-refresh.css">
<link rel="stylesheet" href="assets/font-awesome/css/font-awesome.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<link rel="stylesheet" href="assets/css/smartpark-ai.css"></head><body>
<header class="topbar">
<a class="brand" href="index.php"><i class="fa fa-car"></i><span>Smart<b>Park</b></span></a>
<nav class="navlinks">
<a href="#features">FEATURES</a><a href="#map">MAP</a><a href="#login">LOGIN</a>
<a class="btn btn-primary" href="register.php">GET STARTED</a>
<a class="btn btn-light" href="admin_login.php">ADMIN</a>
</nav></header>

<main class="sp-landing">
<section class="sp-hero container">
  <div class="sp-particles" id="spParticles" aria-hidden="true"></div>
  <div class="sp-hero-nav">
    <div class="sp-logo-mark">SP<span>•</span></div>
    <div class="mini-links"><a href="#features">FEATURES</a><a href="#map">MAP</a><a href="#login">ACCOUNT</a></div>
    <a class="btn btn-primary" href="register.php">GET STARTED</a>
  </div>

  <div class="sp-hero-main">
    <div class="sp-copy">
      <div class="sp-eyebrow"><span class="sp-live-dot"></span>SMART PARKING / LIVE NETWORK</div>
      <h1>SMART<br><span>PARKING</span></h1>
      <p>Find an available space, reserve it before you arrive, and move through the city without wasting time searching for parking.</p>
      <div class="sp-actions">
        <a class="btn btn-primary" href="register.php">FIND A SPACE <i class="fa fa-arrow-right"></i></a>
        <a class="btn btn-light" href="#map">VIEW MAP</a>
      </div>
      <div class="sp-stats-row">
        <div class="sp-stat"><strong><?=e($available)?></strong><span>SPACES AVAILABLE</span></div>
        <div class="sp-stat"><strong><?=e($parkingCount)?></strong><span>LOCATIONS</span></div>
        <div class="sp-stat"><strong>24/7</strong><span>BOOKING</span></div>
      </div>
    </div>

    <div class="sp-video-wrap">
      <video autoplay muted loop playsinline preload="metadata" poster="assets/cars/smartpark-f1-reference.jpg">
        <source src="assets/video/parking-automation-showcase.mp4" type="video/mp4">
      </video>
      <div class="sp-video-label"><span class="sp-live-dot"></span> PARKING AUTOMATION / LIVE</div>
    </div>
  </div>

  <div class="sp-scroll-cue">SCROLL TO EXPLORE</div>
</section>

<section class="section" id="features"><div class="container">
<div class="section-title reveal"><div class="sp-eyebrow">DESIGNED FOR MODERN DRIVERS</div><h2>Everything you need to park smarter.</h2><p>Clean controls, live availability and a faster reservation experience.</p></div>
<div class="sp-feature-grid">
<div class="sp-feature reveal"><div class="icon"><i class="fa fa-map-marker"></i></div><h3>Live availability</h3><p>See parking locations and available spaces on an interactive map before you start your journey.</p></div>
<div class="sp-feature reveal"><div class="icon"><i class="fa fa-calendar-check-o"></i></div><h3>Instant reservation</h3><p>Choose a location, select your hours and reserve a space from one simple flow.</p></div>
<div class="sp-feature reveal"><div class="icon"><i class="fa fa-shield"></i></div><h3>Secure control</h3><p>Manage your profile, bookings and payments through a protected customer account.</p></div>
</div>
</div></section>

<section class="section section-dark sp-map-section" id="map"><div class="container">
<div class="section-title reveal"><div class="sp-eyebrow">SMARTPARK NETWORK</div><h2>Find your parking space.</h2><p>Explore live parking locations, check availability and then create your account to reserve.</p></div>
<div class="sp-map-card reveal" data-tilt><div class="sp-map-head"><strong><i class="fa fa-map-marker" style="color:var(--sp-blue)"></i> LIVE PARKING MAP</strong><span>MAHARASHTRA / REAL-TIME</span></div><div id="network-map" class="smartpark-map sp-main-map"></div><div class="sp-map-footer"><span><i class="fa fa-circle"></i> Available</span><span><i class="fa fa-circle"></i> Limited</span><span><i class="fa fa-circle"></i> Full</span><a class="btn btn-primary btn-sm" href="register.php">REGISTER TO RESERVE</a></div></div>
</div></section>

<section class="section" id="login"><div class="container sp-login-section">
<div class="sp-login-copy reveal"><div class="sp-eyebrow">SECURE CUSTOMER PORTAL</div><h2>YOUR PARKING.<br><span>YOUR CONTROL.</span></h2><p>Sign in to discover nearby locations, reserve spaces, track reservations and complete payments from one clean dashboard.</p><div class="sp-actions"><a class="btn btn-light" href="register.php">CREATE ACCOUNT</a></div></div>
<div class="auth-card reveal"><h2 style="margin-top:0;letter-spacing:-.04em">Customer login</h2>
<?php if($message):?><div class="alert alert-error"><?=e($message)?></div><?php endif;?>
<form method="post">

    <div class="form-group">
        <label>Email</label>
        <input
            class="form-control"
            type="email"
            name="email"
            required
        >
    </div>

    <div class="form-group">
        <label>Password</label>
        <input
            class="form-control"
            type="password"
            name="password"
            required
        >
    </div>

    <button
        class="btn btn-primary"
        style="width:100%"
        name="login"
    >
        SIGN IN
    </button>

</form>

<p style="text-align:right; margin-top:12px;">
    <a href="forgot_password.php"
       style="color:var(--sp-blue); text-decoration:none;">
        Forgot Password?
    </a>
</p>

<p class="muted" style="text-align:center">
    New customer?
    <a href="register.php" style="color:var(--sp-blue)">
        Create account
    </a>
</p>
</div></div></section>
</main>
<footer class="footer">© <?=date('Y')?> SmartPark — Intelligent parking for modern cities.</footer>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="assets/js/smartpark-motion.js"></script>
<script>
function setupSmartMap(id){
 const el=document.getElementById(id); if(!el)return null;
 const map=L.map(id,{zoomControl:false}).setView([19.7515,75.7139],7);
 L.control.zoom({position:'bottomright'}).addTo(map);
 L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,attribution:'© OpenStreetMap'}).addTo(map);
 return map;
}
const networkMap=setupSmartMap('network-map');
fetch('../backend/api/parkings.php').then(r=>r.json()).then(d=>{
 (d.parkings||[]).forEach(p=>{
   const color=Number(p.remaining_slots)>30?'#3b82f6':Number(p.remaining_slots)>10?'#2563eb':'#64748b';
   const extra=(Number(p.ev_charging)===1?'<br><i class="fa fa-bolt"></i> EV charging':'')+(p.contact_phone?'<br><i class="fa fa-phone"></i> '+escapeHtml(p.contact_phone):'');
   const popup='<b>'+escapeHtml(p.name)+'</b><br>'+escapeHtml(p.city||p.location)+'<br>'+escapeHtml(p.parking_type||'Open Air')+' · '+escapeHtml(p.remaining_slots)+' spaces available<br><b>₹'+escapeHtml(p.price)+'/hour</b>'+extra+'<br><a href="register.php" style="color:#2563eb">Sign in to reserve</a>';
   if(networkMap){
     L.circleMarker([Number(p.latitude),Number(p.longitude)],{radius:9,color:'#fff',weight:2,fillColor:color,fillOpacity:.95}).addTo(networkMap).bindPopup(popup);
   }
 });
 setTimeout(()=>{if(networkMap)networkMap.invalidateSize()},250);
}).catch(()=>{});
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script>
(function(){
  // Decorative particle network in the hero background.
  // Skipped entirely for reduced-motion users or if THREE failed to load.
  const mount=document.getElementById('spParticles');
  if(!mount || typeof THREE==='undefined') return;
  if(window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

  const isSmall=window.matchMedia('(max-width:700px)').matches;
  const COUNT=isSmall?900:2200;

  const scene=new THREE.Scene();
  const camera=new THREE.PerspectiveCamera(55,1,0.1,100);
  camera.position.set(0,3.4,9.5);
  camera.lookAt(0,0,0);

  const renderer=new THREE.WebGLRenderer({antialias:true,alpha:true});
  renderer.setPixelRatio(Math.min(window.devicePixelRatio||1,2));
  mount.appendChild(renderer.domElement);

  // Precompute a flat grid of "parking network" nodes: base x/z positions are
  // fixed, only y (height) is animated each frame, so nothing is reallocated
  // in the render loop.
  const cols=Math.ceil(Math.sqrt(COUNT));
  const spacing=0.34;
  const positions=new Float32Array(COUNT*3);
  const phase=new Float32Array(COUNT);
  const colors=new Float32Array(COUNT*3);

  const white=new THREE.Color(0xffffff);
  const blue=new THREE.Color(0x2563eb);
  const mixed=new THREE.Color();

  for(let i=0;i<COUNT;i++){
    const cx=i%cols, cz=Math.floor(i/cols);
    const x=(cx-cols/2)*spacing;
    const z=(cz-cols/2)*spacing;
    phase[i]=Math.sqrt(x*x+z*z);
    positions[i*3]=x; positions[i*3+1]=0; positions[i*3+2]=z;
    const t=Math.min(1,phase[i]/6);
    mixed.copy(white).lerp(blue,t);
    colors[i*3]=mixed.r; colors[i*3+1]=mixed.g; colors[i*3+2]=mixed.b;
  }

  const geometry=new THREE.BufferGeometry();
  const posAttr=new THREE.BufferAttribute(positions,3);
  posAttr.setUsage(THREE.DynamicDrawUsage);
  geometry.setAttribute('position',posAttr);
  geometry.setAttribute('color',new THREE.BufferAttribute(colors,3));

  const material=new THREE.PointsMaterial({
    size:0.05,vertexColors:true,transparent:true,opacity:0.85,
    depthWrite:false,blending:THREE.AdditiveBlending
  });
  const points=new THREE.Points(geometry,material);
  points.rotation.x=-0.55;
  scene.add(points);

  function resize(){
    const w=mount.clientWidth||1, h=mount.clientHeight||1;
    camera.aspect=w/Math.max(h,1);
    camera.updateProjectionMatrix();
    renderer.setSize(w,h,false);
  }
  resize();
  window.addEventListener('resize',resize);

  let raf=null, startTime=null;
  function tick(now){
    if(startTime===null) startTime=now;
    const time=(now-startTime)/1000;
    const posArr=posAttr.array;
    for(let i=0;i<COUNT;i++){
      const wave=Math.sin(phase[i]*1.1-time*1.1)*0.32;
      posArr[i*3+1]=wave;
    }
    posAttr.needsUpdate=true;
    points.rotation.y=time*0.045;
    renderer.render(scene,camera);
    raf=requestAnimationFrame(tick);
  }
  raf=requestAnimationFrame(tick);

  document.addEventListener('visibilitychange',()=>{
    if(document.hidden && raf){cancelAnimationFrame(raf);raf=null;}
    else if(!document.hidden && !raf){raf=requestAnimationFrame(tick);}
  });
})();
</script>
<script src="assets/js/smartpark-ai.js"></script></body></html>
