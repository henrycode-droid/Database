<?php
// ============================================================
// students.php
// All Students — searchable, filterable list
// ============================================================
require_once 'includes/db.php';

// ------------------------------------------------------------
// HANDLE DELETE (POST request from confirm dialog)
// Cascades to enrollment_terms, visa_information, i20_documents,
// and orientation_checklist via ON DELETE CASCADE.
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_student_id'])) {
    $delId = trim($_POST['delete_student_id']);
    if ($delId !== '') {
        $stmt = $pdo->prepare("DELETE FROM students WHERE student_id = ?");
        $stmt->execute([$delId]);
    }
    header("Location: students.php?deleted=1");
    exit;
}


// ------------------------------------------------------------
// SEARCH / FILTER PARAMETERS
// ------------------------------------------------------------
$q       = trim($_GET['q'] ?? '');
$level   = $_GET['level'] ?? '';
$country = $_GET['country'] ?? '';
$term    = $_GET['term'] ?? '';

$where = [];
$params = [];

if ($q !== '') {
    $where[] = "(s.full_name LIKE :q OR s.student_id LIKE :q OR s.country LIKE :q OR s.major LIKE :q)";
    $params[':q'] = "%$q%";
}
if ($level === 'GR' || $level === 'UG') {
    $where[] = "s.level = :level";
    $params[':level'] = $level;
}
if ($country !== '') {
    $where[] = "s.country = :country";
    $params[':country'] = $country;
}
if ($term !== '') {
    $where[] = "EXISTS (SELECT 1 FROM enrollment_terms et2 WHERE et2.student_id = s.student_id AND et2.term_code = :term)";
    $params[':term'] = $term;
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// ------------------------------------------------------------
// MAIN QUERY — students + latest term + visa status
// ------------------------------------------------------------
$sql = "
    SELECT
        s.student_id, s.full_name, s.country, s.level, s.major,
        s.university_email, s.phone,
        et.term_code, et.status,
        vi.visa_expiration_date, vi.sevis_fee_paid,
        i20.i20_issued
    FROM students s
    LEFT JOIN enrollment_terms et
        ON et.term_id = (SELECT MAX(term_id) FROM enrollment_terms WHERE student_id = s.student_id)
    LEFT JOIN visa_information vi ON vi.student_id = s.student_id
    LEFT JOIN i20_documents i20 ON i20.term_id = et.term_id
    $whereSql
    ORDER BY s.full_name ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

// ------------------------------------------------------------
// COUNTRY LIST FOR FILTER DROPDOWN
// ------------------------------------------------------------
$countries = $pdo->query("
    SELECT DISTINCT country FROM students
    WHERE country IS NOT NULL AND country <> ''
    ORDER BY country ASC
")->fetchAll(PDO::FETCH_COLUMN);

// ------------------------------------------------------------
// TERM LIST FOR FILTER DROPDOWN
// ------------------------------------------------------------
$terms = $pdo->query("
    SELECT DISTINCT term_code FROM enrollment_terms
    WHERE term_code IS NOT NULL AND term_code <> ''
    ORDER BY term_code DESC
")->fetchAll(PDO::FETCH_COLUMN);

function v($val) {
    return htmlspecialchars($val ?? '', ENT_QUOTES);
}

$pageTitle = "All Students";
$activeNav = "students";
require_once 'includes/header.php';
?>

  <?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-success">Student record deleted successfully.</div>
  <?php endif; ?>

  <div class="hero">
    <div>
      <h2>All Students</h2>
      <p><?= count($students) ?> student<?= count($students) == 1 ? '' : 's' ?> found</p>
    </div>
    <div class="right">
      <a href="add_student.php" class="btn btn-primary">+ Add New Student</a>
    </div>
  </div>

  <!-- ============================================================
       SEARCH / FILTER BAR
  ============================================================ -->
  <div class="card" id="search">
    <form class="grid" method="GET" action="students.php" style="align-items:end; grid-template-columns: repeat(4, 1fr);">
      <div class="field">
        <label>Search</label>
        <input type="text" name="q" value="<?= v($q) ?>" placeholder="Name, student ID, country, or major...">
      </div>
      <div class="field">
        <label>Level</label>
        <select name="level">
          <option value="">All levels</option>
          <option value="GR" <?= $level === 'GR' ? 'selected' : '' ?>>Graduate (GR)</option>
          <option value="UG" <?= $level === 'UG' ? 'selected' : '' ?>>Undergraduate (UG)</option>
        </select>
      </div>
      <div class="field">
        <label>Country</label>
        <select name="country">
          <option value="">All countries</option>
          <?php foreach ($countries as $c): ?>
            <option value="<?= v($c) ?>" <?= $country === $c ? 'selected' : '' ?>><?= v($c) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Term</label>
        <select name="term">
          <option value="">All terms</option>
          <?php foreach ($terms as $t): ?>
            <option value="<?= v($t) ?>" <?= $term === $t ? 'selected' : '' ?>><?= v($t) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field full" style="flex-direction:row; gap:8px;">
        <button type="submit" class="btn btn-primary">Apply Filters</button>
        <a href="students.php" class="btn btn-secondary">Clear</a>
      </div>
    </form>
  </div>

  <!-- ============================================================
       STUDENTS TABLE
  ============================================================ -->
  <div class="card" style="padding:0; overflow-x:auto;">
    <table class="data-table">
      <thead>
        <tr>
          <th>Student ID</th>
          <th>Name</th>
          <th>Country</th>
          <th>Level</th>
          <th>Major</th>
          <th>Term</th>
          <th>Status</th>
          <th>Visa Expiry</th>
          <th>SEVIS</th>
          <th>I-20</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($students)): ?>
          <tr>
            <td colspan="11" style="text-align:center; color:#999; padding:30px;">
              No students found.
              <?php if ($q !== '' || $level !== '' || $country !== ''): ?>
                Try adjusting your filters or <a href="students.php">view all students</a>.
              <?php else: ?>
                <a href="add_student.php">Add your first student</a> to get started.
              <?php endif; ?>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($students as $s): ?>
            <tr>
              <td><?= v($s['student_id']) ?></td>
              <td><strong><?= v($s['full_name']) ?></strong></td>
              <td><?= v($s['country'] ?: '—') ?></td>
              <td>
                <?php if ($s['level'] === 'GR'): ?>
                  <span class="badge badge-gr">GR</span>
                <?php elseif ($s['level'] === 'UG'): ?>
                  <span class="badge badge-ug">UG</span>
                <?php else: ?>—<?php endif; ?>
              </td>
              <td><?= v($s['major'] ?: '—') ?></td>
              <td><?= v($s['term_code'] ?: '—') ?></td>
              <td><?= v($s['status'] ?: '—') ?></td>
              <td>
                <?php if ($s['visa_expiration_date']): ?>
                  <?php
                    $expDate = strtotime($s['visa_expiration_date']);
                    $daysLeft = floor(($expDate - time()) / 86400);
                    $color = $daysLeft <= 90 ? '#854F0B' : '#1E1E1E';
                  ?>
                  <span style="color: <?= $color ?>;"><?= date("M j, Y", $expDate) ?></span>
                <?php else: ?>—<?php endif; ?>
              </td>
              <td>
                <?php if ($s['sevis_fee_paid'] === null): ?>—
                <?php elseif ($s['sevis_fee_paid']): ?>
                  <span class="badge badge-yes">Paid</span>
                <?php else: ?>
                  <span class="badge badge-no">Unpaid</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($s['i20_issued']): ?>
                  <span class="badge badge-yes">Issued</span>
                <?php else: ?>
                  <span class="badge badge-no">Pending</span>
                <?php endif; ?>
              </td>
              <td style="white-space:nowrap;">
                <a href="add_student.php?student_id=<?= urlencode($s['student_id']) ?>" class="btn btn-secondary" style="padding:5px 12px; font-size:11px;">Edit</a>
                <form method="POST" action="students.php" style="display:inline;"
                      onsubmit="return confirm('Delete <?= v(addslashes($s['full_name'])) ?> (<?= v($s['student_id']) ?>)? This will permanently remove all their records (visa, I-20, orientation). This cannot be undone.');">
                  <input type="hidden" name="delete_student_id" value="<?= v($s['student_id']) ?>">
                  <button type="submit" class="btn btn-secondary" style="padding:5px 12px; font-size:11px; color:#B91C1C; border-color:#F0C1C1; cursor:pointer;">
                    Delete
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

<?php require_once 'includes/footer.php'; ?>
