<?php
// ============================================================
// reports_pdf.php
// Print-friendly version of reports.php for PDF export.
// Opens in a new tab; the browser's "Print > Save as PDF"
// produces a clean PDF without sidebar/topbar.
//
// To trigger the print dialog automatically, this page calls
// window.print() on load via JavaScript.
// ============================================================
require_once 'includes/db.php';

$type = $_GET['type'] ?? 'country';
if (!in_array($type, ['country', 'major', 'i20'])) $type = 'country';

date_default_timezone_set('America/Chicago');

function v($val) {
    return htmlspecialchars($val ?? '', ENT_QUOTES);
}

// ------------------------------------------------------------
// SAME REPORT QUERIES AS reports.php
// ------------------------------------------------------------
if ($type === 'country') {
    $rows = $pdo->query("
        SELECT country,
               COUNT(*) AS total,
               SUM(level='GR') AS gr_count,
               SUM(level='UG') AS ug_count
        FROM students
        WHERE country IS NOT NULL AND country <> ''
        GROUP BY country
        ORDER BY total DESC, country ASC
    ")->fetchAll();
    $reportTitle = "Students by Country";
}

if ($type === 'major') {
    $rows = $pdo->query("
        SELECT major,
               COUNT(*) AS total,
               SUM(level='GR') AS gr_count,
               SUM(level='UG') AS ug_count
        FROM students
        WHERE major IS NOT NULL AND major <> ''
        GROUP BY major
        ORDER BY total DESC, major ASC
    ")->fetchAll();
    $reportTitle = "Students by Major";
}

if ($type === 'i20') {
    $rows = $pdo->query("
        SELECT
            s.student_id, s.full_name, s.level, et.term_code,
            i20.i20_number, i20.i20_document_received,
            i20.export_controls_requested, i20.export_controls_cleared,
            i20.i20_issued, vi.sevis_fee_paid, vi.visa_expiration_date
        FROM students s
        LEFT JOIN enrollment_terms et
            ON et.term_id = (SELECT MAX(term_id) FROM enrollment_terms WHERE student_id = s.student_id)
        LEFT JOIN i20_documents i20 ON i20.term_id = et.term_id
        LEFT JOIN visa_information vi ON vi.student_id = s.student_id
        ORDER BY
            (i20.i20_issued IS NULL) DESC,
            s.full_name ASC
    ")->fetchAll();
    $reportTitle = "I-20 Status Report";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= v($reportTitle) ?> — International Student Services</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, "Segoe UI", Arial, sans-serif; }
  body { color: #1E1E1E; font-size: 12px; padding: 30px; }

  .print-header {
    display: flex; justify-content: space-between; align-items: flex-start;
    border-bottom: 2px solid #0C447C; padding-bottom: 12px; margin-bottom: 18px;
  }
  .print-header h1 { font-size: 18px; color: #0C447C; }
  .print-header .org { font-size: 11px; color: #666; margin-top: 2px; }
  .print-header .meta { text-align: right; font-size: 11px; color: #666; }

  table { width: 100%; border-collapse: collapse; font-size: 11px; }
  th {
    text-align: left; padding: 8px 10px; font-size: 10px; font-weight: 700;
    color: #fff; background: #0C447C; text-transform: uppercase; letter-spacing: .03em;
  }
  td { padding: 7px 10px; border-bottom: 1px solid #eee; }
  tr:nth-child(even) td { background: #F8FBFE; }

  .badge { padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: 600; display: inline-block; }
  .badge-gr { background: #E1F5EE; color: #0F6E56; }
  .badge-ug { background: #EEEDFE; color: #534AB7; }
  .badge-yes { background: #EAF3DE; color: #3B6D11; }
  .badge-no { background: #F2F2F0; color: #888; }

  .summary-bar {
    display: flex; gap: 24px; margin-bottom: 16px; padding: 10px 14px;
    background: #F0F6FD; border-radius: 6px; font-size: 11px;
  }
  .summary-bar strong { color: #0C447C; font-size: 14px; }

  .footer-note { margin-top: 24px; font-size: 9px; color: #999; text-align: center; }

  /* Print-specific */
  @media print {
    body { padding: 0; }
    .no-print { display: none !important; }
    @page { margin: 1.5cm; size: landscape; }
  }

  .print-bar {
    margin-bottom: 16px; display: flex; gap: 8px;
  }
  .print-btn {
    padding: 8px 18px; border-radius: 6px; font-size: 12px; font-weight: 600;
    border: none; cursor: pointer; background: #0C447C; color: #fff;
  }
  .close-btn {
    padding: 8px 18px; border-radius: 6px; font-size: 12px; font-weight: 600;
    border: 1px solid #ccc; cursor: pointer; background: #fff; color: #555;
  }
</style>
</head>
<body>

  <!-- On-screen only: print / close buttons -->
  <div class="print-bar no-print">
    <button class="print-btn" onclick="window.print()">&#128424; Print / Save as PDF</button>
    <button class="close-btn" onclick="window.close()">Close</button>
  </div>

  <!-- ============================================================
       REPORT HEADER
  ============================================================ -->
  <div class="print-header">
    <div>
      <h1><?= v($reportTitle) ?></h1>
      <div class="org">International Student Services</div>
    </div>
    <div class="meta">
      Generated: <?= date("F j, Y  g:i A") ?><br>
      Total records: <?= count($rows) ?>
    </div>
  </div>

  <!-- ============================================================
       BY COUNTRY
  ============================================================ -->
  <?php if ($type === 'country'): ?>
    <?php
      $sumTotal = array_sum(array_column($rows, 'total'));
      $sumGR = array_sum(array_column($rows, 'gr_count'));
      $sumUG = array_sum(array_column($rows, 'ug_count'));
    ?>
    <div class="summary-bar">
      <div><strong><?= count($rows) ?></strong> countries</div>
      <div><strong><?= $sumTotal ?></strong> total students</div>
      <div><strong><?= $sumGR ?></strong> graduate</div>
      <div><strong><?= $sumUG ?></strong> undergraduate</div>
    </div>
    <table>
      <thead>
        <tr>
          <th>Country</th>
          <th>Total Students</th>
          <th>Graduate (GR)</th>
          <th>Undergraduate (UG)</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><strong><?= v($r['country']) ?></strong></td>
            <td><?= (int)$r['total'] ?></td>
            <td><span class="badge badge-gr"><?= (int)$r['gr_count'] ?></span></td>
            <td><span class="badge badge-ug"><?= (int)$r['ug_count'] ?></span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

  <!-- ============================================================
       BY MAJOR
  ============================================================ -->
  <?php if ($type === 'major'): ?>
    <?php
      $sumTotal = array_sum(array_column($rows, 'total'));
      $sumGR = array_sum(array_column($rows, 'gr_count'));
      $sumUG = array_sum(array_column($rows, 'ug_count'));
    ?>
    <div class="summary-bar">
      <div><strong><?= count($rows) ?></strong> programs</div>
      <div><strong><?= $sumTotal ?></strong> total students</div>
      <div><strong><?= $sumGR ?></strong> graduate</div>
      <div><strong><?= $sumUG ?></strong> undergraduate</div>
    </div>
    <table>
      <thead>
        <tr>
          <th>Major / Program</th>
          <th>Total Students</th>
          <th>Graduate (GR)</th>
          <th>Undergraduate (UG)</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><strong><?= v($r['major']) ?></strong></td>
            <td><?= (int)$r['total'] ?></td>
            <td><span class="badge badge-gr"><?= (int)$r['gr_count'] ?></span></td>
            <td><span class="badge badge-ug"><?= (int)$r['ug_count'] ?></span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

  <!-- ============================================================
       I-20 STATUS
  ============================================================ -->
  <?php if ($type === 'i20'): ?>
    <?php
      $pendingCount = count(array_filter($rows, fn($r) => $r['i20_issued'] === null));
      $issuedCount = count($rows) - $pendingCount;
    ?>
    <div class="summary-bar">
      <div><strong><?= count($rows) ?></strong> total students</div>
      <div><strong><?= $issuedCount ?></strong> I-20 issued</div>
      <div><strong><?= $pendingCount ?></strong> I-20 pending</div>
    </div>
    <table>
      <thead>
        <tr>
          <th>Student</th>
          <th>Level</th>
          <th>Term</th>
          <th>I-20 Number</th>
          <th>Docs Received</th>
          <th>Export Ctrl Req.</th>
          <th>Export Ctrl Cleared</th>
          <th>I-20 Issued</th>
          <th>SEVIS Fee</th>
          <th>Visa Expiry</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td>
              <strong><?= v($r['full_name']) ?></strong><br>
              <span style="font-size:9px; color:#999;"><?= v($r['student_id']) ?></span>
            </td>
            <td>
              <?php if ($r['level'] === 'GR'): ?>
                <span class="badge badge-gr">GR</span>
              <?php elseif ($r['level'] === 'UG'): ?>
                <span class="badge badge-ug">UG</span>
              <?php else: ?>&mdash;<?php endif; ?>
            </td>
            <td><?= v($r['term_code'] ?: '—') ?></td>
            <td><?= v($r['i20_number'] ?: '—') ?></td>
            <td><?= $r['i20_document_received'] ? date("M j, Y", strtotime($r['i20_document_received'])) : '—' ?></td>
            <td><?= $r['export_controls_requested'] ? date("M j, Y", strtotime($r['export_controls_requested'])) : '—' ?></td>
            <td><?= $r['export_controls_cleared'] ? date("M j, Y", strtotime($r['export_controls_cleared'])) : '—' ?></td>
            <td>
              <?php if ($r['i20_issued']): ?>
                <span class="badge badge-yes"><?= date("M j, Y", strtotime($r['i20_issued'])) ?></span>
              <?php else: ?>
                <span class="badge badge-no">Pending</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($r['sevis_fee_paid'] === null): ?>&mdash;
              <?php elseif ($r['sevis_fee_paid']): ?>
                <span class="badge badge-yes">Paid</span>
              <?php else: ?>
                <span class="badge badge-no">Unpaid</span>
              <?php endif; ?>
            </td>
            <td><?= $r['visa_expiration_date'] ? date("M j, Y", strtotime($r['visa_expiration_date'])) : '—' ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

  <div class="footer-note">
    International Student Services &mdash; Confidential &mdash; Generated <?= date("F j, Y g:i A") ?>
  </div>

  <script>
    // Auto-open the print dialog so the user can save as PDF immediately
    window.onload = function() {
      window.print();
    };
  </script>

</body>
</html>
