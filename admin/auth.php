<?php session_start();
if(!isset($_SESSION['admin'])){header('Location: login.php');exit;}
function is_owner(){ return ($_SESSION['admin_role'] ?? 'staff') === 'owner'; }
function require_owner(){ if(!is_owner()){http_response_code(403); die('Owners only.');} }
