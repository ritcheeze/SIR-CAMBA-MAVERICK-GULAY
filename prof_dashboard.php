<?php
session_start();

// Redirect to login page if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require __DIR__ . '/db.php';

// Load logged-in user role from DB (so we don't depend on client/session tampering)
$meStmt = $conn->prepare("SELECT id, fullname, role FROM users WHERE id = ?");
$meStmt->bind_param("i", $_SESSION['user_id']);
$meStmt->execute();
$me = $meStmt->get_result()->fetch_assoc();
$meStmt->close();

$profRole = $me['role'] ?? null;

// Trim whitespace and force lowercase to support consistent admin role checks.
$profRole = is_string($profRole) ? strtolower(trim($profRole)) : $profRole;
if ($profRole !== 'admin') {
    echo "<script>alert('Access denied. Admin account required.'); window.location.href='dashboard.php';</script>";
    exit;
}

if (isset($_GET['ajax_fetch_student_subjects'])) {
    header('Content-Type: application/json');
    $targetStudentId = (int)($_GET['student_id'] ?? 0);

    $stmt = $conn->prepare(
        "SELECT cs.id AS subject_id, cs.subject_name, sg.grade
         FROM student_enrollments se
         JOIN curriculum_subjects cs ON se.subject_id = cs.id
         LEFT JOIN student_grades sg ON sg.subject_id = cs.id AND sg.user_id = se.user_id
         WHERE se.user_id = ? AND se.status = 'approved'
         ORDER BY cs.subject_name ASC"
    );
    $stmt->bind_param("i", $targetStudentId);
    $stmt->execute();
    echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    $stmt->close();
    exit;
}

if (isset($_GET['action_process_enrollment'], $_GET['student_id'])) {
    $action = $_GET['action_process_enrollment'];
    $targetStudentId = (int)$_GET['student_id'];

    if ($action === 'approve') {
        $updateEnr = $conn->prepare("UPDATE student_enrollments SET status = 'approved' WHERE user_id = ?");
        $updateEnr->bind_param("i", $targetStudentId);
        $updateEnr->execute();
        $updateEnr->close();
        echo "<script>alert('Enrollment approved.'); window.location.href='prof_dashboard.php';</script>";
        exit;
    }

    if ($action === 'reject') {
        $rejectEnr = $conn->prepare("DELETE FROM student_enrollments WHERE user_id = ?");
        $rejectEnr->bind_param("i", $targetStudentId);
        $rejectEnr->execute();
        $rejectEnr->close();
        echo "<script>alert('Enrollment request rejected.'); window.location.href='prof_dashboard.php';</script>";
        exit;
    }
}

// Handle grade update
$success = null;
$error = null;

// Curriculum subject CRUD (admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['curriculum_subject_action'])) {
    $action = $_POST['curriculum_subject_action'] ?? '';
    $course = trim($_POST['subject_course'] ?? '');
    $yearLevel = isset($_POST['subject_year_level']) ? (int)$_POST['subject_year_level'] : 0;
    $semester = isset($_POST['subject_semester']) ? (int)$_POST['subject_semester'] : 0;
    $subjectName = trim($_POST['subject_name'] ?? '');

    if ($action === 'add') {
        if ($course === '' || $yearLevel < 1 || $yearLevel > 4 || $semester < 1 || $semester > 2 || $subjectName === '') {
            $error = 'Please provide valid curriculum subject details.';
        } else {
            $ins = $conn->prepare(
                "INSERT INTO curriculum_subjects (course, year_level, semester, subject_name)
                 VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE subject_name = VALUES(subject_name)"
            );
            $ins->bind_param('s i i s', $course, $yearLevel, $semester, $subjectName);
            $ins->execute();
            $ins->close();

            $success = 'Curriculum subject saved.';
        }
    }

    if ($action === 'delete') {
        $subjectId = isset($_POST['curriculum_subject_id']) ? (int)$_POST['curriculum_subject_id'] : 0;
        if ($subjectId <= 0) {
            $error = 'Invalid curriculum subject id.';
        } else {
            $del = $conn->prepare('DELETE FROM curriculum_subjects WHERE id = ?');
            $del->bind_param('i', $subjectId);
            $del->execute();
            $del->close();

            $success = 'Curriculum subject deleted.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_grades'])) {
    $targetStudentId = (int)($_POST['id'] ?? 0);
    $studentCode = trim($_POST['student_id'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $section = trim($_POST['section'] ?? '');
    $yearLevel = isset($_POST['year_level']) ? (int)$_POST['year_level'] : 0;
    $gpa = isset($_POST['gpa']) ? (float)$_POST['gpa'] : -1;

    if ($targetStudentId <= 0 || $studentCode === '' || $course === '' || $yearLevel < 1 || $yearLevel > 10 || $gpa < 0 || $gpa > 4) {
        $error = 'Please select a student and provide valid student record details.';
    } else {
        $updateStudent = $conn->prepare(
            'UPDATE users
             SET course = ?, section = ?, year_level = ?, gpa = ?
             WHERE id = ? AND student_id = ?'
        );
        $updateStudent->bind_param('ssidis', $course, $section, $yearLevel, $gpa, $targetStudentId, $studentCode);
        $saved = $updateStudent->execute();
        $updateStudent->close();

        if ($saved) {
            $success = 'Student record updated successfully.';
        } else {
            $error = 'Student record update failed. Please try again.';
        }
    }
}

// List curriculum subjects for admin UI
$curriculumSubjectsStmt = $conn->prepare(
    "SELECT id, course, year_level, semester, subject_name
     FROM curriculum_subjects
     ORDER BY course ASC, year_level ASC, semester ASC, subject_name ASC"
);
$curriculumSubjectsStmt->execute();
$curriculumSubjects = $curriculumSubjectsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$curriculumSubjectsStmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_submit_student_evaluations'])) {

    $targetStudentId = (int)($_POST['student_user_id'] ?? 0);
    $assignedSection = trim($_POST['override_section'] ?? '');
    $standingStatus = trim($_POST['academic_status'] ?? 'Regular');
    $allowedStatuses = ['Regular', 'Irregular', 'Dropped'];

    if ($targetStudentId > 0 && $assignedSection !== '' && in_array($standingStatus, $allowedStatuses, true)) {
        $updateUserMain = $conn->prepare("UPDATE users SET section = ?, enrollment_status = ? WHERE id = ?");
        $updateUserMain->bind_param("ssi", $assignedSection, $standingStatus, $targetStudentId);
        $updateUserMain->execute();
        $updateUserMain->close();

        if (isset($_POST['grades']) && is_array($_POST['grades'])) {
            foreach ($_POST['grades'] as $subjectId => $scoreVal) {
                if ($scoreVal === '' || $scoreVal === null) {
                    continue;
                }

                $subjectId = (int)$subjectId;
                $scoreFloat = (float)$scoreVal;
                if ($scoreFloat === 0.0 && (string)$scoreVal !== '0' && (string)$scoreVal !== '0.00') {
                    // allow 0 grades if explicitly entered; otherwise ignore accidental non-numeric
                    // (keeps behavior tolerant)
                }

                $gradeStmt = $conn->prepare(
                    "INSERT INTO student_grades (user_id, subject_id, grade)
                     VALUES (?, ?, ?)
                     ON DUPLICATE KEY UPDATE grade = VALUES(grade)"
                );
                $gradeStmt->bind_param("iid", $targetStudentId, $subjectId, $scoreFloat);
                $gradeStmt->execute();
                $gradeStmt->close();
            }
        }

        $gpaStmt = $conn->prepare(
            "UPDATE users u
             SET u.gpa = (
                SELECT IFNULL(AVG(sg.grade), 0)
                FROM student_grades sg
                JOIN student_enrollments se
                  ON se.user_id = sg.user_id
                 AND se.subject_id = sg.subject_id
                WHERE sg.user_id = u.id
                  AND sg.grade IS NOT NULL
             )
             WHERE u.id = ?"
        );
        $gpaStmt->bind_param("i", $targetStudentId);
        $gpaStmt->execute();
        $gpaStmt->close();

        $success = "Student evaluation saved successfully. GPA updated.";

    } else {
        $error = "Please select a valid student, section, and status.";
    }
}

$pendingStmt = $conn->prepare(
    "SELECT DISTINCT u.id, u.fullname, u.email, u.course, u.year_level, u.section
     FROM users u
     JOIN student_enrollments se ON u.id = se.user_id
     WHERE se.status = 'pending'
     ORDER BY u.fullname ASC"
);
$pendingStmt->execute();
$pendingEnrollees = $pendingStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$pendingStmt->close();

// Load all students for display (Added section column retrieval)
$studentsStmt = $conn->prepare("SELECT id, fullname, student_id, course, section, year_level, gpa, enrollment_status FROM users WHERE student_id IS NOT NULL AND (role IS NULL OR LOWER(TRIM(role)) != 'admin') ORDER BY fullname ASC");
$studentsStmt->execute();
$students = $studentsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$studentsStmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="dashboard.css">
    <style>
        .content-wrap { max-width: 1200px; }
        .table-wrap { overflow-x: auto; margin-top: 1rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; border-bottom: 1px solid #e2e8f0; text-align: left; }
        th { background: #f1f5f9; }
        .muted { color: #64748b; font-size: 0.95rem; }
        
        /* Layout grid expansion for section input column */
        .form-inline { display: grid; grid-template-columns: repeat(6, minmax(130px, 1fr)) 70px; gap: 10px; align-items: end; }
        @media (max-width: 1100px) {
            .form-inline { grid-template-columns: repeat(2, minmax(140px, 1fr)); }
        }
        .btn-primary {
            background-color: #1e3a8a;
            color: #fff;
            border: none;
            padding: 10px 14px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
        }
        .btn-primary:hover { background-color: #17306d; }
        .alert { padding: 12px 14px; border-radius: 10px; margin-top: 1rem; font-weight: 700; }
        .alert.success { background: #dcfce7; color: #166534; border-left: 5px solid #10b981; }
        .alert.error { background: #fee2e2; color: #991b1b; border-left: 5px solid #ef4444; }
        input, select { width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px; }
        .status-pill { display:inline-block; padding:4px 8px; border-radius:999px; font-size:12px; font-weight:700; text-transform:uppercase; }
        .status-pill.pending { background:#fef9c3; color:#854d0e; }
        .status-pill.approved, .status-pill.regular { background:#dcfce7; color:#166534; }
        .status-pill.irregular { background:#ffedd5; color:#9a3412; }
        .status-pill.dropped { background:#fee2e2; color:#991b1b; }
        .btn-success { background:#16a34a; }
        .btn-danger { background:#ef4444; }
        .action-buttons {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }
        .action-buttons form {
            display: inline-flex;
            margin: 0;
        }
        .action-buttons .btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 74px;
            height: 46px;
            padding: 0 16px;
            line-height: 1;
        }
        .modal-overlay { display:none; position:fixed; inset:0; z-index:999; background:rgba(15,23,42,0.7); align-items:center; justify-content:center; padding:1rem; }
        .modal-box { width:100%; max-width:680px; max-height:90vh; overflow:auto; background:#fff; border-radius:12px; padding:1.5rem; box-shadow:0 25px 80px rgba(0,0,0,0.25); }
        .modal-header { display:flex; align-items:center; justify-content:space-between; gap:1rem; }
        .modal-header h3 { margin:0; }
        .modal-close { width:36px; height:36px; border:none; border-radius:8px; background:#f1f5f9; color:#334155; cursor:pointer; font-size:24px; line-height:1; }
        .modal-close:hover { background:#e2e8f0; }
        .modal-actions { display:flex; justify-content:flex-end; gap:10px; margin-top:1rem; }
        .grade-row { display:grid; grid-template-columns:1fr 140px; gap:10px; align-items:center; padding:10px; border-bottom:1px solid #e2e8f0; }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="nav-brand">Campus Portal (Admin)</div>
    <div class="user-info">
        <span>Welcome, <strong><?php echo htmlspecialchars($me['fullname'] ?? 'Admin'); ?></strong></span>
        <a href="logout.php" class="logout-link">Logout</a>
    </div>
</nav>

<div class="dashboard-container content-wrap">
    <aside class="sidebar">
        <ul>
            <li><a href="#" class="active">Students</a></li>
            <li><a href="#" onclick="alert('Use the Edit button on any student in the table below.')">Update Grades</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <h2>Student Information & Grade Updates</h2>
        <div class="muted">View student details and modify their section assignments or academic standings.</div>

        <?php
            // Status messages from redirects (e.g., delete.php)
            if ($success === null && isset($_GET['status']) && $_GET['status'] === 'student_deleted') {
                $success = 'Student deleted successfully.';
            }
        ?>
        <?php if ($success): ?>
            <div class="alert success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <h3 style="margin-top:1.5rem;">Pending Enrollment Requests</h3>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Email</th>
                        <th>Course</th>
                        <th>Year</th>
                        <th>Section</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$pendingEnrollees): ?>
                        <tr><td colspan="6" class="muted">No pending enrollment requests.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($pendingEnrollees as $p): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($p['fullname']); ?></strong></td>
                            <td><?php echo htmlspecialchars($p['email']); ?></td>
                            <td><?php echo htmlspecialchars($p['course'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars((string)($p['year_level'] ?? '')); ?></td>
                            <td><?php echo htmlspecialchars($p['section'] ?? 'Unassigned'); ?></td>
                            <td>
                                <a class="btn-primary btn-success" href="prof_dashboard.php?action_process_enrollment=approve&student_id=<?php echo (int)$p['id']; ?>">Approve</a>
                                <a class="btn-primary btn-danger" href="prof_dashboard.php?action_process_enrollment=reject&student_id=<?php echo (int)$p['id']; ?>" onclick="return confirm('Reject this enrollment request?')">Reject</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <h3 style="margin-top:2rem;">Student Roster</h3>
        <?php
            // Group students by section for easier viewing.
            $studentsBySection = [];
            foreach ($students as $s) {
                $sec = trim((string)($s['section'] ?? 'Unassigned'));
                if ($sec === '') {
                    $sec = 'Unassigned';
                }
                $studentsBySection[$sec][] = $s;
            }

            $sectionOrder = ['A','B','C','D','E','F','G','H'];
            $sections = array_keys($studentsBySection);
            usort($sections, function($a, $b) use ($sectionOrder) {
                $aIdx = array_search(strtoupper($a), $sectionOrder, true);
                $bIdx = array_search(strtoupper($b), $sectionOrder, true);
                $aRank = ($aIdx === false) ? 999 : $aIdx;
                $bRank = ($bIdx === false) ? 999 : $bIdx;
                if ($aRank === $bRank) {
                    return strcasecmp($a, $b);
                }
                return $aRank <=> $bRank;
            });
        ?>

        <div class="table-wrap">
            <?php if (!$students): ?>
                <table>
                    <tbody>
                        <tr><td colspan="8" class="muted">No students found.</td></tr>
                    </tbody>
                </table>
            <?php else: ?>
                <?php if (!$sections): ?>
                    <table>
                        <tbody>
                            <tr><td colspan="8" class="muted">No students found.</td></tr>
                        </tbody>
                    </table>
                <?php else: ?>
                    <?php foreach ($sections as $sec): ?>
                        <div style="margin-top:1.25rem;">
                            <h4 style="margin:0 0 .5rem 0;">Section: <?php echo htmlspecialchars($sec); ?></h4>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Student ID</th>
                                        <th>Full Name</th>
                                        <th>Course</th>
                                        <th>Section</th>
                                        <th>Year Level</th>
                                        <th>GPA</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($studentsBySection[$sec] as $s): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($s['student_id']); ?></td>
                                            <td><?php echo htmlspecialchars($s['fullname']); ?></td>
                                            <td><?php echo htmlspecialchars($s['course'] ?? ''); ?></td>
                                            <td><strong><?php echo htmlspecialchars($s['section'] ?? 'Unassigned'); ?></strong></td>
                                            <td><?php echo htmlspecialchars((string)($s['year_level'] ?? '')); ?></td>
                                            <td><?php echo htmlspecialchars((string)($s['gpa'] ?? '0.00')); ?></td>
                                            <td><span class="status-pill <?php echo strtolower(htmlspecialchars($s['enrollment_status'] ?? 'Regular')); ?>"><?php echo htmlspecialchars($s['enrollment_status'] ?? 'Regular'); ?></span></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn-primary" type="button" onclick='prefill(
                                                        <?php echo (int)$s['id']; ?>, 
                                                        <?php echo htmlspecialchars(json_encode($s['student_id']), ENT_QUOTES, "UTF-8"); ?>, 
                                                        <?php echo htmlspecialchars(json_encode($s['course'] ?? ""), ENT_QUOTES, "UTF-8"); ?>, 
                                                        <?php echo htmlspecialchars(json_encode($s['section'] ?? ""), ENT_QUOTES, "UTF-8"); ?>, 
                                                        <?php echo htmlspecialchars(json_encode((string)($s['year_level'] ?? "")), ENT_QUOTES, "UTF-8"); ?>, 
                                                        <?php echo htmlspecialchars(json_encode((string)($s['gpa'] ?? "0.00")), ENT_QUOTES, "UTF-8"); ?>
                                                    )'>Edit</button>
                                                    <button class="btn-primary btn-success" type="button" onclick='openEvaluation(
                                                        <?php echo (int)$s['id']; ?>,
                                                        <?php echo htmlspecialchars(json_encode($s['fullname']), ENT_QUOTES, "UTF-8"); ?>,
                                                        <?php echo htmlspecialchars(json_encode($s['section'] ?? ""), ENT_QUOTES, "UTF-8"); ?>,
                                                        <?php echo htmlspecialchars(json_encode($s['enrollment_status'] ?? "Regular"), ENT_QUOTES, "UTF-8"); ?>,
                                                        <?php echo htmlspecialchars(json_encode($s['course'] ?? ""), ENT_QUOTES, "UTF-8"); ?>,
                                                        <?php echo htmlspecialchars(json_encode((string)($s['year_level'] ?? "")), ENT_QUOTES, "UTF-8"); ?>
                                                    )'>Evaluate</button>

                                                    <form method="POST" action="delete.php">
                                                        <input type="hidden" name="student_user_id" value="<?php echo (int)$s['id']; ?>">
                                                        <button class="btn-primary btn-danger" type="submit"
                                                            onclick="return confirm('Delete this student and all related records? This cannot be undone.');">
                                                            Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>


        <h3 style="margin-top:2rem;">Update Selected Student Records</h3>
        <form method="POST" action="prof_dashboard.php" style="margin-top:1rem;">

            <input type="hidden" name="update_grades" value="1">
            <input type="hidden" id="id" name="id" value="0">
            <div class="form-inline">
                <div>
                    <label class="muted">Student ID</label>
                    <input id="student_id" name="student_id" type="text" readonly required style="background-color: #f1f5f9;">
                </div>
                <div>
                    <label class="muted">Course</label>
                    <input id="course" name="course" type="text" required>
                </div>
                <div>
                    <label class="muted">Section</label>
                    <input id="section" name="section" type="text" placeholder="e.g. A">
                </div>
                <div>
                    <label class="muted">Year Level</label>
                    <input id="year_level" name="year_level" type="number" min="1" max="10" required>
                </div>
                <div>
                    <label class="muted">GPA</label>
                    <input id="gpa" name="gpa" type="number" step="0.01" min="0" max="4" required>
                </div>
                <div>
                    <label class="muted"> </label>
                    <div class="muted">(Saves variables into database)</div>
                </div>
                <div>
                    <button class="btn-primary" type="submit" style="width:100%;">Save</button>
                </div>
            </div>
        </form>

        <script>
            function prefill(id, studentId, course, section, yearLevel, gpa) {
                document.getElementById('id').value = id ?? 0;
                document.getElementById('student_id').value = studentId ?? '';
                document.getElementById('course').value = course ?? '';
                document.getElementById('section').value = section ?? '';
                document.getElementById('year_level').value = yearLevel ?? '';
                
                // Clean and structure numerical floating values dynamically
                let cleanGpa = parseFloat(gpa);
                document.getElementById('gpa').value = isNaN(cleanGpa) ? '0.00' : cleanGpa.toFixed(2);
                
                window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
            }

            function openEvaluation(id, fullName, section, status, course, yearLevel) {
                document.getElementById('eval_student_name').textContent = fullName;
                document.getElementById('student_user_id').value = id;
                document.getElementById('academic_status').value = status || 'Regular';

                // Build section codes like BSCS-3A, BSCS-3B...
                const overrideSelect = document.getElementById('override_section');
                overrideSelect.innerHTML = '';

                const courseToCode = {
                    'BS Computer Science': 'BSCS',
                    'BS Information Technology': 'BSIT',
                    'BS Computer Engineering': 'BSCPE',
                    'BS Data Science': 'BSDS',
                    'BS Cybersecurity': 'BSCY',
                    'BS Business Administration': 'BSBA',
                    'BS Accountancy': 'BSACT',
                    'BS Entrepreneurship': 'BSENT',
                    'BS Marketing Management': 'BSMM',
                    'BS Financial Management': 'BSFM',
                    'BS Human Resource Management': 'BSHRM',
                    'BS Office Administration': 'BSOA',
                    'BS Civil Engineering': 'BSCE',
                    'BS Mechanical Engineering': 'BSME',
                    'BS Electrical Engineering': 'BSEE',
                    'BS Electronics Engineering': 'BSECE',
                    'BS Industrial Engineering': 'BSIE',
                    'BS Chemical Engineering': 'BSCHE',
                    'BS Aeronautical Engineering': 'BSAE',
                    'BS Nursing': 'BSN',
                    'BS Medical Technology': 'BSMT',
                    'BS Pharmacy': 'BSPHAR',
                    'BS Physical Therapy': 'BSPT',
                    'BS Psychology': 'BSPSY'
                };

                const courseKey = (course || '').trim();
                const coursePrefix = courseToCode[courseKey] || 'BS';
                const y = parseInt(yearLevel, 10);

                const targetSection = (section || '').trim();
                const letters = ['A', 'B', 'C', 'D'];

                letters.forEach(letter => {
                    const fullCode = `${coursePrefix}-${isNaN(y) ? '' : y}${letter}`;
                    const opt = document.createElement('option');
                    opt.value = fullCode;
                    opt.textContent = fullCode;
                    overrideSelect.appendChild(opt);
                });

                // Prefer exact match to student's current stored section (e.g., BSCS-3A)
                let matched = false;
                for (const opt of overrideSelect.options) {
                    if (opt.value === targetSection) {
                        overrideSelect.value = targetSection;
                        matched = true;
                        break;
                    }
                }
                if (!matched) {
                    // fallback: choose A
                    overrideSelect.value = overrideSelect.options[0]?.value || '';
                }

                document.getElementById('grade-fields').innerHTML = '<p class="muted">Loading approved subjects...</p>';
                document.getElementById('evaluation-modal').style.display = 'flex';

                fetch(`prof_dashboard.php?ajax_fetch_student_subjects=1&student_id=${encodeURIComponent(id)}`)
                    .then(response => response.json())
                    .then(subjects => {
                        const container = document.getElementById('grade-fields');
                        if (!subjects.length) {
                            container.innerHTML = '<p class="muted">No approved subjects found for this student.</p>';
                            return;
                        }

                        container.innerHTML = subjects.map(subject => `
                            <div class="grade-row">
                                <strong>${subject.subject_name}</strong>
                                <input type="number" step="0.01" min="0" max="5" name="grades[${subject.subject_id}]" value="${subject.grade ?? ''}" placeholder="Grade">
                            </div>
                        `).join('');
                    })
                    .catch(() => {
                        document.getElementById('grade-fields').innerHTML = '<p class="muted">Unable to load subjects.</p>';
                    });
            }

            function closeEvaluation() {
                document.getElementById('evaluation-modal').style.display = 'none';
            }
        </script>
    </main>
</div>

<div class="modal-overlay" id="evaluation-modal">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Evaluate Student</h3>
            <button type="button" class="modal-close" onclick="closeEvaluation()" aria-label="Close evaluation">&times;</button>
        </div>
        <p class="muted">Student: <strong id="eval_student_name"></strong></p>
        <form method="POST" action="prof_dashboard.php">
            <input type="hidden" name="action_submit_student_evaluations" value="1">
            <input type="hidden" name="student_user_id" id="student_user_id">

            <div class="form-inline" style="grid-template-columns: repeat(2, minmax(180px, 1fr)); margin-top:1rem;">
                <div>
                    <label class="muted">Section</label>
                    <select name="override_section" id="override_section" required>
                        <!-- options are injected dynamically based on selected student's course/year -->
                    </select>
                </div>
                <div>
                    <label class="muted">Academic Status</label>
                    <select name="academic_status" id="academic_status" required>
                        <option value="Regular">Regular</option>
                        <option value="Irregular">Irregular</option>
                        <option value="Dropped">Dropped</option>
                    </select>
                </div>
            </div>

            <h4 style="margin-top:1rem;">Subject Grades</h4>
            <div id="grade-fields" style="margin-top:0.5rem;"></div>

            <div class="modal-actions">
                <button type="button" class="btn-primary" style="background:#64748b;" onclick="closeEvaluation()">Cancel</button>
                <button type="submit" class="btn-primary btn-success">Save Evaluation</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>
