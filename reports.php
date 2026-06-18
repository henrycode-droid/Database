<?php
// ============================================================
// reports.php
// Reports: By Country, By Major, I-20 Status
// ============================================================
require_once 'includes/db.php';

$type = $_GET['type'] ?? 'country';
if (!in_array($type, ['country', 'major', 'i20'])) $type = 'country';

function v($val) {
    return htmlspecialchars($val ?? '', ENT_QUOTES);
}

// ------------------------------------------------------------
// REPORT QUERIES
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
}

$pageTitle = "Reports";
$activeNav = ($type === 'country') ? 'rpt_country' : (($type === 'major') ? 'rpt_major' : 'rpt_i20');
require_once 'includes/header.php';
?>

  <div class="hero">
    <div>
      <h2>Reports</h2>
      <p>View student data broken down by country, major, or I-20 status.</p>
    </div>
    <div class="right">
      <a href="reports_pdf.php?type=<?= v($type) ?>" target="_blank" class="btn btn-primary">&#11015; Download PDF</a>
    </div>
  </div>

  <!-- ============================================================
       TAB STRIP
  ============================================================ -->
  <div class="card" style="padding: 6px; display:flex; gap:6px;">
    <a href="reports.php?type=country" class="btn <?= $type === 'country' ? 'btn-primary' : 'btn-secondary' ?>" style="flex:1; text-align:center;">By Country</a>
    <a href="reports.php?type=major" class="btn <?= $type === 'major' ? 'btn-primary' : 'btn-secondary' ?>" style="flex:1; text-align:center;">By Major</a>
    <a href="reports.php?type=i20" class="btn <?= $type === 'i20' ? 'btn-primary' : 'btn-secondary' ?>" style="flex:1; text-align:center;">I-20 Status</a>
  </div>

  <!-- ============================================================
       BY COUNTRY
  ============================================================ -->
  <?php if ($type === 'country'): ?>
    <div class="card" style="padding:0; overflow-x:auto;">
      <table class="data-table">
        <thead>
          <tr>
            <th>Country</th>
            <th>Total Students</th>
            <th>Graduate (GR)</th>
            <th>Undergraduate (UG)</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr><td colspan="4" style="text-align:center; color:#999; padding:30px;">No data yet.</td></tr>
          <?php else: ?>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td><strong><?= v($r['country']) ?></strong></td>
                <td><?= (int)$r['total'] ?></td>
                <td><span class="badge badge-gr"><?= (int)$r['gr_count'] ?></span></td>
                <td><span class="badge badge-ug"><?= (int)$r['ug_count'] ?></span></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

  <!-- ============================================================
       BY MAJOR
  ============================================================ -->
  <?php if ($type === 'major'): ?>
    <div class="card" style="padding:0; overflow-x:auto;">
      <table class="data-table">
        <thead>
          <tr>
            <th>Major / Program</th>
            <th>Total Students</th>
            <th>Graduate (GR)</th>
            <th>Undergraduate (UG)</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr><td colspan="4" style="text-align:center; color:#999; padding:30px;">No data yet.</td></tr>
          <?php else: ?>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td><strong><?= v($r['major']) ?></strong></td>
                <td><?= (int)$r['total'] ?></td>
                <td><span class="badge badge-gr"><?= (int)$r['gr_count'] ?></span></td>
                <td><span class="badge badge-ug"><?= (int)$r['ug_count'] ?></span></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

  <!-- ============================================================
       I-20 STATUS
  ============================================================ -->
  <?php if ($type === 'i20'): ?>
    <div class="card" style="padding:0; overflow-x:auto;">
      <table class="data-table">
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
          <?php if (empty($rows)): ?>
            <tr><td colspan="10" style="text-align:center; color:#999; padding:30px;">No data yet.</td></tr>
          <?php else: ?>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td>
                  <a href="add_student.php?student_id=<?= urlencode($r['student_id']) ?>">
                    <strong><?= v($r['full_name']) ?></strong>
                  </a><br>
                  <span style="font-size:11px; color:#999;"><?= v($r['student_id']) ?></span>
                </td>
                <td>
                  <?php if ($r['level'] === 'GR'): ?>
                    <span class="badge badge-gr">GR</span>
                  <?php elseif ($r['level'] === 'UG'): ?>
                    <span class="badge badge-ug">UG</span>
                  <?php else: ?>—<?php endif; ?>
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
                  <?php if ($r['sevis_fee_paid'] === null): ?>—
                  <?php elseif ($r['sevis_fee_paid']): ?>
                    <span class="badge badge-yes">Paid</span>
                  <?php else: ?>
                    <span class="badge badge-no">Unpaid</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($r['visa_expiration_date']):
                    $expDate = strtotime($r['visa_expiration_date']);
                    $daysLeft = floor(($expDate - time()) / 86400);
                    $color = $daysLeft <= 90 ? '#854F0B' : '#1E1E1E';
                  ?>
                    <span style="color: <?= $color ?>;"><?= date("M j, Y", $expDate) ?></span>
                  <?php else: ?>—<?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
