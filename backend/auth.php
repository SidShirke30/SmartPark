<?php if (session_status() !== PHP_SESSION_ACTIVE) session_start(); function require_customer(){
    if (empty($_SESSION['driver_id']) && empty($_SESSION['driver_email'])){
        header('Location: ../frontend/index.php'); exit; }
}
function require_admin(){
    if (empty($_SESSION['admin_id'])){
        header('Location: ../frontend/admin_login.php'); exit; }
}
function e($v){
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
