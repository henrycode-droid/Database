<?php
// ============================================================
// includes/header.php
// Shared top bar + sidebar navigation for every page.
//
// USAGE: at the top of each page, set $pageTitle and $activeNav,
// then: require_once 'includes/header.php';
//
//   $pageTitle = "Dashboard";
//   $activeNav = "dashboard";   // matches data-nav values below
// ============================================================
date_default_timezone_set('America/Chicago');
if (!isset($pageTitle)) $pageTitle = "International Student Services";
if (!isset($activeNav)) $activeNav = "";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($pageTitle) ?> — International Student Services</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="topbar">
  <div class="brand">
    <span>&#127979;</span>
    International Student Services
  </div>
  <div class="clock"><?= date("l, F j, Y  |  g:i A") ?></div>
</div>

<div class="layout">

  <div class="sidebar">
    <div class="nav-heading">Forms</div>
    <a class="nav-item <?= $activeNav === 'dashboard' ? 'active' : '' ?>" href="index.php">
      <span class="icon">&#9783;</span><span class="label">Dashboard</span>
    </a>
    <a class="nav-item <?= $activeNav === 'add_student' ? 'active' : '' ?>" href="add_student.php">
      <span class="icon">&#43;</span><span class="label">New student</span>
    </a>
    <a class="nav-item <?= $activeNav === 'students' ? 'active' : '' ?>" href="students.php">
      <span class="icon">&#128101;</span><span class="label">All students</span>
    </a>
    <a class="nav-item <?= $activeNav === 'search' ? 'active' : '' ?>" href="students.php#search">
      <span class="icon">&#128269;</span><span class="label">Search</span>
    </a>

    <div class="nav-heading">Reports</div>
    <a class="nav-item <?= $activeNav === 'rpt_country' ? 'active' : '' ?>" href="reports.php?type=country">
      <span class="icon">&#127758;</span><span class="label">By country</span>
    </a>
    <a class="nav-item <?= $activeNav === 'rpt_major' ? 'active' : '' ?>" href="reports.php?type=major">
      <span class="icon">&#128218;</span><span class="label">By major</span>
    </a>
    <a class="nav-item <?= $activeNav === 'rpt_i20' ? 'active' : '' ?>" href="reports.php?type=i20">
      <span class="icon">&#128196;</span><span class="label">I-20 status</span>
    </a>
    <a class="nav-item <?= $activeNav === 'alerts' ? 'active' : '' ?>" href="alerts.php">
      <span class="icon">&#9888;</span><span class="label">Alerts</span>
    </a>
  </div>

  <div class="main">
    <div class="container">
<!-- Page content begins here -->
