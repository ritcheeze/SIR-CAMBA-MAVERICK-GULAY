<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require __DIR__ . '/db.php';

$studentId = $_SESSION['user_id'];

if (isset($_GET['ajax_query'])) {
    header('Content-Type: application/json');

    $course = trim($_GET['course'] ?? '');
    $year = isset($_GET['year']) ? (int)$_GET['year'] : 0;
    $semester = isset($_GET['sem']) ? (int)$_GET['sem'] : 0;

    $stmt = $conn->prepare('SELECT id, subject_name FROM curriculum_subjects WHERE course = ? AND year_level = ? AND semester = ? ORDER BY id ASC');
    $stmt->bind_param('sii', $course, $year, $semester);
    $stmt->execute();
    echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    $stmt->close();
    exit;
}

// Load student
$studentStmt = $conn->prepare('SELECT id, student_id, fullname, course, year_level FROM users WHERE id = ?');
$studentStmt->bind_param('i', $studentId);
$studentStmt->execute();
$student = $studentStmt->get_result()->fetch_assoc();
$studentStmt->close();

if (!$student || empty($student['student_id'])) {
    die('Student record missing or student_id not assigned.');
}

$studentCode = $student['student_id'];

$profileStmt = $conn->prepare('SELECT * FROM student_profiles WHERE user_id = ? LIMIT 1');
$profileStmt->bind_param('i', $studentId);
$profileStmt->execute();
$profile = $profileStmt->get_result()->fetch_assoc();
$profileStmt->close();

$enrollmentStatusStmt = $conn->prepare('SELECT COUNT(*) AS total FROM student_enrollments WHERE user_id = ?');
$enrollmentStatusStmt->bind_param('i', $studentId);
$enrollmentStatusStmt->execute();
$hasSubjectEnrollment = ((int)$enrollmentStatusStmt->get_result()->fetch_assoc()['total']) > 0;
$enrollmentStatusStmt->close();

$courses = [
    'BS Information Technology',
    'BS Computer Science',
    'BS Computer Engineering',
    'BS Data Science',
    'BS Cybersecurity',
    'BS Business Administration',
    'BS Accountancy',
    'BS Entrepreneurship',
    'BS Marketing Management',
    'BS Financial Management',
    'BS Human Resource Management',
    'BS Office Administration',
    'BS Civil Engineering',
    'BS Mechanical Engineering',
    'BS Electrical Engineering',
    'BS Electronics Engineering',
    'BS Industrial Engineering',
    'BS Chemical Engineering',
    'BS Aeronautical Engineering',
    'BS Nursing',
    'BS Medical Technology',
    'BS Pharmacy',
    'BS Physical Therapy',
    'BS Psychology',
];

function makeSectionCode(string $course, int $yearLevel): string
{
    $courseCodes = [
        'BS Information Technology' => 'BSIT',
        'BS Computer Science' => 'BSCS',
        'BS Computer Engineering' => 'BSCPE',
        'BS Data Science' => 'BSDS',
        'BS Cybersecurity' => 'BSCY',
        'BS Business Administration' => 'BSBA',
        'BS Accountancy' => 'BSACT',
        'BS Entrepreneurship' => 'BSENT',
        'BS Marketing Management' => 'BSMM',
        'BS Financial Management' => 'BSFM',
        'BS Human Resource Management' => 'BSHRM',
        'BS Office Administration' => 'BSOA',
        'BS Civil Engineering' => 'BSCE',
        'BS Mechanical Engineering' => 'BSME',
        'BS Electrical Engineering' => 'BSEE',
        'BS Electronics Engineering' => 'BSECE',
        'BS Industrial Engineering' => 'BSIE',
        'BS Chemical Engineering' => 'BSCHE',
        'BS Aeronautical Engineering' => 'BSAE',
        'BS Nursing' => 'BSN',
        'BS Medical Technology' => 'BSMT',
        'BS Pharmacy' => 'BSPHAR',
        'BS Physical Therapy' => 'BSPT',
        'BS Psychology' => 'BSPSY',
    ];

    return ($courseCodes[$course] ?? 'BS') . '-' . $yearLevel . 'A';
}

$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course = trim($_POST['course'] ?? '');
    $yearLevel = isset($_POST['year_level']) ? (int)$_POST['year_level'] : 0;
    $semester = isset($_POST['semester']) ? (int)$_POST['semester'] : 0;
    $subjects = isset($_POST['subjects']) && is_array($_POST['subjects']) ? array_map('intval', $_POST['subjects']) : [];

    if (!in_array($course, $courses, true) || $yearLevel < 1 || $yearLevel > 4) {
        $error = 'Please provide a valid course and year level.';
    } elseif (isset($_POST['action_enroll']) && ($semester < 1 || $semester > 2 || !$subjects)) {
        $error = 'Please select a semester and at least one subject.';
    } else {
        // Prevent duplicate enrollment in the same course/year by checking existing enrollments.
        $existingStmt = $conn->prepare('SELECT id FROM enrollments WHERE student_id = ? AND course = ? AND year_level = ? LIMIT 1');
        $existingStmt->bind_param('ssi', $studentCode, $course, $yearLevel);
        $existingStmt->execute();
        $existingExists = $existingStmt->get_result()->num_rows > 0;
        $existingStmt->close();

        if ($existingExists) {
            $error = 'You are already enrolled for that course and year level.';
        } else {
            $sectionCode = makeSectionCode($course, $yearLevel);

            $seedStmt = $conn->prepare(
                'INSERT INTO sections (section_code, course, year_level, capacity, enrolled_count) '
                . 'SELECT ?, ?, ?, 40, 0 '
                . 'WHERE NOT EXISTS (SELECT 1 FROM sections WHERE course = ? AND year_level = ?)'
            );
            $seedStmt->bind_param('ssisi', $sectionCode, $course, $yearLevel, $course, $yearLevel);
            $seedStmt->execute();
            $seedStmt->close();

            // Auto-select: pick the first available section for course/year based on smallest remaining capacity.
            // sections table is created in enrollment_schema.sql (or by you).
            $pickStmt = $conn->prepare(
                'SELECT id, section_code, capacity, enrolled_count '
                . 'FROM sections '
                . 'WHERE course = ? AND year_level = ? AND enrolled_count < capacity '
                . 'ORDER BY (capacity - enrolled_count) ASC, id ASC '
                . 'LIMIT 1'
            );
            $pickStmt->bind_param('si', $course, $yearLevel);
            $pickStmt->execute();
            $section = $pickStmt->get_result()->fetch_assoc();
            $pickStmt->close();

            if (!$section) {
                $error = 'No available sections found for the selected course/year.';
            } else {
                // Transaction to avoid race conditions.
                $conn->begin_transaction();
                try {
                    if (isset($_POST['action_enroll'])) {
                        $firstName = trim($_POST['first_name'] ?? '');
                        $middleName = trim($_POST['middle_name'] ?? '');
                        $lastName = trim($_POST['last_name'] ?? '');
                        $birthDate = trim($_POST['birth_date'] ?? '');
                        $age = isset($_POST['age']) ? (int)$_POST['age'] : 0;
                        $elementary = trim($_POST['graduated_elementary'] ?? '');
                        $jhs = trim($_POST['graduated_jhs'] ?? '');
                        $english = isset($_POST['grade_english']) ? (float)$_POST['grade_english'] : 0;
                        $math = isset($_POST['grade_math']) ? (float)$_POST['grade_math'] : 0;
                        $science = isset($_POST['grade_science']) ? (float)$_POST['grade_science'] : 0;
                        $filipino = isset($_POST['grade_filipino']) ? (float)$_POST['grade_filipino'] : 0;

                        if ($firstName === '' || $lastName === '' || $birthDate === '' || $age < 15 || $elementary === '' || $jhs === '') {
                            throw new RuntimeException('Profile details are incomplete.');
                        }

                        if ($profile) {
                            $profStmt = $conn->prepare(
                                'UPDATE student_profiles SET first_name = ?, middle_name = ?, last_name = ?, birth_date = ?, age = ?, '
                                . 'graduated_elementary = ?, graduated_jhs = ?, grade_english = ?, grade_math = ?, grade_science = ?, grade_filipino = ? '
                                . 'WHERE user_id = ?'
                            );
                            $profStmt->bind_param('ssssissddddi', $firstName, $middleName, $lastName, $birthDate, $age, $elementary, $jhs, $english, $math, $science, $filipino, $studentId);
                        } else {
                            $profStmt = $conn->prepare(
                                'INSERT INTO student_profiles '
                                . '(user_id, first_name, middle_name, last_name, birth_date, age, graduated_elementary, graduated_jhs, grade_english, grade_math, grade_science, grade_filipino) '
                                . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                            );
                            $profStmt->bind_param('issssissdddd', $studentId, $firstName, $middleName, $lastName, $birthDate, $age, $elementary, $jhs, $english, $math, $science, $filipino);
                        }

                        $profStmt->execute();
                        $profStmt->close();
                    }

                    $ins = $conn->prepare('INSERT INTO enrollments (student_id, course, year_level, section_id, section_code) VALUES (?, ?, ?, ?, ?)');
                    // student_id (string), course (string), year_level (int), section_id (int), section_code (string)
                    $ins->bind_param('sssis', $studentCode, $course, $yearLevel, $section['id'], $section['section_code']);
                    $ins->execute();
                    $ins->close();

                    $upd = $conn->prepare('UPDATE sections SET enrolled_count = enrolled_count + 1 WHERE id = ?');
                    $upd->bind_param('i', $section['id']);
                    $upd->execute();
                    $upd->close();

                    $studentUpd = $conn->prepare('UPDATE users SET course = ?, year_level = ?, section = ? WHERE id = ?');
                    $studentUpd->bind_param('sisi', $course, $yearLevel, $section['section_code'], $studentId);
                    $studentUpd->execute();
                    $studentUpd->close();

                    if (isset($_POST['action_enroll'])) {
                        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
                        foreach ($subjects as $subjectId) {
                            $checkSubject = $conn->prepare('SELECT id FROM curriculum_subjects WHERE id = ? AND course = ? AND year_level = ? AND semester = ? LIMIT 1');
                            $checkSubject->bind_param('isii', $subjectId, $course, $yearLevel, $semester);
                            $checkSubject->execute();
                            $validSubject = $checkSubject->get_result()->num_rows === 1;
                            $checkSubject->close();

                            if (!$validSubject) {
                                continue;
                            }

                            $day = $days[array_rand($days)];
                            $startHour = random_int(8, 15);
                            $endHour = $startHour + 2;
                            $schedule = $day . ' ' . $startHour . ':00 - ' . $endHour . ':00';

                            $subjectEnroll = $conn->prepare(
                                'INSERT INTO student_enrollments (user_id, subject_id, semester, year_level, status, schedule_day_time) '
                                . "VALUES (?, ?, ?, ?, 'pending', ?) "
                                . 'ON DUPLICATE KEY UPDATE semester = VALUES(semester), year_level = VALUES(year_level), status = VALUES(status), schedule_day_time = VALUES(schedule_day_time)'
                            );
                            $subjectEnroll->bind_param('iiiis', $studentId, $subjectId, $semester, $yearLevel, $schedule);
                            $subjectEnroll->execute();
                            $subjectEnroll->close();
                        }
                    }

                    $conn->commit();

                    $success = isset($_POST['action_enroll'])
                        ? 'Enrollment submitted successfully. Your subjects are pending admin approval.'
                        : 'Enrolled successfully in section ' . htmlspecialchars($section['section_code']) . '.';
                } catch (Throwable $t) {
                    $conn->rollback();
                    $error = 'Enrollment failed. Please complete all required fields and try again.';
                }
            }
        }
    }
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enrollment</title>
    <link rel="stylesheet" href="dashboard.css">
    <style>
        .form-card { max-width: 900px; margin: 2rem auto; background: #fff; padding: 1.5rem; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.06); }
        .row { display: grid; grid-template-columns: repeat(2, minmax(220px, 1fr)); gap: 1rem; }
        .row.three { grid-template-columns: repeat(3, minmax(160px, 1fr)); }
        .row.four { grid-template-columns: repeat(4, minmax(120px, 1fr)); }
        label { font-weight: 700; color: #334155; }
        input, select { width: 100%; padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 10px; }
        .btn-primary { background-color: #1e3a8a; color: #fff; border: none; padding: 10px 14px; border-radius: 10px; cursor: pointer; font-weight: 700; }
        .alert { padding: 12px 14px; border-radius: 10px; margin-top: 1rem; font-weight: 700; }
        .alert.success { background: #dcfce7; color: #166534; border-left: 5px solid #10b981; }
        .alert.error { background: #fee2e2; color: #991b1b; border-left: 5px solid #ef4444; }
        .section-box { background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:1rem; margin-top:1rem; }
        .subject-item { display:flex; gap:10px; align-items:center; padding:10px; border-bottom:1px solid #e2e8f0; }
        .subject-item:last-child { border-bottom:none; }
        .subject-item input { width:auto; }
        h3 { margin-top: 1.5rem; color:#0f172a; }
        @media (max-width: 720px) { .row, .row.three, .row.four { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<nav class="navbar">
    <div class="nav-brand">Campus Portal</div>
    <div class="user-info">
        <span>Welcome, <strong><?php echo htmlspecialchars($student['fullname'] ?? 'Student'); ?></strong></span>
        <a href="dashboard.php" class="dashboard-link" aria-label="Back to dashboard" title="Back to dashboard">&larr;</a>
        <a href="logout.php" class="logout-link">Logout</a>
    </div>
</nav>

<div class="form-card">
    <div class="page-title-row">
        <h2>Enrollment System</h2>
        <a href="dashboard.php" class="back-dashboard-btn" aria-label="Back to dashboard" title="Back to dashboard">&larr;</a>
    </div>
    <p class="muted">Auto-assigns the first available section based on course and year level.</p>

    <?php if ($success): ?><div class="alert success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <?php if ($hasSubjectEnrollment): ?>
        <div class="alert success">Your subject enrollment request is already in the system. Check your dashboard for status updates.</div>
    <?php endif; ?>

    <form method="POST" action="enrollment.php" style="margin-top:1rem;">
        <input type="hidden" name="action_enroll" value="1">

        <h3>Student Background</h3>
        <div class="row three">
            <div>
                <label>First Name</label>
                <input type="text" name="first_name" required value="<?php echo htmlspecialchars($profile['first_name'] ?? ''); ?>">
            </div>
            <div>
                <label>Middle Name</label>
                <input type="text" name="middle_name" value="<?php echo htmlspecialchars($profile['middle_name'] ?? ''); ?>">
            </div>
            <div>
                <label>Last Name</label>
                <input type="text" name="last_name" required value="<?php echo htmlspecialchars($profile['last_name'] ?? ''); ?>">
            </div>
        </div>

        <div class="row" style="margin-top:1rem;">
            <div>
                <label>Birth Date</label>
                <input type="date" name="birth_date" required value="<?php echo htmlspecialchars($profile['birth_date'] ?? ''); ?>">
            </div>
            <div>
                <label>Age</label>
                <input type="number" name="age" min="15" max="100" required value="<?php echo htmlspecialchars((string)($profile['age'] ?? '')); ?>">
            </div>
        </div>

        <div class="row" style="margin-top:1rem;">
            <div>
                <label>Elementary School Graduated</label>
                <input type="text" name="graduated_elementary" required value="<?php echo htmlspecialchars($profile['graduated_elementary'] ?? ''); ?>">
            </div>
            <div>
                <label>Junior High School Graduated</label>
                <input type="text" name="graduated_jhs" required value="<?php echo htmlspecialchars($profile['graduated_jhs'] ?? ''); ?>">
            </div>
        </div>

        <h3>Grade 12 Scores</h3>
        <div class="row four">
            <div>
                <label>English</label>
                <input type="number" step="0.01" name="grade_english" min="70" max="100" required value="<?php echo htmlspecialchars((string)($profile['grade_english'] ?? '')); ?>">
            </div>
            <div>
                <label>Mathematics</label>
                <input type="number" step="0.01" name="grade_math" min="70" max="100" required value="<?php echo htmlspecialchars((string)($profile['grade_math'] ?? '')); ?>">
            </div>
            <div>
                <label>Science</label>
                <input type="number" step="0.01" name="grade_science" min="70" max="100" required value="<?php echo htmlspecialchars((string)($profile['grade_science'] ?? '')); ?>">
            </div>
            <div>
                <label>Filipino</label>
                <input type="number" step="0.01" name="grade_filipino" min="70" max="100" required value="<?php echo htmlspecialchars((string)($profile['grade_filipino'] ?? '')); ?>">
            </div>
        </div>

        <h3>Program Selection</h3>
        <div class="row">
            <div>
                <label>Course</label>
                <select name="course" id="course_opt" onchange="fetchCurriculum()" required>
                    <option value="">Select course</option>
                    <?php foreach ($courses as $courseOption): ?>
                        <option value="<?php echo htmlspecialchars($courseOption); ?>" <?php echo (($student['course'] ?? '') === $courseOption) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($courseOption); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Year Level</label>
                <select name="year_level" id="year_opt" onchange="fetchCurriculum()" required>
                    <?php for ($year = 1; $year <= 4; $year++): ?>
                        <option value="<?php echo $year; ?>" <?php echo ((int)($student['year_level'] ?? 1) === $year) ? 'selected' : ''; ?>>
                            <?php echo $year; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>  

        <div class="row" style="margin-top:1rem;">
            <div>
                <label>Semester</label>
                <select name="semester" id="sem_opt" onchange="fetchCurriculum()" required>
                    <option value="1">1st Semester</option>
                    <option value="2">2nd Semester</option>
                </select>
            </div>
        </div>

        <div class="section-box">
            <h3 style="margin-top:0;">Available Subjects</h3>
            <div id="curriculum-checklist-box">
                <p class="muted">Select a course, year level, and semester to load available subjects.</p>
            </div>
        </div>

        <button type="submit" class="btn-primary" style="margin-top:1rem; width:100%;">Submit Enrollment</button>
    </form>

    <p class="muted" style="margin-top:1rem;">
        Your student ID: <strong><?php echo htmlspecialchars($studentCode); ?></strong>
    </p>
</div>
<script>
function fetchCurriculum() {
    const course = document.getElementById('course_opt').value;
    const year = document.getElementById('year_opt').value;
    const sem = document.getElementById('sem_opt').value;
    const container = document.getElementById('curriculum-checklist-box');

    if (!course || !year || !sem) {
        container.innerHTML = '<p class="muted">Select a course, year level, and semester to load available subjects.</p>';
        return;
    }

    container.innerHTML = '<p class="muted">Loading subjects...</p>';

    fetch(`enrollment.php?ajax_query=1&course=${encodeURIComponent(course)}&year=${encodeURIComponent(year)}&sem=${encodeURIComponent(sem)}`)
        .then(response => response.json())
        .then(subjects => {
            if (!subjects.length) {
                container.innerHTML = '<p class="muted">No curriculum subjects found for this course/year/semester yet.</p>';
                return;
            }

            container.innerHTML = subjects.map(subject => `
                <label class="subject-item">
                    <input type="checkbox" name="subjects[]" value="${subject.id}" checked>
                    <span>${subject.subject_name}</span>
                </label>
            `).join('');
        })
        .catch(() => {
            container.innerHTML = '<p class="muted">Unable to load subjects. Please try again.</p>';
        });
}

fetchCurriculum();
</script>
</body>
</html>
