<?php
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Playground Booking</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    /* minimal styling - replace with Bootstrap/Tailwind later */
    body{font-family:Arial,sans-serif;margin:0;padding:0;background:#f7f7f8}
    header{background:#2b6cb0;color:#fff;padding:12px 20px}
    nav a{color:#fff;margin-right:12px;text-decoration:none}
    .container{max-width:960px;margin:20px auto;padding:16px;background:#fff;border-radius:6px;box-shadow:0 2px 6px rgba(0,0,0,.06)}
    .card{border:1px solid #eee;padding:12px;border-radius:6px;margin-bottom:12px}
    .btn{display:inline-block;padding:8px 12px;border-radius:6px;background:#2b6cb0;color:#fff;text-decoration:none}
    .muted{color:#666;font-size:0.9rem}
    table{width:100%;border-collapse:collapse}
    td,th{padding:8px;border:1px solid #eee}
  </style>
</head>
<body>
<header>
  <nav class="container">
    <a href="/public/index.php" style="font-weight:bold">Playground Booking</a>
    <?php if (!empty($_SESSION['user'])): ?>
      <span class="muted"> | Hello, <?=htmlspecialchars($_SESSION['user']['name'])?></span>
      <a href="/public/my_bookings.php" style="margin-left:12px">My Bookings</a>
      <a href="/public/logout.php" style="margin-left:12px">Logout</a>
    <?php else: ?>
      <a href="/public/login.php" style="float:right">Login</a>
      <a href="/public/register.php" style="float:right;margin-right:8px">Register</a>
    <?php endif; ?>
  </nav>
</header>

<main class="container" role="main">
