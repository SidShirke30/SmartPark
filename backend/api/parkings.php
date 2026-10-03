<?php
require_once __DIR__ . '/../db_connect.php';
header('Content-Type: application/json; charset=utf-8');
$q=trim($_GET['q']??'');
$lat=isset($_GET['lat'])?(float)$_GET['lat']:null;
$lng=isset($_GET['lng'])?(float)$_GET['lng']:null;
$radius=isset($_GET['radius'])?min(100, max(1,(float)$_GET['radius'])):50;
$where="latitude IS NOT NULL AND longitude IS NOT NULL AND CAST(remaining_slots AS UNSIGNED)>0";
$params=[]; $types='';
if($q!==''){ $where.=" AND (name LIKE ? OR location LIKE ? OR city LIKE ? OR street LIKE ?)"; $like="%$q%"; $params=[$like,$like,$like,$like]; $types='ssss'; }
$sql="SELECT id,name,location,street,city,price,slot,CAST(remaining_slots AS UNSIGNED) remaining_slots,latitude,longitude,parking_type,ev_charging,contact_phone FROM parkings WHERE $where";
if($lat!==null && $lng!==null){
  $sql .= " AND (6371*ACOS(LEAST(1,COS(RADIANS(?))*COS(RADIANS(latitude))*COS(RADIANS(longitude)-RADIANS(?))+SIN(RADIANS(?))*SIN(RADIANS(latitude))))) <= ?";
  $params[]=$lat; $params[]=$lng; $params[]=$lat; $params[]=$radius; $types.='dddi';
}
$sql .= " ORDER BY name";
$stmt=mysqli_prepare($con,$sql);
if($types) mysqli_stmt_bind_param($stmt,$types,...$params);
mysqli_stmt_execute($stmt); $r=mysqli_stmt_get_result($stmt);
$out=[]; while($p=mysqli_fetch_assoc($r)){ $out[]=$p; }
echo json_encode(['ok'=>true,'count'=>count($out),'parkings'=>$out]);
