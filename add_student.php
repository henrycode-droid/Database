<?php
// ============================================================
// add_student.php
// Single form to add (or edit) a student across ALL tables:
//   students, enrollment_terms, visa_information,
//   i20_documents, orientation_checklist
// ============================================================
require_once 'includes/db.php';

$editMode = false;
$student = [];
$enrollment = [];
$visa = [];
$i20 = [];
$checklist = [];
$errors = [];
$success = false;

// ------------------------------------------------------------
// EDIT MODE: load existing data if student_id passed in URL
// ------------------------------------------------------------
if (isset($_GET['student_id']) && $_GET['student_id'] !== '') {
    $editMode = true;
    $sid = trim($_GET['student_id']);
    $sid = strtoupper($sid);

    $stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
    $stmt->execute([$sid]);
    $student = $stmt->fetch() ?: [];

    $stmt = $pdo->prepare("SELECT * FROM enrollment_terms WHERE student_id = ? ORDER BY term_id DESC LIMIT 1");
    $stmt->execute([$sid]);
    $enrollment = $stmt->fetch() ?: [];

    $stmt = $pdo->prepare("SELECT * FROM visa_information WHERE student_id = ?");
    $stmt->execute([$sid]);
    $visa = $stmt->fetch() ?: [];

    if (!empty($enrollment['term_id'])) {
        $stmt = $pdo->prepare("SELECT * FROM i20_documents WHERE term_id = ?");
        $stmt->execute([$enrollment['term_id']]);
        $i20 = $stmt->fetch() ?: [];

        $stmt = $pdo->prepare("SELECT * FROM orientation_checklist WHERE term_id = ?");
        $stmt->execute([$enrollment['term_id']]);
        $checklist = $stmt->fetch() ?: [];
    }
}

// ------------------------------------------------------------
// HANDLE FORM SUBMISSION
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- Basic validation ---
    $student_id = trim($_POST['student_id'] ?? '');
    $student_id = strtoupper($student_id); // normalize J-numbers to uppercase
    $full_name  = trim($_POST['full_name'] ?? '');
    $level      = $_POST['level'] ?? '';

    if ($student_id === '') $errors[] = "Student ID is required.";
    if ($full_name === '')  $errors[] = "Full name is required.";
    if (!in_array($level, ['GR', 'UG'])) $errors[] = "Level must be GR or UG.";

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // -----------------------------------------------
            // 1. STUDENTS table — INSERT ... ON DUPLICATE KEY UPDATE
            // -----------------------------------------------
            $sql = "INSERT INTO students
                        (student_id, full_name, personal_email, university_email,
                         phone, country, level, major, recruiter, merit,
                         transfer_or_new, in_state, notes, last_contact)
                    VALUES
                        (:student_id, :full_name, :personal_email, :university_email,
                         :phone, :country, :level, :major, :recruiter, :merit,
                         :transfer_or_new, :in_state, :notes, :last_contact)
                    ON DUPLICATE KEY UPDATE
                        full_name = VALUES(full_name),
                        personal_email = VALUES(personal_email),
                        university_email = VALUES(university_email),
                        phone = VALUES(phone),
                        country = VALUES(country),
                        level = VALUES(level),
                        major = VALUES(major),
                        recruiter = VALUES(recruiter),
                        merit = VALUES(merit),
                        transfer_or_new = VALUES(transfer_or_new),
                        in_state = VALUES(in_state),
                        notes = VALUES(notes),
                        last_contact = VALUES(last_contact)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':student_id'       => $student_id,
                ':full_name'        => $full_name,
                ':personal_email'   => $_POST['personal_email'] ?: null,
                ':university_email' => $_POST['university_email'] ?: null,
                ':phone'            => $_POST['phone'] ?: null,
                ':country'          => $_POST['country'] ?: null,
                ':level'            => $level,
                ':major'            => $_POST['major'] ?: null,
                ':recruiter'        => $_POST['recruiter'] ?? 'N',
                ':merit'            => $_POST['merit'] ?? 'N',
                ':transfer_or_new'  => $_POST['transfer_or_new'] ?? 'N',
                ':in_state'         => $_POST['in_state'] ?? 'N',
                ':notes'            => $_POST['notes'] ?: null,
                ':last_contact'     => $_POST['last_contact'] ?: null,
            ]);

            // -----------------------------------------------
            // 2. ENROLLMENT_TERMS — one row per student+term
            // -----------------------------------------------
            $term_code = trim($_POST['term_code'] ?? '');
            $term_id = null;

            if ($term_code !== '') {
                $sql = "INSERT INTO enrollment_terms
                            (student_id, term_code, level, major, status,
                             program_start_date, start_date_changed_to, accepted_term)
                        VALUES
                            (:student_id, :term_code, :level, :major, :status,
                             :program_start_date, :start_date_changed_to, :accepted_term)
                        ON DUPLICATE KEY UPDATE
                            level = VALUES(level),
                            major = VALUES(major),
                            status = VALUES(status),
                            program_start_date = VALUES(program_start_date),
                            start_date_changed_to = VALUES(start_date_changed_to),
                            accepted_term = VALUES(accepted_term)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':student_id'            => $student_id,
                    ':term_code'             => $term_code,
                    ':level'                 => $level,
                    ':major'                 => $_POST['major'] ?: null,
                    ':status'                => $_POST['status'] ?: 'Pending I-20',
                    ':program_start_date'    => $_POST['program_start_date'] ?: null,
                    ':start_date_changed_to' => $_POST['start_date_changed_to'] ?: null,
                    ':accepted_term'         => $_POST['accepted_term'] ?: null,
                ]);

                // Get the term_id (whether just inserted or already existed)
                $stmt = $pdo->prepare("SELECT term_id FROM enrollment_terms WHERE student_id = ? AND term_code = ?");
                $stmt->execute([$student_id, $term_code]);
                $term_id = $stmt->fetchColumn();
            }

            // -----------------------------------------------
            // 3. VISA_INFORMATION — one row per student
            // -----------------------------------------------
            $sql = "INSERT INTO visa_information
                        (student_id, visa_type, visa_number, visa_issuance_date,
                         visa_expiration_date, visa_issuance_post, port_of_entry,
                         date_of_entry, i94_admission_number, admit_until_date,
                         sevis_fee_paid, visa_issued)
                    VALUES
                        (:student_id, :visa_type, :visa_number, :visa_issuance_date,
                         :visa_expiration_date, :visa_issuance_post, :port_of_entry,
                         :date_of_entry, :i94_admission_number, :admit_until_date,
                         :sevis_fee_paid, :visa_issued)
                    ON DUPLICATE KEY UPDATE
                        visa_type = VALUES(visa_type),
                        visa_number = VALUES(visa_number),
                        visa_issuance_date = VALUES(visa_issuance_date),
                        visa_expiration_date = VALUES(visa_expiration_date),
                        visa_issuance_post = VALUES(visa_issuance_post),
                        port_of_entry = VALUES(port_of_entry),
                        date_of_entry = VALUES(date_of_entry),
                        i94_admission_number = VALUES(i94_admission_number),
                        admit_until_date = VALUES(admit_until_date),
                        sevis_fee_paid = VALUES(sevis_fee_paid),
                        visa_issued = VALUES(visa_issued)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':student_id'           => $student_id,
                ':visa_type'            => $_POST['visa_type'] ?: null,
                ':visa_number'          => $_POST['visa_number'] ?: null,
                ':visa_issuance_date'   => $_POST['visa_issuance_date'] ?: null,
                ':visa_expiration_date' => $_POST['visa_expiration_date'] ?: null,
                ':visa_issuance_post'   => $_POST['visa_issuance_post'] ?: null,
                ':port_of_entry'        => $_POST['port_of_entry'] ?: null,
                ':date_of_entry'        => $_POST['date_of_entry'] ?: null,
                ':i94_admission_number' => $_POST['i94_admission_number'] ?: null,
                ':admit_until_date'     => $_POST['admit_until_date'] ?: null,
                ':sevis_fee_paid'       => isset($_POST['sevis_fee_paid']) ? 1 : 0,
                ':visa_issued'          => isset($_POST['visa_issued']) ? 1 : 0,
            ]);

            // -----------------------------------------------
            // 4 & 5. I-20 DOCUMENTS + ORIENTATION CHECKLIST
            //         (require a term_id to link to)
            // -----------------------------------------------
            if ($term_id) {
                // I-20 documents
                $sql = "INSERT INTO i20_documents
                            (student_id, term_id, i20_number, i20_document_received,
                             export_controls_requested, export_controls_cleared,
                             i20_issued, updated_i20, deferral_form_received)
                        VALUES
                            (:student_id, :term_id, :i20_number, :i20_document_received,
                             :export_controls_requested, :export_controls_cleared,
                             :i20_issued, :updated_i20, :deferral_form_received)
                        ON DUPLICATE KEY UPDATE
                            i20_number = VALUES(i20_number),
                            i20_document_received = VALUES(i20_document_received),
                            export_controls_requested = VALUES(export_controls_requested),
                            export_controls_cleared = VALUES(export_controls_cleared),
                            i20_issued = VALUES(i20_issued),
                            updated_i20 = VALUES(updated_i20),
                            deferral_form_received = VALUES(deferral_form_received)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':student_id'                => $student_id,
                    ':term_id'                   => $term_id,
                    ':i20_number'                => $_POST['i20_number'] ?: null,
                    ':i20_document_received'     => $_POST['i20_document_received'] ?: null,
                    ':export_controls_requested' => $_POST['export_controls_requested'] ?: null,
                    ':export_controls_cleared'   => $_POST['export_controls_cleared'] ?: null,
                    ':i20_issued'                => $_POST['i20_issued_date'] ?: null,
                    ':updated_i20'               => $_POST['updated_i20'] ?: null,
                    ':deferral_form_received'    => isset($_POST['deferral_form_received']) ? 1 : 0,
                ]);

                // Orientation checklist
                $sql = "INSERT INTO orientation_checklist
                            (student_id, term_id, acceptance_letter_sent, welcome_sent,
                             welcome_resent, next_steps_letter_sent, emergency_appointment_letter,
                             faculty_letter_sent, your_new_home_letter_sent, blackboard_course_emailed,
                             housing_email_sent, id_username_login_sent, orientation_begun,
                             orientation_complete, checked_in, updated_goaintl)
                        VALUES
                            (:student_id, :term_id, :acceptance_letter_sent, :welcome_sent,
                             :welcome_resent, :next_steps_letter_sent, :emergency_appointment_letter,
                             :faculty_letter_sent, :your_new_home_letter_sent, :blackboard_course_emailed,
                             :housing_email_sent, :id_username_login_sent, :orientation_begun,
                             :orientation_complete, :checked_in, :updated_goaintl)
                        ON DUPLICATE KEY UPDATE
                            acceptance_letter_sent = VALUES(acceptance_letter_sent),
                            welcome_sent = VALUES(welcome_sent),
                            welcome_resent = VALUES(welcome_resent),
                            next_steps_letter_sent = VALUES(next_steps_letter_sent),
                            emergency_appointment_letter = VALUES(emergency_appointment_letter),
                            faculty_letter_sent = VALUES(faculty_letter_sent),
                            your_new_home_letter_sent = VALUES(your_new_home_letter_sent),
                            blackboard_course_emailed = VALUES(blackboard_course_emailed),
                            housing_email_sent = VALUES(housing_email_sent),
                            id_username_login_sent = VALUES(id_username_login_sent),
                            orientation_begun = VALUES(orientation_begun),
                            orientation_complete = VALUES(orientation_complete),
                            checked_in = VALUES(checked_in),
                            updated_goaintl = VALUES(updated_goaintl)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':student_id'                   => $student_id,
                    ':term_id'                      => $term_id,
                    ':acceptance_letter_sent'       => $_POST['acceptance_letter_sent'] ?: null,
                    ':welcome_sent'                 => $_POST['welcome_sent'] ?: null,
                    ':welcome_resent'               => $_POST['welcome_resent'] ?: null,
                    ':next_steps_letter_sent'       => $_POST['next_steps_letter_sent'] ?: null,
                    ':emergency_appointment_letter' => $_POST['emergency_appointment_letter'] ?: null,
                    ':faculty_letter_sent'          => $_POST['faculty_letter_sent'] ?: null,
                    ':your_new_home_letter_sent'    => $_POST['your_new_home_letter_sent'] ?: null,
                    ':blackboard_course_emailed'    => $_POST['blackboard_course_emailed'] ?: null,
                    ':housing_email_sent'           => $_POST['housing_email_sent'] ?: null,
                    ':id_username_login_sent'       => $_POST['id_username_login_sent'] ?: null,
                    ':orientation_begun'            => $_POST['orientation_begun'] ?: null,
                    ':orientation_complete'         => $_POST['orientation_complete'] ?: null,
                    ':checked_in'                   => $_POST['checked_in'] ?: null,
                    ':updated_goaintl'              => $_POST['updated_goaintl'] ?: null,
                ]);
            }

            $pdo->commit();
            $success = true;

            // Redirect to avoid re-submission on refresh
            header("Location: add_student.php?student_id=" . urlencode($student_id) . "&saved=1");
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// Re-load data after redirect (saved=1)
if (isset($_GET['saved']) && $_GET['saved'] == '1') {
    $success = true;
}

// Helper to safely output values
function v($val) {
    return htmlspecialchars($val ?? '', ENT_QUOTES);
}

$pageTitle = $editMode ? "Edit Student" : "Add New Student";
$activeNav = "add_student";
require_once 'includes/header.php';
?>

  <div class="hero">
    <h2><?= $editMode ? 'Edit Student Record' : 'Add New Student' ?></h2>
    <p>Fill in the fields below. Sections beyond Personal Information are optional and can be completed later.</p>
  </div>

  <?php if ($success): ?>
    <div class="alert alert-success">Student record saved successfully.</div>
  <?php endif; ?>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-error">
      <strong>Please fix the following:</strong>
      <ul><?php foreach ($errors as $err): ?><li><?= v($err) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <form method="POST" action="<?= isset($_GET['student_id']) ? '?student_id=' . urlencode($_GET['student_id']) : '' ?>">

    <!-- ============================================================
         SECTION 1: PERSONAL INFORMATION (students table)
    ============================================================ -->
    <div class="form-section">
      <h3>Personal Information</h3>
      <div class="grid">
        <div class="field">
          <label>Student ID (J-number) *</label>
          <input type="text" name="student_id" value="<?= v($student['student_id'] ?? $_GET['student_id'] ?? '') ?>"
                 <?= $editMode ? 'readonly' : '' ?> required placeholder="J00123456">
        </div>
        <div class="field">
          <label>Full Name *</label>
          <input type="text" name="full_name" value="<?= v($student['full_name'] ?? '') ?>" required placeholder="First Last">
        </div>
        <div class="field">
          <label>Country of Origin</label>
          <input type="text" name="country" value="<?= v($student['country'] ?? '') ?>" placeholder="e.g. Nigeria">
        </div>
        <div class="field">
          <label>Personal Email</label>
          <input type="email" name="personal_email" value="<?= v($student['personal_email'] ?? '') ?>" placeholder="student@email.com">
        </div>
        <div class="field">
          <label>University Email</label>
          <input type="email" name="university_email" value="<?= v($student['university_email'] ?? '') ?>" placeholder="@jaguar.tamu.edu">
        </div>
        <div class="field">
          <label>Phone Number</label>
          <input type="tel" name="phone" value="<?= v($student['phone'] ?? '') ?>" placeholder="+1 (000) 000-0000">
        </div>
        <div class="field">
          <label>Last Contact Date</label>
          <input type="date" name="last_contact" value="<?= v($student['last_contact'] ?? '') ?>">
        </div>
        <div class="field full">
          <label>Notes</label>
          <textarea name="notes" placeholder="Additional staff notes..."><?= v($student['notes'] ?? '') ?></textarea>
        </div>
      </div>
    </div>

    <!-- ============================================================
         SECTION 2: ACADEMIC / ENROLLMENT (students + enrollment_terms)
    ============================================================ -->
    <div class="form-section">
      <h3>Academic &amp; Enrollment Information</h3>
      <div class="grid">
        <div class="field">
          <label>Level *</label>
          <select name="level" required>
            <option value="">Select...</option>
            <option value="GR" <?= ($student['level'] ?? '') === 'GR' ? 'selected' : '' ?>>Graduate (GR)</option>
            <option value="UG" <?= ($student['level'] ?? '') === 'UG' ? 'selected' : '' ?>>Undergraduate (UG)</option>
          </select>
        </div>
        <div class="field">
          <label>Major / Program</label>
          <input type="text" name="major" value="<?= v($student['major'] ?? $enrollment['major'] ?? '') ?>" placeholder="e.g. Computer Science">
        </div>
        <div class="field">
          <label>Term Code</label>
          <input type="text" name="term_code" value="<?= v($enrollment['term_code'] ?? '') ?>" placeholder="e.g. 202610 ">
        </div>
        <div class="field">
          <label>Term Accepted</label>
          <input type="text" name="accepted_term" value="<?= v($enrollment['accepted_term'] ?? '') ?>" placeholder="e.g. (Fall 2026)">
        </div>
        <div class="field">
          <label>Status</label>
          <select name="status">
            <?php
            $statuses = ['Pending I-20','Active','Visa Pending','Deferred','Checked In','Orientation Complete','Withdrawn'];
            $curStatus = $enrollment['status'] ?? 'Pending I-20';
            foreach ($statuses as $st) {
                $sel = ($st === $curStatus) ? 'selected' : '';
                echo "<option value=\"" . v($st) . "\" $sel>" . v($st) . "</option>";
            }
            ?>
          </select>
        </div>
        <div class="field">
          <label>Program Start Date</label>
          <input type="date" name="program_start_date" value="<?= v($enrollment['program_start_date'] ?? '') ?>">
        </div>
        <div class="field">
          <label>Start Date Changed To</label>
          <input type="date" name="start_date_changed_to" value="<?= v($enrollment['start_date_changed_to'] ?? '') ?>">
        </div>
      </div>

      <div class="grid-2" style="margin-top:16px;">
        <div class="field">
          <label>Transfer or New </label>
          <div class="pill-group">
            <label class="pill <?= ($student['transfer_or_new'] ?? 'N') === 'N' ? 'active' : '' ?>">
              <input type="radio" name="transfer_or_new" value="N" <?= ($student['transfer_or_new'] ?? 'N') === 'N' ? 'checked' : '' ?>> New
            </label>
            <label class="pill <?= ($student['transfer_or_new'] ?? '') === 'T' ? 'active' : '' ?>">
              <input type="radio" name="transfer_or_new" value="T" <?= ($student['transfer_or_new'] ?? '') === 'T' ? 'checked' : '' ?>> Transfer
            </label>
          </div>
        </div>
        <div class="field">
          <label>In-State Tuition</label>
          <div class="pill-group">
            <label class="pill <?= ($student['in_state'] ?? 'N') === 'Y' ? 'active' : '' ?>">
              <input type="radio" name="in_state" value="Y" <?= ($student['in_state'] ?? '') === 'Y' ? 'checked' : '' ?>> Yes
            </label>
            <label class="pill <?= ($student['in_state'] ?? 'N') === 'N' ? 'active' : '' ?>">
              <input type="radio" name="in_state" value="N" <?= ($student['in_state'] ?? 'N') === 'N' ? 'checked' : '' ?>> No
            </label>
          </div>
        </div>
        <div class="field">
          <label>Merit Scholarship</label>
          <div class="pill-group">
            <label class="pill <?= ($student['merit'] ?? 'N') === 'Y' ? 'active' : '' ?>">
              <input type="radio" name="merit" value="Y" <?= ($student['merit'] ?? '') === 'Y' ? 'checked' : '' ?>> Yes
            </label>
            <label class="pill <?= ($student['merit'] ?? 'N') === 'N' ? 'active' : '' ?>">
              <input type="radio" name="merit" value="N" <?= ($student['merit'] ?? 'N') === 'N' ? 'checked' : '' ?>> No
            </label>
          </div>
        </div>
        <div class="field">
          <label>Recruiter</label>
          <div class="pill-group">
            <label class="pill <?= ($student['recruiter'] ?? 'N') === 'Y' ? 'active' : '' ?>">
              <input type="radio" name="recruiter" value="Y" <?= ($student['recruiter'] ?? '') === 'Y' ? 'checked' : '' ?>> Yes
            </label>
            <label class="pill <?= ($student['recruiter'] ?? 'N') === 'N' ? 'active' : '' ?>">
              <input type="radio" name="recruiter" value="N" <?= ($student['recruiter'] ?? 'N') === 'N' ? 'checked' : '' ?>> No
            </label>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================
         SECTION 3: VISA INFORMATION (visa_information table)
    ============================================================ -->
    <div class="form-section">
      <h3>Visa &amp; Travel Information</h3>
      <div class="grid">
        <div class="field">
          <label>Visa Type</label>
          <input type="text" name="visa_type" value="<?= v($visa['visa_type'] ?? '') ?>" placeholder="F-1, J-1, H-4...">
        </div>
        <div class="field">
          <label>Visa Number</label>
          <input type="text" name="visa_number" value="<?= v($visa['visa_number'] ?? '') ?>">
        </div>
        <div class="field">
          <label>Visa Issuance Post</label>
          <input type="text" name="visa_issuance_post" value="<?= v($visa['visa_issuance_post'] ?? '') ?>" placeholder="e.g. Lagos, Mumbai">
        </div>
        <div class="field">
          <label>Visa Issuance Date</label>
          <input type="date" name="visa_issuance_date" value="<?= v($visa['visa_issuance_date'] ?? '') ?>">
        </div>
        <div class="field">
          <label>Visa Expiration Date</label>
          <input type="date" name="visa_expiration_date" value="<?= v($visa['visa_expiration_date'] ?? '') ?>">
        </div>
        <div class="field">
          <label>Port of Entry</label>
          <input type="text" name="port_of_entry" value="<?= v($visa['port_of_entry'] ?? '') ?>" placeholder="e.g. DFW">
        </div>
        <div class="field">
          <label>Date of Entry</label>
          <input type="date" name="date_of_entry" value="<?= v($visa['date_of_entry'] ?? '') ?>">
        </div>
        <div class="field">
          <label>I-94 Admission Number</label>
          <input type="text" name="i94_admission_number" value="<?= v($visa['i94_admission_number'] ?? '') ?>">
        </div>
        <div class="field">
          <label>Admit Until Date</label>
          <input type="text" name="admit_until_date" value="<?= v($visa['admit_until_date'] ?? '') ?>" placeholder="D/S or a date">
        </div>
      </div>
      <div class="grid-2" style="margin-top:16px;">
        <div class="check-row">
          <input type="checkbox" id="sevis_fee_paid" name="sevis_fee_paid" <?= !empty($visa['sevis_fee_paid']) ? 'checked' : '' ?>>
          <label for="sevis_fee_paid">SEVIS fee paid</label>
        </div>
        <div class="check-row">
          <input type="checkbox" id="visa_issued" name="visa_issued" <?= !empty($visa['visa_issued']) ? 'checked' : '' ?>>
          <label for="visa_issued">Visa issued</label>
        </div>
      </div>
    </div>

    <!-- ============================================================
         SECTION 4: I-20 DOCUMENTS (i20_documents table)
    ============================================================ -->
    <div class="form-section">
      <h3>I-20 Document Tracking</h3>
      <p style="font-size:11px;color:#999;margin-bottom:14px;">Requires a Term Code above to save these fields.</p>
      <div class="grid">
        <div class="field">
          <label>I-20 Number</label>
          <input type="text" name="i20_number" value="<?= v($i20['i20_number'] ?? '') ?>" placeholder="N-number">
        </div>
        <div class="field">
          <label>I-20 Document Received</label>
          <input type="date" name="i20_document_received" value="<?= v($i20['i20_document_received'] ?? '') ?>">
        </div>
        <div class="field">
          <label>I-20 Issued Date</label>
          <input type="date" name="i20_issued_date" value="<?= v($i20['i20_issued'] ?? '') ?>">
        </div>
        <div class="field">
          <label>Export Controls Requested</label>
          <input type="date" name="export_controls_requested" value="<?= v($i20['export_controls_requested'] ?? '') ?>">
        </div>
        <div class="field">
          <label>Export Controls Cleared</label>
          <input type="date" name="export_controls_cleared" value="<?= v($i20['export_controls_cleared'] ?? '') ?>">
        </div>
        <div class="field">
          <label>Updated I-20 Date</label>
          <input type="date" name="updated_i20" value="<?= v($i20['updated_i20'] ?? '') ?>">
        </div>
      </div>
      <div class="check-row" style="margin-top:14px;">
        <input type="checkbox" id="deferral_form_received" name="deferral_form_received" <?= !empty($i20['deferral_form_received']) ? 'checked' : '' ?>>
        <label for="deferral_form_received">Deferral form received</label>
      </div>
    </div>

    <!-- ============================================================
         SECTION 5: ORIENTATION CHECKLIST (orientation_checklist table)
    ============================================================ -->
    <div class="form-section">
      <h3>Orientation &amp; Communications Checklist</h3>
      <p style="font-size:11px;color:#999;margin-bottom:14px;">Enter the date each step was completed. Leave blank if not yet done.</p>
      <div class="checklist-grid">
        <div class="field"><label>Acceptance Letter Sent</label><input type="date" name="acceptance_letter_sent" value="<?= v($checklist['acceptance_letter_sent'] ?? '') ?>"></div>
        <div class="field"><label>Welcome Sent</label><input type="date" name="welcome_sent" value="<?= v($checklist['welcome_sent'] ?? '') ?>"></div>
        <div class="field"><label>Welcome Resent</label><input type="date" name="welcome_resent" value="<?= v($checklist['welcome_resent'] ?? '') ?>"></div>
        <div class="field"><label>Next Steps Letter Sent</label><input type="date" name="next_steps_letter_sent" value="<?= v($checklist['next_steps_letter_sent'] ?? '') ?>"></div>
        <div class="field"><label>Emergency Appt. Letter</label><input type="date" name="emergency_appointment_letter" value="<?= v($checklist['emergency_appointment_letter'] ?? '') ?>"></div>
        <div class="field"><label>Faculty Letter Sent</label><input type="date" name="faculty_letter_sent" value="<?= v($checklist['faculty_letter_sent'] ?? '') ?>"></div>
        <div class="field"><label>"Your New Home" Letter</label><input type="date" name="your_new_home_letter_sent" value="<?= v($checklist['your_new_home_letter_sent'] ?? '') ?>"></div>
        <div class="field"><label>Blackboard Course Emailed</label><input type="date" name="blackboard_course_emailed" value="<?= v($checklist['blackboard_course_emailed'] ?? '') ?>"></div>
        <div class="field"><label>Housing Email Sent</label><input type="date" name="housing_email_sent" value="<?= v($checklist['housing_email_sent'] ?? '') ?>"></div>
        <div class="field"><label>ID/Username/Login Sent</label><input type="date" name="id_username_login_sent" value="<?= v($checklist['id_username_login_sent'] ?? '') ?>"></div>
        <div class="field"><label>Orientation Begun</label><input type="date" name="orientation_begun" value="<?= v($checklist['orientation_begun'] ?? '') ?>"></div>
        <div class="field"><label>Orientation Complete</label><input type="date" name="orientation_complete" value="<?= v($checklist['orientation_complete'] ?? '') ?>"></div>
        <div class="field"><label>Checked In</label><input type="date" name="checked_in" value="<?= v($checklist['checked_in'] ?? '') ?>"></div>
        <div class="field"><label>Updated in GOAINTL</label><input type="date" name="updated_goaintl" value="<?= v($checklist['updated_goaintl'] ?? '') ?>"></div>
      </div>
    </div>

    <div class="actions">
      <a href="index.php" class="btn btn-cancel">Cancel</a>
      <button type="submit" class="btn btn-save"><?= $editMode ? 'Update Record' : 'Save Student' ?></button>
    </div>

  </form>
</div>

<?php require_once 'includes/footer.php'; ?>

<script>
// Pill toggle behavior — clicking a pill highlights it and unhighlights its sibling
document.querySelectorAll('.pill-group').forEach(group => {
  group.querySelectorAll('.pill').forEach(pill => {
    pill.addEventListener('click', () => {
      group.querySelectorAll('.pill').forEach(p => p.classList.remove('active'));
      pill.classList.add('active');
    });
  });
});
</script>
