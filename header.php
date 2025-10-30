<?php
// header.php

require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AuctionIndo - Platform Lelang Terpercaya</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">
                <h1><a href="index.php">AuctionIndo</a></h1>
            </div>
            <nav>
                <ul>
                    <li><a href="index.php">Beranda</a></li>
                    <li><a href="search.php">Cari Barang</a></li>
                    <?php if (isLoggedIn()): ?>
                        <li><a href="new_listing.php">Buat Lelang</a></li>
                        <li><a href="dashboard.php">Dashboard</a></li>
                        <?php if (isAdmin()): ?>
                            <li><a href="admin_panel.php">Admin Panel</a></li>
                        <?php endif; ?>
                        <li><a href="logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Login</a></li>
                        <li><a href="register.php">Daftar</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <main>
        <div class="container">