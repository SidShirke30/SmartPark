<?php require_once '../backend/db_connect.php';$tables=['admin','parkings','requests','users','site_settings'];?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>ParkSmart Setup Check</title>
<link rel="stylesheet" href="assets/css/smartpark-motion.css">
<link rel="stylesheet" href="assets/css/smartpark-theme.css"><link rel="stylesheet" href="assets/css/smartpark-refresh.css">
</head>
<body>
<div class="container section">
<div class="card">
<h1>ParkSmart setup check</h1>
<p class="muted">Database: smartpark_db</p>
<?php foreach ($tables as $t){
    $ok=mysqli_query($con,"SHOW TABLES LIKE '".mysqli_real_escape_string($con,$t)."'");echo '<p>'.($ok&&mysqli_num_rows($ok)?'✅':'❌').' '.htmlspecialchars($t).'</p>';}
?>
<p>
<a class="btn btn-primary" href="index.php">Home</a>
<a class="btn btn-light" href="javascript:history.back()">Back</a>
</p>
</div>
</div>
<script src="assets/js/smartpark-motion.js"></script></body>
</html>
