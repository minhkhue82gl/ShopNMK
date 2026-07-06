<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}


if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    $_SESSION['error'] = "Hệ thống từ chối truy cập! Vui lòng đăng nhập tài khoản Quản trị viên.";
    
   
    if (strpos($_SERVER['REQUEST_URI'], '/modules/') !== false) {
        header('Location: ../../login.php');
    } else {
        header('Location: login.php');
    }
    exit;
}


$base_url = (strpos($_SERVER['REQUEST_URI'], '/modules/') !== false) ? '../../' : '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ thống Quản trị - Website Bán Giày NMK</title>
    
    <link rel="stylesheet" href="<?= $base_url ?>../assets/css/bootstrap.min.css">
    
    <link rel="stylesheet" href="<?= $base_url ?>../assets/css/style.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { 
            background-color: #f8f9fa; 
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        }
        .sidebar { 
            min-height: 100vh; 
            box-shadow: 2px 0 10px rgba(0,0,0,0.05); 
            z-index: 1000;
        }
        .nav-link { 
            color: #495057; 
            font-weight: 500; 
            padding: 12px 20px; 
            border-radius: 6px; 
            margin-bottom: 4px;
            transition: all 0.2s ease;
        }
        .nav-link:hover { 
            background-color: #f1f3f5; 
            color: #ff5722; 
        }
        .nav-link.active-module { 
            background-color: #ff5722; 
            color: #ffffff !important; 
        }
        .main-content {
            padding: 30px;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">