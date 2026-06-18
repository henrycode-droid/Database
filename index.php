<?php
// ============================================================
// index.php
// Main Dashboard — KPI cards, quick access, alerts, search
// ============================================================
require_once 'includes/db.php';

// ------------------------------------------------------------
// KPI QUERIES
// ------------------------------------------------------------
$totalStudents = (int) $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$totalGR       = (int) $pdo->query("SELECT COUNT(*) FROM students WHERE level = 'GR'")->fetchColumn();
$totalUG       = (int) $pdo->query("SELECT COUNT(*) FROM students WHERE level = 'UG'")->fetchColumn();
$totalCountries = (int) $pdo->query("SELECT COUNT(DISTINCT country) FROM students WHERE country IS NOT NULL AND country <> ''")->fetchColumn();

// ------------------------------------------------------------
// ALERTS
// ------------------------------------------------------------
// Visas expiring within 90 days
$visaExpiring = (int) $pdo->query("
    SELECT COUNT(*) FROM visa_information
    WHERE visa_expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)
")->fetchColumn();

// SEVIS fee not paid
$sevisUnpaid = (int) $pdo->query("
    SELECT COUNT(*) FROM visa_information WHERE sevis_fee_paid = 0
")->fetchColumn();

// I-20 pending (issued IS NULL)
$i20Pending = (int) $pdo->query("
    SELECT COUNT(*) FROM i20_documents WHERE i20_issued IS NULL
")->fetchColumn();

$pageTitle = "Dashboard";
$activeNav = "dashboard";
require_once 'includes/header.php';
?>

  <div class="hero">
    <div>
      <h2>International Student Services</h2>
      <p>Welcome back — here's what's happening across your student population.</p>
    </div>
    <div class="right">
      <div>Today: <?= date("F j, Y") ?></div>
    </div>
  </div>

  <!-- ============================================================
       KPI CARDS
  ============================================================ -->
  <div class="kpi-row">
    <div class="kpi">
      <div class="tile blue">&#128101;</div>
      <div class="num blue"><?= $totalStudents ?></div>
      <div class="label">Total Students</div>
    </div>
    <div class="kpi">
      <div class="tile teal">&#127979;</div>
      <div class="num teal"><?= $totalGR ?></div>
      <div class="label">Graduate (GR)</div>
    </div>
    <div class="kpi">
      <div class="tile purp">&#128218;</div>
      <div class="num purp"><?= $totalUG ?></div>
      <div class="label">Undergraduate (UG)</div>
    </div>
    <div class="kpi">
      <div class="tile amber">&#127758;</div>
      <div class="num amber"><?= $totalCountries ?></div>
      <div class="label">Countries Represented</div>
    </div>
  </div>

  <!-- ============================================================
       SEARCH BAR
  ============================================================ -->
 

  <!-- ============================================================
       QUICK ACCESS
  ============================================================ -->
  <div>
    <div class="section-title">Quick Access</div>
    <div class="qa-row">
      <a href="add_student.php" class="qa-card c-blue">
        <div class="tile blue">&#43;</div>
        <div class="title">Add New Student</div>
        <div class="sub">Register a new admission</div>
      </a>
      <a href="students.php" class="qa-card c-teal">
        <div class="tile teal">&#9783;</div>
        <div class="title">Student Profiles</div>
        <div class="sub">View and edit records</div>
      </a>
      <a href="students.php#search" class="qa-card c-purp">
        <div class="tile purp">&#128269;</div>
        <div class="title">Search Students</div>
        <div class="sub">Filter by any field</div>
      </a>
      <a href="reports.php?type=i20" class="qa-card c-amber">
        <div class="tile amber">&#128196;</div>
        <div class="title">I-20 Checklist</div>
        <div class="sub">Track I-20 documents</div>
      </a>
      <a href="reports.php?type=major" class="qa-card c-coral">
        <div class="tile" style="background:#FAECE7;color:#993C1D;">&#9776;</div>
        <div class="title">Programs</div>
        <div class="sub">Manage study programs</div>
      </a>
      <a href="reports.php" class="qa-card c-green">
        <div class="tile" style="background:#EAF3DE;color:#3B6D11;">&#128202;</div>
        <div class="title">Reports</div>
        <div class="sub">View all reports</div>
      </a>
    </div>
  </div>

  <!-- ============================================================
       ALERTS
  ============================================================ -->
  <div>
    <div class="section-title">Alerts</div>
    <div class="alert-box">
      <?php if ($visaExpiring > 0): ?>
        <div class="alert-row warn">
          &#9888; <strong><?= $visaExpiring ?></strong> student<?= $visaExpiring == 1 ? '' : 's' ?> have visas expiring within 90 days —
          <a href="reports.php?type=i20">view details</a>
        </div>
      <?php endif; ?>

      <?php if ($sevisUnpaid > 0): ?>
        <div class="alert-row danger">
          &#9888; <strong><?= $sevisUnpaid ?></strong> student<?= $sevisUnpaid == 1 ? '' : 's' ?> have not confirmed SEVIS fee payment —
          <a href="reports.php?type=i20">view details</a>
        </div>
      <?php endif; ?>

      <?php if ($i20Pending > 0): ?>
        <div class="alert-row warn">
          &#9888; <strong><?= $i20Pending ?></strong> student<?= $i20Pending == 1 ? '' : 's' ?> have a pending I-20 —
          <a href="reports.php?type=i20">view details</a>
        </div>
      <?php endif; ?>

      <?php if ($visaExpiring == 0 && $sevisUnpaid == 0 && $i20Pending == 0): ?>
        <div class="alert-row" style="background:#EAF3DE; border:1px solid #C5E0A5; color:#3B6D11;">
          &#10003; No outstanding alerts. Everything looks up to date.
        </div>
      <?php endif; ?>
    </div>
  </div>

<?php require_once 'includes/footer.php'; ?>
