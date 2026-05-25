from flask import Flask, render_template, request, redirect, url_for, session, jsonify
from werkzeug.security import check_password_hash
import mysql.connector
import os
import random
import subprocess

app = Flask(__name__)
app.secret_key = os.environ.get("FLASK_SECRET_KEY", "dev-secret-change-me")

COURSES = [
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
]

COURSE_CODES = {
    'BS Information Technology': 'BSIT',
    'BS Computer Science': 'BSCS',
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
    'BS Psychology': 'BSPSY',
}


def get_db():
    # MySQL is expected to run on the same laptop as Flask (XAMPP)
    return mysql.connector.connect(
        host=os.environ.get("DB_HOST", "localhost"),
        user=os.environ.get("DB_USER", "root"),
        password=os.environ.get("DB_PASS", ""),
        database=os.environ.get("DB_NAME", "lspu_portal"),
    )


def require_login():
    if "user_id" not in session:
        return False
    return True


def verify_password(stored_hash, password):
    stored_hash = stored_hash or ""
    if stored_hash.startswith(("$2y$", "$2a$", "$2b$")):
        php_exe = os.environ.get("PHP_EXE", r"C:\xampp\php\php.exe")
        if os.path.exists(php_exe):
            result = subprocess.run(
                [
                    php_exe,
                    "-r",
                    "echo password_verify($argv[1], $argv[2]) ? '1' : '0';",
                    password,
                    stored_hash,
                ],
                capture_output=True,
                text=True,
                timeout=5,
            )
            return result.stdout.strip() == "1"
    try:
        return check_password_hash(stored_hash, password)
    except ValueError:
        return False


def php_password_hash(password):
    php_exe = os.environ.get("PHP_EXE", r"C:\xampp\php\php.exe")
    if os.path.exists(php_exe):
        result = subprocess.run(
            [
                php_exe,
                "-r",
                "echo password_hash($argv[1], PASSWORD_BCRYPT);",
                password,
            ],
            capture_output=True,
            text=True,
            timeout=5,
        )
        hashed = result.stdout.strip()
        if result.returncode == 0 and hashed:
            return hashed

    from werkzeug.security import generate_password_hash
    return generate_password_hash(password)


def make_section_code(course, year_level):
    return f"{COURSE_CODES.get(course, 'BS')}-{year_level}A"


def set_admin_message(message, status="success"):
    session["admin_message"] = message
    session["admin_message_status"] = status


def parse_int(value, default=0):
    try:
        return int(value)
    except (TypeError, ValueError):
        return default


def parse_float(value, default=0.0):
    try:
        return float(value)
    except (TypeError, ValueError):
        return default


@app.route("/index.php", methods=["GET"])
@app.route("/", methods=["GET"])
def home():
    return render_template("index.html")


@app.route("/login.php", methods=["POST"])
@app.route("/login", methods=["POST"])
def login():
    email = request.form.get("email", "").strip()
    password = request.form.get("password", "")
    remember = request.form.get("remember_me") is not None

    conn = get_db()
    cur = conn.cursor(dictionary=True)
    cur.execute("SELECT id, fullname, password, role FROM users WHERE email = %s", (email,))
    user = cur.fetchone()
    cur.close()
    conn.close()

    if not user or not verify_password(user["password"], password):
        return "<script>alert('Incorrect email/password!'); window.history.back();</script>", 400

    session["user_id"] = user["id"]
    session["user_name"] = user.get("fullname")
    session["role"] = str(user.get("role") or "").strip().lower()

    # Flask session expiration is already controlled via secret cookie.
    # 'remember_me' is left as a hook; the simplest implementation is session permanence.
    if remember:
        session.permanent = True

    if session["role"] == "admin":
        return redirect(url_for("admin_dashboard"))
    return redirect(url_for("student_dashboard"))


@app.route("/register.php", methods=["POST"])
@app.route("/register", methods=["POST"])
def register():
    # Keep parity with existing PHP form inputs
    fullname = request.form.get("fullname", "").strip()
    email = request.form.get("email", "").strip()
    password = request.form.get("password", "")
    confirm_password = request.form.get("confirm_password", "")
    role = request.form.get("role", "student").strip().lower()
    role = "admin" if role == "admin" else "student"

    if password != confirm_password:
        return "<script>alert('Passwords do not match!'); window.history.back();</script>", 400

    conn = get_db()
    cur = conn.cursor(dictionary=True)
    cur.execute("SELECT id FROM users WHERE email = %s LIMIT 1", (email,))
    existing = cur.fetchone()
    if existing:
        cur.close()
        conn.close()
        return "<script>alert('Email is already registered!'); window.history.back();</script>", 400

    hashed_password = php_password_hash(password)

    if role == "student":
        # Generate a year-based student_id similar to the PHP logic
        # NOTE: we keep it simple here; collisions are handled by retrying.
        prefix = str(__import__("datetime").datetime.now().year)
        import random

        student_id = None
        while student_id is None:
            candidate = f"{prefix}-{random.randint(0, 999999):06d}"
            cur.execute("SELECT id FROM users WHERE student_id = %s LIMIT 1", (candidate,))
            if not cur.fetchone():
                student_id = candidate

        cur.execute(
            "INSERT INTO users (fullname, email, password, role, student_id) VALUES (%s,%s,%s,%s,%s)",
            (fullname, email, hashed_password, role, student_id),
        )
    else:
        cur.execute(
            "INSERT INTO users (fullname, email, password, role) VALUES (%s,%s,%s,%s)",
            (fullname, email, hashed_password, role),
        )

    conn.commit()
    cur.close()
    conn.close()

    return redirect(url_for("home"))


@app.route("/forgot_password.php", methods=["GET", "POST"])
@app.route("/forgot_password", methods=["GET", "POST"])
def forgot_password():
    message = ""
    status = ""

    if request.method == "POST":
        email = request.form.get("email", "").strip()
        new_password = request.form.get("new_password", "").strip()

        if not email or not new_password:
            message = "Please complete both fields."
            status = "error"
        elif "@" not in email or "." not in email.rsplit("@", 1)[-1]:
            message = "Please enter a valid email address."
            status = "error"
        elif len(new_password) < 6:
            message = "Password must be at least 6 characters."
            status = "error"
        else:
            conn = get_db()
            cur = conn.cursor(dictionary=True)
            cur.execute("SELECT id FROM users WHERE email = %s LIMIT 1", (email,))
            user = cur.fetchone()

            if user:
                hashed_password = php_password_hash(new_password)
                cur.execute(
                    "UPDATE users SET password = %s WHERE email = %s",
                    (hashed_password, email),
                )
                conn.commit()
                message = "Password updated successfully. Please sign in."
                status = "success"
            else:
                message = "No account found with that email."
                status = "error"

            cur.close()
            conn.close()

    return render_template("forgot_password.html", message=message, status=status)


@app.route("/logout.php", methods=["GET"])
@app.route("/logout", methods=["GET"])
def logout():
    session.clear()
    return redirect(url_for("home"))


@app.route("/student_dashboard", methods=["GET"])
@app.route("/dashboard.php", methods=["GET"])
@app.route("/dashboard", methods=["GET"])
def student_dashboard():
    if not require_login():
        return redirect(url_for("home"))

    if request.args.get("drop_subject_id"):
        return redirect(url_for("drop_subject", subject_id=request.args.get("drop_subject_id")))

    user_id = session["user_id"]
    conn = get_db()
    cur = conn.cursor(dictionary=True)

    cur.execute(
        "SELECT id, student_id, fullname, course, year_level, section, gpa, enrollment_status FROM users WHERE id = %s",
        (user_id,),
    )
    student = cur.fetchone() or {}

    cur.execute(
        "SELECT COUNT(*) AS total FROM student_enrollments WHERE user_id = %s",
        (user_id,),
    )
    pending_count = (cur.fetchone() or {}).get("total", 0)

    cur.execute(
        """
        SELECT se.status, se.schedule_day_time, cs.id AS subject_id, cs.subject_name
        FROM student_enrollments se
        JOIN curriculum_subjects cs ON se.subject_id = cs.id
        WHERE se.user_id = %s
        ORDER BY cs.subject_name ASC
        """,
        (user_id,),
    )
    enrolled_subjects = cur.fetchall()

    cur.execute(
        """
        SELECT cs.subject_name, sg.grade
        FROM student_grades sg
        JOIN student_enrollments se
          ON se.user_id = sg.user_id
         AND se.subject_id = sg.subject_id
        JOIN curriculum_subjects cs ON sg.subject_id = cs.id
        WHERE sg.user_id = %s
        ORDER BY cs.subject_name ASC
        """,
        (user_id,),
    )
    grades = cur.fetchall()

    cur.close()
    conn.close()

    return render_template(
        "dashboard.html",
        student=student,
        pending_count=pending_count,
        enrolled_subjects=enrolled_subjects,
        grades=grades,
        system_message=session.pop("system_message", ""),
    )


@app.route("/dashboard/drop_subject", methods=["GET"])
def drop_subject():
    if not require_login():
        return redirect(url_for("home"))

    user_id = session["user_id"]
    subject_id = parse_int(request.args.get("subject_id"))

    conn = get_db()
    cur = conn.cursor(dictionary=True)
    try:
        cur.execute(
            """
            SELECT cs.subject_name
            FROM student_enrollments se
            JOIN curriculum_subjects cs ON se.subject_id = cs.id
            WHERE se.user_id = %s AND se.subject_id = %s
            LIMIT 1
            """,
            (user_id, subject_id),
        )
        dropped_subject = cur.fetchone()

        cur.execute(
            "DELETE FROM student_enrollments WHERE user_id = %s AND subject_id = %s",
            (user_id, subject_id),
        )
        cur.execute(
            "DELETE FROM student_grades WHERE user_id = %s AND subject_id = %s",
            (user_id, subject_id),
        )
        recalculate_gpa(cur, user_id)
        conn.commit()
        if dropped_subject:
            session["system_message"] = f"{dropped_subject['subject_name']} has been dropped. Your GPA now uses your remaining enrolled subjects."
    except Exception:
        conn.rollback()
        session["system_message"] = "Unable to drop subject. Please try again."
    finally:
        cur.close()
        conn.close()

    return redirect(url_for("student_dashboard"))


@app.route("/account_settings", methods=["GET", "POST"])
def account_settings():
    if not require_login():
        return redirect(url_for("home"))

    user_id = session["user_id"]
    message = "Account information is locked. Contact an administrator to request changes." if request.method == "POST" else ""
    status = "error" if request.method == "POST" else "success"

    conn = get_db()
    cur = conn.cursor(dictionary=True)

    cur.execute(
        """
        SELECT id, student_id, fullname, course, year_level, section, enrollment_status
        FROM users
        WHERE id = %s
        """,
        (user_id,),
    )
    student = cur.fetchone() or {}

    cur.execute("SELECT * FROM student_profiles WHERE user_id = %s LIMIT 1", (user_id,))
    profile = cur.fetchone() or {}

    cur.execute(
        """
        SELECT se.semester, cs.subject_name
        FROM student_enrollments se
        JOIN curriculum_subjects cs ON se.subject_id = cs.id
        WHERE se.user_id = %s
        ORDER BY se.semester ASC, cs.subject_name ASC
        """,
        (user_id,),
    )
    enrolled_subjects = cur.fetchall()
    semesters = sorted({row.get("semester") for row in enrolled_subjects if row.get("semester")})
    semester_labels = {
        1: "1st Semester",
        2: "2nd Semester",
    }
    semester_display = ", ".join(semester_labels.get(sem, f"Semester {sem}") for sem in semesters) or "N/A"

    cur.close()
    conn.close()

    return render_template(
        "account_settings.html",
        student=student,
        profile=profile,
        enrolled_subjects=enrolled_subjects,
        semester_display=semester_display,
        message=message,
        status=status,
    )


def require_admin():
    if not require_login():
        return False
    return str(session.get("role") or "").strip().lower() == "admin"


def recalculate_gpa(cur, user_id):
    cur.execute(
        """
        UPDATE users u
        SET u.gpa = (
            SELECT IFNULL(AVG(sg.grade), 0)
            FROM student_grades sg
            JOIN student_enrollments se
              ON se.user_id = sg.user_id
             AND se.subject_id = sg.subject_id
            WHERE sg.user_id = u.id
              AND sg.grade IS NOT NULL
        )
        WHERE u.id = %s
        """,
        (user_id,),
    )


@app.route("/prof_dashboard/student_subjects", methods=["GET"])
def admin_student_subjects():
    if not require_admin():
        return jsonify([]), 403

    student_id = parse_int(request.args.get("student_id"))
    conn = get_db()
    cur = conn.cursor(dictionary=True)
    cur.execute(
        """
        SELECT cs.id AS subject_id, cs.subject_name, sg.grade
        FROM student_enrollments se
        JOIN curriculum_subjects cs ON se.subject_id = cs.id
        LEFT JOIN student_grades sg
          ON sg.subject_id = cs.id
         AND sg.user_id = se.user_id
        WHERE se.user_id = %s
          AND se.status = 'approved'
        ORDER BY cs.subject_name ASC
        """,
        (student_id,),
    )
    rows = cur.fetchall()
    cur.close()
    conn.close()
    return jsonify(rows)


@app.route("/prof_dashboard/process_enrollment", methods=["GET"])
def admin_process_enrollment():
    if not require_admin():
        return redirect(url_for("home"))

    action = request.args.get("action", "")
    student_id = parse_int(request.args.get("student_id"))
    conn = get_db()
    cur = conn.cursor(dictionary=True)

    if action == "approve":
        cur.execute("UPDATE student_enrollments SET status = 'approved' WHERE user_id = %s", (student_id,))
        conn.commit()
        set_admin_message("Enrollment approved.")
    elif action == "reject":
        cur.execute("DELETE FROM student_enrollments WHERE user_id = %s", (student_id,))
        conn.commit()
        set_admin_message("Enrollment request rejected.")
    else:
        set_admin_message("Invalid enrollment action.", "error")

    cur.close()
    conn.close()
    return redirect(url_for("admin_dashboard"))


@app.route("/delete.php", methods=["POST"])
@app.route("/prof_dashboard/delete_student", methods=["POST"])
def admin_delete_student():
    if not require_admin():
        return redirect(url_for("home"))

    student_id = parse_int(request.form.get("student_user_id"))
    if student_id <= 0:
        set_admin_message("Invalid student id.", "error")
        return redirect(url_for("admin_dashboard"))

    conn = get_db()
    cur = conn.cursor(dictionary=True)
    try:
        cur.execute("DELETE FROM student_grades WHERE user_id = %s", (student_id,))
        cur.execute("DELETE FROM student_enrollments WHERE user_id = %s", (student_id,))
        cur.execute("DELETE FROM student_profiles WHERE user_id = %s", (student_id,))
        cur.execute("DELETE FROM users WHERE id = %s", (student_id,))
        conn.commit()
        set_admin_message("Student deleted successfully.")
    except Exception as exc:
        conn.rollback()
        set_admin_message(f"Delete failed: {exc}", "error")
    finally:
        cur.close()
        conn.close()

    return redirect(url_for("admin_dashboard"))


@app.route("/prof_dashboard/update_student", methods=["POST"])
def admin_update_student():
    if not require_admin():
        return redirect(url_for("home"))

    target_id = parse_int(request.form.get("id"))
    student_code = request.form.get("student_id", "").strip()
    course = request.form.get("course", "").strip()
    section = request.form.get("section", "").strip()
    year_level = parse_int(request.form.get("year_level"))
    gpa = parse_float(request.form.get("gpa"), -1)

    if target_id <= 0 or not student_code or not course or year_level < 1 or year_level > 10 or gpa < 0 or gpa > 4:
        set_admin_message("Please select a student and provide valid student record details.", "error")
        return redirect(url_for("admin_dashboard"))

    conn = get_db()
    cur = conn.cursor(dictionary=True)
    try:
        cur.execute(
            """
            UPDATE users
               SET course = %s, section = %s, year_level = %s, gpa = %s
             WHERE id = %s AND student_id = %s
            """,
            (course, section, year_level, gpa, target_id, student_code),
        )
        conn.commit()
        set_admin_message("Student record updated successfully.")
    except Exception as exc:
        conn.rollback()
        set_admin_message(f"Student record update failed: {exc}", "error")
    finally:
        cur.close()
        conn.close()
    return redirect(url_for("admin_dashboard"))


@app.route("/prof_dashboard/evaluate", methods=["POST"])
def admin_evaluate_student():
    if not require_admin():
        return redirect(url_for("home"))

    target_id = parse_int(request.form.get("student_user_id"))
    section = request.form.get("override_section", "").strip()
    status = request.form.get("academic_status", "Regular").strip()
    if status not in {"Regular", "Irregular", "Dropped"}:
        status = "Regular"
    if target_id <= 0 or not section:
        set_admin_message("Please select a valid student, section, and status.", "error")
        return redirect(url_for("admin_dashboard"))

    conn = get_db()
    cur = conn.cursor(dictionary=True)
    try:
        cur.execute(
            "UPDATE users SET section = %s, enrollment_status = %s WHERE id = %s",
            (section, status, target_id),
        )

        for key, value in request.form.items():
            if not key.startswith("grades[") or not key.endswith("]") or value == "":
                continue
            subject_id = parse_int(key[7:-1])
            grade = parse_float(value, -1)
            if subject_id <= 0 or grade < 0 or grade > 5:
                continue
            cur.execute(
                """
                INSERT INTO student_grades (user_id, subject_id, grade)
                VALUES (%s, %s, %s)
                ON DUPLICATE KEY UPDATE grade = VALUES(grade)
                """,
                (target_id, subject_id, grade),
            )

        recalculate_gpa(cur, target_id)
        conn.commit()
        set_admin_message("Student evaluation saved successfully. GPA updated.")
    except Exception as exc:
        conn.rollback()
        set_admin_message(f"Student evaluation failed: {exc}", "error")
    finally:
        cur.close()
        conn.close()

    return redirect(url_for("admin_dashboard"))


@app.route("/prof_dashboard/curriculum", methods=["POST"])
def admin_curriculum_subject():
    if not require_admin():
        return redirect(url_for("home"))

    action = request.form.get("curriculum_subject_action", "").strip()
    course = request.form.get("subject_course", "").strip()
    year_level = parse_int(request.form.get("subject_year_level"))
    semester = parse_int(request.form.get("subject_semester"))
    subject_name = request.form.get("subject_name", "").strip()
    subject_id = parse_int(request.form.get("curriculum_subject_id"))

    conn = get_db()
    cur = conn.cursor(dictionary=True)
    try:
        if action == "add":
            if course not in COURSES or year_level < 1 or year_level > 4 or semester < 1 or semester > 2 or not subject_name:
                set_admin_message("Please provide valid curriculum subject details.", "error")
            else:
                cur.execute(
                    """
                    INSERT INTO curriculum_subjects (course, year_level, semester, subject_name)
                    VALUES (%s, %s, %s, %s)
                    ON DUPLICATE KEY UPDATE subject_name = VALUES(subject_name)
                    """,
                    (course, year_level, semester, subject_name),
                )
                conn.commit()
                set_admin_message("Curriculum subject saved.")
        elif action == "delete":
            if subject_id <= 0:
                set_admin_message("Invalid curriculum subject id.", "error")
            else:
                cur.execute("DELETE FROM curriculum_subjects WHERE id = %s", (subject_id,))
                conn.commit()
                set_admin_message("Curriculum subject deleted.")
        else:
            set_admin_message("Invalid curriculum action.", "error")
    except Exception as exc:
        conn.rollback()
        set_admin_message(f"Curriculum update failed: {exc}", "error")
    finally:
        cur.close()
        conn.close()

    return redirect(url_for("admin_dashboard") + "#curriculum")


@app.route("/prof_dashboard.php", methods=["POST"])
def admin_legacy_post():
    if request.form.get("curriculum_subject_action"):
        return admin_curriculum_subject()
    if request.form.get("update_grades"):
        return admin_update_student()
    if request.form.get("action_submit_student_evaluations"):
        return admin_evaluate_student()

    set_admin_message("Invalid admin dashboard request.", "error")
    return redirect(url_for("admin_dashboard"))


@app.route("/admin_dashboard", methods=["GET"])
@app.route("/prof_dashboard.php", methods=["GET"])
@app.route("/prof_dashboard", methods=["GET"])
def admin_dashboard():
    if not require_admin():
        return redirect(url_for("home"))

    if request.args.get("ajax_fetch_student_subjects"):
        return admin_student_subjects()
    if request.args.get("action_process_enrollment"):
        return redirect(
            url_for(
                "admin_process_enrollment",
                action=request.args.get("action_process_enrollment"),
                student_id=request.args.get("student_id"),
            )
        )

    conn = get_db()
    cur = conn.cursor(dictionary=True)

    cur.execute("SELECT fullname FROM users WHERE id = %s", (session["user_id"],))
    admin = cur.fetchone() or {}

    cur.execute(
        """
        SELECT DISTINCT u.id, u.fullname, u.email, u.course, u.year_level, u.section
        FROM users u
        JOIN student_enrollments se ON u.id = se.user_id
        WHERE se.status = 'pending'
        ORDER BY u.fullname ASC
        """
    )
    pending_enrollees = cur.fetchall()

    cur.execute(
        """
        SELECT id, fullname, student_id, course, section, year_level, gpa, enrollment_status
        FROM users
        WHERE student_id IS NOT NULL
          AND (role IS NULL OR LOWER(TRIM(role)) != 'admin')
        ORDER BY section ASC, fullname ASC
        """
    )
    students = cur.fetchall()

    cur.execute(
        """
        SELECT id, course, year_level, semester, subject_name
        FROM curriculum_subjects
        ORDER BY course ASC, year_level ASC, semester ASC, subject_name ASC
        """
    )
    curriculum_subjects = cur.fetchall()

    cur.close()
    conn.close()

    students_by_section = {}
    for student in students:
        section = (student.get("section") or "Unassigned").strip() or "Unassigned"
        students_by_section.setdefault(section, []).append(student)

    return render_template(
        "prof_dashboard.html",
        admin=admin,
        pending_enrollees=pending_enrollees,
        students_by_section=students_by_section,
        curriculum_subjects=curriculum_subjects,
        courses=COURSES,
        admin_message=session.pop("admin_message", ""),
        admin_message_status=session.pop("admin_message_status", "success"),
    )


@app.route("/enrollment.php", methods=["GET", "POST"])
@app.route("/enrollment", methods=["GET", "POST"])
def enrollment():
    if not require_login():
        return redirect(url_for("home"))

    user_id = session["user_id"]
    conn = get_db()

    if request.method == "GET" and request.args.get("ajax_query") == "1":
        course = (request.args.get("course") or "").strip()
        year = parse_int(request.args.get("year"))
        sem = parse_int(request.args.get("sem"))

        cur = conn.cursor(dictionary=True)
        cur.execute(
            "SELECT id, subject_name FROM curriculum_subjects WHERE course = %s AND year_level = %s AND semester = %s ORDER BY id ASC",
            (course, year, sem),
        )
        rows = cur.fetchall()
        cur.close()
        conn.close()
        return jsonify(rows)

    cur = conn.cursor(dictionary=True)
    cur.execute("SELECT id, student_id, fullname, course, year_level FROM users WHERE id = %s", (user_id,))
    student = cur.fetchone()

    if not student or not student.get("student_id"):
        cur.close()
        conn.close()
        return "Student record missing or student_id not assigned.", 400

    cur.execute("SELECT * FROM student_profiles WHERE user_id = %s LIMIT 1", (user_id,))
    profile = cur.fetchone() or {}

    success = None
    error = None

    if request.method == "POST":
        course = request.form.get("course", "").strip()
        year_level = parse_int(request.form.get("year_level"))
        semester = parse_int(request.form.get("semester"))
        subjects = request.form.getlist("subjects[]") or request.form.getlist("subjects")
        subject_ids = [parse_int(subject_id) for subject_id in subjects if str(subject_id).isdigit()]

        if course not in COURSES or year_level < 1 or year_level > 4:
            error = "Please provide a valid course and year level."
        elif semester < 1 or semester > 2 or not subject_ids:
            error = "Please select a semester and at least one subject."
        else:
            try:
                cur.execute(
                    "SELECT id FROM enrollments WHERE student_id = %s AND course = %s AND year_level = %s LIMIT 1",
                    (student["student_id"], course, year_level),
                )
                existing = cur.fetchone()

                if existing:
                    error = "You are already enrolled for that course and year level."
                else:
                    first_name = request.form.get("first_name", "").strip()
                    middle_name = request.form.get("middle_name", "").strip()
                    last_name = request.form.get("last_name", "").strip()
                    birth_date = request.form.get("birth_date", "").strip()
                    age = parse_int(request.form.get("age"))
                    elementary = request.form.get("graduated_elementary", "").strip()
                    jhs = request.form.get("graduated_jhs", "").strip()
                    english = parse_float(request.form.get("grade_english"))
                    math = parse_float(request.form.get("grade_math"))
                    science = parse_float(request.form.get("grade_science"))
                    filipino = parse_float(request.form.get("grade_filipino"))

                    if not first_name or not last_name or not birth_date or age < 15 or not elementary or not jhs:
                        raise ValueError("Profile details are incomplete.")

                    section_code = make_section_code(course, year_level)

                    cur.execute(
                        """
                        INSERT INTO sections (section_code, course, year_level, capacity, enrolled_count)
                        SELECT %s, %s, %s, 40, 0
                        WHERE NOT EXISTS (
                            SELECT 1 FROM sections WHERE course = %s AND year_level = %s
                        )
                        """,
                        (section_code, course, year_level, course, year_level),
                    )

                    cur.execute(
                        """
                        SELECT id, section_code, capacity, enrolled_count
                        FROM sections
                        WHERE course = %s
                          AND year_level = %s
                          AND enrolled_count < capacity
                        ORDER BY (capacity - enrolled_count) ASC, id ASC
                        LIMIT 1
                        """,
                        (course, year_level),
                    )
                    section = cur.fetchone()
                    if not section:
                        raise ValueError("No available sections found for the selected course/year.")

                    if profile:
                        cur.execute(
                            """
                            UPDATE student_profiles
                               SET first_name = %s, middle_name = %s, last_name = %s,
                                   birth_date = %s, age = %s,
                                   graduated_elementary = %s, graduated_jhs = %s,
                                   grade_english = %s, grade_math = %s,
                                   grade_science = %s, grade_filipino = %s
                             WHERE user_id = %s
                            """,
                            (
                                first_name,
                                middle_name,
                                last_name,
                                birth_date,
                                age,
                                elementary,
                                jhs,
                                english,
                                math,
                                science,
                                filipino,
                                user_id,
                            ),
                        )
                    else:
                        cur.execute(
                            """
                            INSERT INTO student_profiles
                            (user_id, first_name, middle_name, last_name, birth_date, age,
                             graduated_elementary, graduated_jhs, grade_english, grade_math,
                             grade_science, grade_filipino)
                            VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
                            """,
                            (
                                user_id,
                                first_name,
                                middle_name,
                                last_name,
                                birth_date,
                                age,
                                elementary,
                                jhs,
                                english,
                                math,
                                science,
                                filipino,
                            ),
                        )

                    cur.execute(
                        """
                        INSERT INTO enrollments (student_id, course, year_level, section_id, section_code)
                        VALUES (%s, %s, %s, %s, %s)
                        """,
                        (student["student_id"], course, year_level, section["id"], section["section_code"]),
                    )

                    cur.execute(
                        "UPDATE sections SET enrolled_count = enrolled_count + 1 WHERE id = %s",
                        (section["id"],),
                    )

                    cur.execute(
                        "UPDATE users SET course = %s, year_level = %s, section = %s WHERE id = %s",
                        (course, year_level, section["section_code"], user_id),
                    )

                    days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"]
                    enrolled_subject_names = []
                    for subject_id in subject_ids:
                        cur.execute(
                            """
                            SELECT id, subject_name
                            FROM curriculum_subjects
                            WHERE id = %s
                              AND course = %s
                              AND year_level = %s
                              AND semester = %s
                            LIMIT 1
                            """,
                            (subject_id, course, year_level, semester),
                        )
                        subject = cur.fetchone()
                        if not subject:
                            continue

                        start_hour = random.randint(8, 15)
                        schedule = f"{random.choice(days)} {start_hour}:00 - {start_hour + 2}:00"
                        cur.execute(
                            """
                            INSERT INTO student_enrollments
                            (user_id, subject_id, semester, year_level, status, schedule_day_time)
                            VALUES (%s, %s, %s, %s, 'pending', %s)
                            ON DUPLICATE KEY UPDATE
                                semester = VALUES(semester),
                                year_level = VALUES(year_level),
                                status = VALUES(status),
                                schedule_day_time = VALUES(schedule_day_time)
                            """,
                            (user_id, subject_id, semester, year_level, schedule),
                        )
                        enrolled_subject_names.append(subject["subject_name"])

                    conn.commit()
                    success = "Enrollment submitted successfully. Your subjects are pending admin approval."
                    if enrolled_subject_names:
                        if len(enrolled_subject_names) == 1:
                            session["system_message"] = f"{enrolled_subject_names[0]} has been added to your enrollment."
                        else:
                            session["system_message"] = f"{len(enrolled_subject_names)} subjects have been added to your enrollment."

                    cur.execute("SELECT id, student_id, fullname, course, year_level FROM users WHERE id = %s", (user_id,))
                    student = cur.fetchone()
                    cur.execute("SELECT * FROM student_profiles WHERE user_id = %s LIMIT 1", (user_id,))
                    profile = cur.fetchone() or {}
            except Exception:
                conn.rollback()
                error = error or "Enrollment failed. Please complete all required fields and try again."

    cur.execute(
        "SELECT COUNT(*) AS total FROM student_enrollments WHERE user_id = %s",
        (user_id,),
    )
    row = cur.fetchone()
    # Safely extract total from the dictionary row, default to 0 if None
    has_subject_enrollment = (row.get("total", 0) if row else 0) > 0

    cur.close()
    conn.close()

    return render_template(
        "enrollment.html",
        student=student,
        profile=profile,
        has_subject_enrollment=has_subject_enrollment,
        courses=COURSES,
        success=success,
        error=error,
    )


if __name__ == "__main__":
    # Bind to all interfaces so the phone can access via laptop LAN IP.
    # Example: http://192.168.1.10:5000
    app.run(host="0.0.0.0", port=int(os.environ.get("PORT", "5000")), debug=True)
