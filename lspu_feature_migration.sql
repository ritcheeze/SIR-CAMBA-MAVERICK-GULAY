USE `lspu_portal`;

ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `enrollment_status` ENUM('Regular', 'Irregular', 'Dropped') DEFAULT 'Regular';

CREATE TABLE IF NOT EXISTS `student_profiles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `first_name` VARCHAR(100) NOT NULL,
    `middle_name` VARCHAR(100) DEFAULT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `birth_date` DATE NOT NULL,
    `age` INT NOT NULL,
    `graduated_elementary` VARCHAR(255) NOT NULL,
    `graduated_jhs` VARCHAR(255) NOT NULL,
    `grade_english` DECIMAL(5,2) NOT NULL,
    `grade_math` DECIMAL(5,2) NOT NULL,
    `grade_science` DECIMAL(5,2) NOT NULL,
    `grade_filipino` DECIMAL(5,2) NOT NULL,
    UNIQUE KEY `student_profiles_user` (`user_id`),
    CONSTRAINT `student_profiles_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `curriculum_subjects` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,    
    `course` VARCHAR(100) NOT NULL,
    `year_level` INT NOT NULL,
    `semester` INT NOT NULL,
    `subject_name` VARCHAR(150) NOT NULL,
    UNIQUE KEY `curriculum_subject_unique` (`course`, `year_level`, `semester`, `subject_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `student_enrollments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `subject_id` INT NOT NULL,
    `semester` INT NOT NULL,
    `year_level` INT NOT NULL,
    `status` ENUM('pending', 'approved') DEFAULT 'pending',
    `schedule_day_time` VARCHAR(100) NOT NULL,
    UNIQUE KEY `student_subject_enrollment` (`user_id`, `subject_id`),
    CONSTRAINT `student_enrollments_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `student_enrollments_subject_fk` FOREIGN KEY (`subject_id`) REFERENCES `curriculum_subjects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `student_grades` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `subject_id` INT NOT NULL,
    `grade` DECIMAL(4,2) DEFAULT NULL,
    UNIQUE KEY `user_subject` (`user_id`, `subject_id`),
    CONSTRAINT `student_grades_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `student_grades_subject_fk` FOREIGN KEY (`subject_id`) REFERENCES `curriculum_subjects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Curriculum seeding updated to provided curriculum.
-- Delete existing rows for affected courses, then insert corrected curriculum lists.
DELETE FROM `curriculum_subjects` WHERE `course` IN (
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
  'BS Psychology'
);

INSERT INTO `curriculum_subjects` (`course`, `year_level`, `semester`, `subject_name`) VALUES

-- =========================
-- BS Information Technology
-- =========================
('BS Information Technology', 1, 1, 'Purposive Communication'),
('BS Information Technology', 1, 1, 'Understanding the Self'),
('BS Information Technology', 1, 1, 'Mathematics in the Modern World'),
('BS Information Technology', 1, 1, 'Introduction to Computing'),
('BS Information Technology', 1, 1, 'Computer Programming 1'),
('BS Information Technology', 1, 1, 'NSTP 1'),
('BS Information Technology', 1, 1, 'PE 1'),

('BS Information Technology', 1, 2, 'Readings in Philippine History'),
('BS Information Technology', 1, 2, 'Science, Technology and Society'),
('BS Information Technology', 1, 2, 'Computer Programming 2'),
('BS Information Technology', 1, 2, 'Discrete Mathematics'),
('BS Information Technology', 1, 2, 'Human-Computer Interaction'),
('BS Information Technology', 1, 2, 'NSTP 2'),
('BS Information Technology', 1, 2, 'PE 2'),

('BS Information Technology', 2, 1, 'Data Structures and Algorithms'),
('BS Information Technology', 2, 1, 'Object-Oriented Programming'),
('BS Information Technology', 2, 1, 'Networking 1'),
('BS Information Technology', 2, 1, 'Information Management'),
('BS Information Technology', 2, 1, 'Web Systems and Technologies'),
('BS Information Technology', 2, 1, 'PE 3'),

('BS Information Technology', 2, 2, 'Database Management Systems'),
('BS Information Technology', 2, 2, 'Systems Analysis and Design'),
('BS Information Technology', 2, 2, 'Networking 2'),
('BS Information Technology', 2, 2, 'Integrative Programming'),
('BS Information Technology', 2, 2, 'Information Assurance and Security'),
('BS Information Technology', 2, 2, 'PE 4'),

('BS Information Technology', 3, 1, 'Operating Systems'),
('BS Information Technology', 3, 1, 'Application Development'),
('BS Information Technology', 3, 1, 'Platform Technologies'),
('BS Information Technology', 3, 1, 'IT Elective 1'),
('BS Information Technology', 3, 1, 'Quantitative Methods'),

('BS Information Technology', 3, 2, 'Capstone Project 1'),
('BS Information Technology', 3, 2, 'Systems Integration and Architecture'),
('BS Information Technology', 3, 2, 'IT Elective 2'),
('BS Information Technology', 3, 2, 'Emerging Technologies'),
('BS Information Technology', 3, 2, 'Professional Issues in IT'),

('BS Information Technology', 4, 1, 'Capstone Project 2'),
('BS Information Technology', 4, 1, 'Practicum/OJT'),
('BS Information Technology', 4, 1, 'IT Elective 3'),

('BS Information Technology', 4, 2, 'Seminar and Research'),
('BS Information Technology', 4, 2, 'Advanced IT Elective'),
('BS Information Technology', 4, 2, 'Industry-Based Project'),

-- =========================
-- BS Computer Science
-- =========================
('BS Computer Science', 1, 1, 'Introduction to Computing'),
('BS Computer Science', 1, 1, 'Programming Fundamentals'),
('BS Computer Science', 1, 1, 'Calculus 1'),
('BS Computer Science', 1, 1, 'Mathematics in the Modern World'),
('BS Computer Science', 1, 1, 'NSTP 1'),
('BS Computer Science', 1, 1, 'PE 1'),

('BS Computer Science', 1, 2, 'Object-Oriented Programming'),
('BS Computer Science', 1, 2, 'Discrete Structures'),
('BS Computer Science', 1, 2, 'Calculus 2'),
('BS Computer Science', 1, 2, 'Readings in Philippine History'),
('BS Computer Science', 1, 2, 'NSTP 2'),
('BS Computer Science', 1, 2, 'PE 2'),

('BS Computer Science', 2, 1, 'Data Structures and Algorithms'),
('BS Computer Science', 2, 1, 'Computer Organization'),
('BS Computer Science', 2, 1, 'Statistics and Probability'),
('BS Computer Science', 2, 1, 'Logic Design'),
('BS Computer Science', 2, 1, 'PE 3'),

('BS Computer Science', 2, 2, 'Design and Analysis of Algorithms'),
('BS Computer Science', 2, 2, 'Database Systems'),
('BS Computer Science', 2, 2, 'Operating Systems'),
('BS Computer Science', 2, 2, 'Software Engineering'),
('BS Computer Science', 2, 2, 'PE 4'),

('BS Computer Science', 3, 1, 'Programming Languages'),
('BS Computer Science', 3, 1, 'Artificial Intelligence'),
('BS Computer Science', 3, 1, 'Computer Networks'),
('BS Computer Science', 3, 1, 'CS Elective 1'),
('BS Computer Science', 3, 1, 'Research Methods'),

('BS Computer Science', 3, 2, 'Machine Learning'),
('BS Computer Science', 3, 2, 'Information Security'),
('BS Computer Science', 3, 2, 'Human-Computer Interaction'),
('BS Computer Science', 3, 2, 'CS Elective 2'),
('BS Computer Science', 3, 2, 'Thesis 1'),

('BS Computer Science', 4, 1, 'Thesis 2'),
('BS Computer Science', 4, 1, 'Practicum/OJT'),
('BS Computer Science', 4, 1, 'CS Elective 3'),

('BS Computer Science', 4, 2, 'Seminar in CS'),
('BS Computer Science', 4, 2, 'Emerging Technologies'),
('BS Computer Science', 4, 2, 'Project Presentation');

-- =========================
-- BS Computer Engineering (BSCpE)
-- =========================
-- NOTE: Using the same subject naming style as provided.
INSERT INTO `curriculum_subjects` (`course`, `year_level`, `semester`, `subject_name`) VALUES
('BS Computer Engineering', 1, 1, 'Calculus 1'),
('BS Computer Engineering', 1, 1, 'Chemistry for Engineers'),
('BS Computer Engineering', 1, 1, 'Engineering Drawing'),
('BS Computer Engineering', 1, 1, 'Introduction to Computing'),
('BS Computer Engineering', 1, 1, 'NSTP 1'),
('BS Computer Engineering', 1, 1, 'PE 1'),

('BS Computer Engineering', 1, 2, 'Calculus 2'),
('BS Computer Engineering', 1, 2, 'Physics for Engineers'),
('BS Computer Engineering', 1, 2, 'Computer Programming'),
('BS Computer Engineering', 1, 2, 'Differential Equations'),
('BS Computer Engineering', 1, 2, 'NSTP 2'),
('BS Computer Engineering', 1, 2, 'PE 2'),

('BS Computer Engineering', 2, 1, 'Data Structures'),
('BS Computer Engineering', 2, 1, 'Digital Logic Design'),
('BS Computer Engineering', 2, 1, 'Engineering Economics'),
('BS Computer Engineering', 2, 1, 'Electrical Circuits 1'),
('BS Computer Engineering', 2, 1, 'PE 3'),

('BS Computer Engineering', 2, 2, 'Electrical Circuits 2'),
('BS Computer Engineering', 2, 2, 'Electronics 1'),
('BS Computer Engineering', 2, 2, 'Microprocessors'),
('BS Computer Engineering', 2, 2, 'Object-Oriented Programming'),
('BS Computer Engineering', 2, 2, 'PE 4'),

('BS Computer Engineering', 3, 1, 'Embedded Systems'),
('BS Computer Engineering', 3, 1, 'Operating Systems'),
('BS Computer Engineering', 3, 1, 'Computer Networks'),
('BS Computer Engineering', 3, 1, 'Signals and Systems'),
('BS Computer Engineering', 3, 1, 'Research Methods'),

('BS Computer Engineering', 3, 2, 'Computer Architecture'),
('BS Computer Engineering', 3, 2, 'Control Systems'),
('BS Computer Engineering', 3, 2, 'Software Engineering'),
('BS Computer Engineering', 3, 2, 'CpE Elective 1'),
('BS Computer Engineering', 3, 2, 'Thesis 1'),

('BS Computer Engineering', 4, 1, 'Thesis 2'),
('BS Computer Engineering', 4, 1, 'Practicum/OJT'),
('BS Computer Engineering', 4, 1, 'CpE Elective 2'),

('BS Computer Engineering', 4, 2, 'Design Project'),
('BS Computer Engineering', 4, 2, 'Seminar'),
('BS Computer Engineering', 4, 2, 'Professional Practice');

-- =========================
-- BS Data Science (BSDS)
-- =========================
INSERT INTO `curriculum_subjects` (`course`, `year_level`, `semester`, `subject_name`) VALUES
('BS Data Science', 1, 1, 'Introduction to Data Science'),
('BS Data Science', 1, 1, 'Programming Fundamentals'),
('BS Data Science', 1, 1, 'Calculus 1'),
('BS Data Science', 1, 1, 'Statistics 1'),
('BS Data Science', 1, 1, 'NSTP 1'),
('BS Data Science', 1, 1, 'PE 1'),

('BS Data Science', 1, 2, 'Object-Oriented Programming'),
('BS Data Science', 1, 2, 'Calculus 2'),
('BS Data Science', 1, 2, 'Statistics 2'),
('BS Data Science', 1, 2, 'Data Visualization'),
('BS Data Science', 1, 2, 'NSTP 2'),
('BS Data Science', 1, 2, 'PE 2'),

('BS Data Science', 2, 1, 'Database Systems'),
('BS Data Science', 2, 1, 'Probability Theory'),
('BS Data Science', 2, 1, 'Data Structures'),
('BS Data Science', 2, 1, 'Linear Algebra'),
('BS Data Science', 2, 1, 'PE 3'),

('BS Data Science', 2, 2, 'Big Data Analytics'),
('BS Data Science', 2, 2, 'Machine Learning 1'),
('BS Data Science', 2, 2, 'Data Mining'),
('BS Data Science', 2, 2, 'Research Methods'),
('BS Data Science', 2, 2, 'PE 4'),

('BS Data Science', 3, 1, 'Predictive Analytics'),
('BS Data Science', 3, 1, 'Artificial Intelligence'),
('BS Data Science', 3, 1, 'Cloud Computing'),
('BS Data Science', 3, 1, 'Data Ethics'),

('BS Data Science', 3, 2, 'Deep Learning'),
('BS Data Science', 3, 2, 'Natural Language Processing'),
('BS Data Science', 3, 2, 'Data Science Elective'),
('BS Data Science', 3, 2, 'Thesis 1'),

('BS Data Science', 4, 1, 'Thesis 2'),
('BS Data Science', 4, 1, 'Practicum/OJT'),

('BS Data Science', 4, 2, 'Capstone Project'),
('BS Data Science', 4, 2, 'Seminar in Data Science');

-- =========================
-- BS Cybersecurity (BSCY)
-- =========================
INSERT INTO `curriculum_subjects` (`course`, `year_level`, `semester`, `subject_name`) VALUES
('BS Cybersecurity', 1, 1, 'Introduction to Cybersecurity'),
('BS Cybersecurity', 1, 1, 'Programming Fundamentals'),
('BS Cybersecurity', 1, 1, 'Mathematics in the Modern World'),
('BS Cybersecurity', 1, 1, 'NSTP 1'),
('BS Cybersecurity', 1, 1, 'PE 1'),

('BS Cybersecurity', 1, 2, 'Networking Fundamentals'),
('BS Cybersecurity', 1, 2, 'Linux Administration'),
('BS Cybersecurity', 1, 2, 'Programming 2'),
('BS Cybersecurity', 1, 2, 'NSTP 2'),
('BS Cybersecurity', 1, 2, 'PE 2'),

('BS Cybersecurity', 2, 1, 'Information Security'),
('BS Cybersecurity', 2, 1, 'Data Structures'),
('BS Cybersecurity', 2, 1, 'Operating Systems'),
('BS Cybersecurity', 2, 1, 'PE 3'),

('BS Cybersecurity', 2, 2, 'Ethical Hacking'),
('BS Cybersecurity', 2, 2, 'Cryptography'),
('BS Cybersecurity', 2, 2, 'Network Security'),
('BS Cybersecurity', 2, 2, 'PE 4'),

('BS Cybersecurity', 3, 1, 'Digital Forensics'),
('BS Cybersecurity', 3, 1, 'Security Operations'),
('BS Cybersecurity', 3, 1, 'Incident Response'),
('BS Cybersecurity', 3, 1, 'Research Methods'),

('BS Cybersecurity', 3, 2, 'Penetration Testing'),
('BS Cybersecurity', 3, 2, 'Cloud Security'),
('BS Cybersecurity', 3, 2, 'Cyber Law'),
('BS Cybersecurity', 3, 2, 'Thesis 1'),

('BS Cybersecurity', 4, 1, 'Thesis 2'),
('BS Cybersecurity', 4, 1, 'Practicum/OJT'),

('BS Cybersecurity', 4, 2, 'Security Audit'),
('BS Cybersecurity', 4, 2, 'Cybersecurity Seminar');

-- =========================
-- BS Business Administration (BSBA)
-- =========================
INSERT INTO `curriculum_subjects` (`course`, `year_level`, `semester`, `subject_name`) VALUES
('BS Business Administration', 1, 1, 'Principles of Management'),
('BS Business Administration', 1, 1, 'Fundamentals of Accounting'),
('BS Business Administration', 1, 1, 'Purposive Communication'),
('BS Business Administration', 1, 1, 'NSTP 1'),
('BS Business Administration', 1, 1, 'PE 1'),

('BS Business Administration', 1, 2, 'Microeconomics'),
('BS Business Administration', 1, 2, 'Business Mathematics'),
('BS Business Administration', 1, 2, 'Marketing Fundamentals'),
('BS Business Administration', 1, 2, 'NSTP 2'),
('BS Business Administration', 1, 2, 'PE 2'),

('BS Business Administration', 2, 1, 'Financial Management'),
('BS Business Administration', 2, 1, 'Human Resource Management'),
('BS Business Administration', 2, 1, 'Operations Management'),
('BS Business Administration', 2, 1, 'PE 3'),

('BS Business Administration', 2, 2, 'Business Statistics'),
('BS Business Administration', 2, 2, 'Business Law'),
('BS Business Administration', 2, 2, 'Entrepreneurship'),
('BS Business Administration', 2, 2, 'PE 4'),

('BS Business Administration', 3, 1, 'Strategic Management'),
('BS Business Administration', 3, 1, 'International Business'),
('BS Business Administration', 3, 1, 'Research in Business'),

('BS Business Administration', 3, 2, 'Business Ethics'),
('BS Business Administration', 3, 2, 'Elective Courses'),
('BS Business Administration', 3, 2, 'Feasibility Study'),

('BS Business Administration', 4, 1, 'Practicum/OJT'),
('BS Business Administration', 4, 1, 'Capstone Project'),

('BS Business Administration', 4, 2, 'Seminar'),
('BS Business Administration', 4, 2, 'Business Plan Defense');

-- =========================
-- BS Accountancy (BSACT)
-- =========================
-- NOTE: Your provided task lists BSA major subjects as a single combined list.
-- Since curriculum_subjects requires year/semester, we insert them as year 1 / semester 1 by default.
INSERT INTO `curriculum_subjects` (`course`, `year_level`, `semester`, `subject_name`) VALUES
('BS Accountancy', 1, 1, 'Financial Accounting and Reporting 1'),
('BS Accountancy', 1, 1, 'Financial Accounting and Reporting 2'),
('BS Accountancy', 1, 1, 'Financial Accounting and Reporting 3'),
('BS Accountancy', 1, 1, 'Financial Accounting and Reporting 4'),

('BS Accountancy', 1, 2, 'Cost Accounting'),
('BS Accountancy', 1, 2, 'Intermediate Accounting 1'),
('BS Accountancy', 1, 2, 'Intermediate Accounting 2'),
('BS Accountancy', 1, 2, 'Intermediate Accounting 3'),

('BS Accountancy', 2, 1, 'Advanced Accounting'),
('BS Accountancy', 2, 1, 'Auditing Theory'),
('BS Accountancy', 2, 1, 'Auditing Problems'),
('BS Accountancy', 2, 1, 'Taxation 1'),

('BS Accountancy', 2, 2, 'Taxation 2'),
('BS Accountancy', 2, 2, 'Taxation 3'),

('BS Accountancy', 3, 1, 'Management Advisory Services'),
('BS Accountancy', 3, 1, 'Accounting Information Systems'),

('BS Accountancy', 3, 2, 'Regulatory Framework'),
('BS Accountancy', 3, 2, 'Strategic Cost Management'),

('BS Accountancy', 4, 1, 'Business Law'),
('BS Accountancy', 4, 1, 'Financial Management'),

('BS Accountancy', 4, 2, 'Internship'),
('BS Accountancy', 4, 2, 'Integrated Review Courses');

-- =========================
-- BS Entrepreneurship (BSENT)
-- =========================
INSERT INTO `curriculum_subjects` (`course`, `year_level`, `semester`, `subject_name`) VALUES
('BS Entrepreneurship', 1, 1, 'Entrepreneurial Mind'),
('BS Entrepreneurship', 1, 1, 'Creativity and Innovation'),
('BS Entrepreneurship', 1, 1, 'Opportunity Seeking'),
('BS Entrepreneurship', 1, 1, 'Marketing Management'),

('BS Entrepreneurship', 1, 2, 'Business Plan Preparation'),
('BS Entrepreneurship', 1, 2, 'Family Business Management'),
('BS Entrepreneurship', 1, 2, 'New Venture Creation'),

('BS Entrepreneurship', 2, 1, 'E-Commerce'),
('BS Entrepreneurship', 2, 1, 'Strategic Entrepreneurship'),
('BS Entrepreneurship', 2, 1, 'Practicum'),

('BS Entrepreneurship', 2, 2, 'Business Incubation');

-- =========================
-- BS Marketing Management (BSMM)
-- =========================
INSERT INTO `curriculum_subjects` (`course`, `year_level`, `semester`, `subject_name`) VALUES
('BS Marketing Management', 1, 1, 'Principles of Marketing'),
('BS Marketing Management', 1, 1, 'Consumer Behavior'),
('BS Marketing Management', 1, 1, 'Marketing Research'),
('BS Marketing Management', 1, 2, 'Retail Management'),
('BS Marketing Management', 1, 2, 'Sales Management'),
('BS Marketing Management', 1, 2, 'Digital Marketing'),

('BS Marketing Management', 2, 1, 'Advertising'),
('BS Marketing Management', 2, 1, 'Brand Management'),
('BS Marketing Management', 2, 1, 'International Marketing'),
('BS Marketing Management', 2, 2, 'Practicum');

-- =========================
-- BS Financial Management (BSFM)
-- =========================
INSERT INTO `curriculum_subjects` (`course`, `year_level`, `semester`, `subject_name`) VALUES
('BS Financial Management', 1, 1, 'Financial Markets'),
('BS Financial Management', 1, 1, 'Investments'),
('BS Financial Management', 1, 2, 'Banking and Finance'),
('BS Financial Management', 1, 2, 'Risk Management'),

('BS Financial Management', 2, 1, 'Treasury Management'),
('BS Financial Management', 2, 1, 'Portfolio Management'),
('BS Financial Management', 2, 2, 'Financial Analysis'),
('BS Financial Management', 2, 2, 'Corporate Finance'),
('BS Financial Management', 3, 1, 'Practicum');

-- =========================
-- BS Human Resource Management (BSHRM)
-- =========================
INSERT INTO `curriculum_subjects` (`course`, `year_level`, `semester`, `subject_name`) VALUES
('BS Human Resource Management', 1, 1, 'Recruitment and Selection'),
('BS Human Resource Management', 1, 1, 'Training and Development'),
('BS Human Resource Management', 1, 2, 'Compensation Management'),
('BS Human Resource Management', 1, 2, 'Labor Relations'),

('BS Human Resource Management', 2, 1, 'Performance Management'),
('BS Human Resource Management', 2, 1, 'Organizational Development'),
('BS Human Resource Management', 2, 2, 'Human Resource Information Systems'),
('BS Human Resource Management', 2, 2, 'Practicum');

-- =========================
-- BS Office Administration (BSOA)
-- =========================
INSERT INTO `curriculum_subjects` (`course`, `year_level`, `semester`, `subject_name`) VALUES
('BS Office Administration', 1, 1, 'Office Procedures'),
('BS Office Administration', 1, 1, 'Business Communication'),
('BS Office Administration', 1, 2, 'Records Management'),
('BS Office Administration', 1, 2, 'Administrative Management'),

('BS Office Administration', 2, 1, 'Event Management'),
('BS Office Administration', 2, 1, 'Office Technology'),
('BS Office Administration', 2, 2, 'Shorthand'),
('BS Office Administration', 2, 2, 'Internship');

-- =========================
-- Engineering programs
-- =========================
-- Common engineering subjects are assigned to 1st-2nd year.
-- Specialization subjects are assigned to 3rd-4th year.
INSERT INTO `curriculum_subjects` (`course`, `year_level`, `semester`, `subject_name`) VALUES
('BS Civil Engineering', 1, 1, 'Calculus 1'),
('BS Civil Engineering', 1, 1, 'Physics'),
('BS Civil Engineering', 1, 1, 'Chemistry'),
('BS Civil Engineering', 1, 1, 'Engineering Drawing'),
('BS Civil Engineering', 1, 2, 'Calculus 2'),
('BS Civil Engineering', 1, 2, 'Computer Programming'),
('BS Civil Engineering', 1, 2, 'Mathematics for Engineers'),
('BS Civil Engineering', 1, 2, 'Engineering Economics'),
('BS Civil Engineering', 2, 1, 'Calculus 3'),
('BS Civil Engineering', 2, 1, 'Differential Equations'),
('BS Civil Engineering', 2, 2, 'Surveying'),
('BS Civil Engineering', 2, 2, 'Structural Theory'),
('BS Civil Engineering', 3, 1, 'Reinforced Concrete Design'),
('BS Civil Engineering', 3, 1, 'Steel Design'),
('BS Civil Engineering', 3, 2, 'Hydraulics'),
('BS Civil Engineering', 3, 2, 'Transportation Engineering'),
('BS Civil Engineering', 4, 1, 'Construction Management'),
('BS Civil Engineering', 4, 1, 'Geotechnical Engineering'),
('BS Civil Engineering', 4, 2, 'CE Design Project'),

('BS Mechanical Engineering', 1, 1, 'Calculus 1'),
('BS Mechanical Engineering', 1, 1, 'Physics'),
('BS Mechanical Engineering', 1, 1, 'Chemistry'),
('BS Mechanical Engineering', 1, 1, 'Engineering Drawing'),
('BS Mechanical Engineering', 1, 2, 'Calculus 2'),
('BS Mechanical Engineering', 1, 2, 'Computer Programming'),
('BS Mechanical Engineering', 1, 2, 'Mathematics for Engineers'),
('BS Mechanical Engineering', 1, 2, 'Engineering Economics'),
('BS Mechanical Engineering', 2, 1, 'Calculus 3'),
('BS Mechanical Engineering', 2, 1, 'Differential Equations'),
('BS Mechanical Engineering', 2, 2, 'Thermodynamics'),
('BS Mechanical Engineering', 2, 2, 'Fluid Mechanics'),
('BS Mechanical Engineering', 3, 1, 'Machine Design'),
('BS Mechanical Engineering', 3, 1, 'Heat Transfer'),
('BS Mechanical Engineering', 3, 2, 'Refrigeration and Air Conditioning'),
('BS Mechanical Engineering', 4, 1, 'Power Plant Engineering'),
('BS Mechanical Engineering', 4, 2, 'ME Design Project'),

('BS Electrical Engineering', 1, 1, 'Calculus 1'),
('BS Electrical Engineering', 1, 1, 'Physics'),
('BS Electrical Engineering', 1, 1, 'Chemistry'),
('BS Electrical Engineering', 1, 1, 'Engineering Drawing'),
('BS Electrical Engineering', 1, 2, 'Calculus 2'),
('BS Electrical Engineering', 1, 2, 'Computer Programming'),
('BS Electrical Engineering', 1, 2, 'Mathematics for Engineers'),
('BS Electrical Engineering', 1, 2, 'Engineering Economics'),
('BS Electrical Engineering', 2, 1, 'Calculus 3'),
('BS Electrical Engineering', 2, 1, 'Differential Equations'),
('BS Electrical Engineering', 2, 2, 'Electrical Circuits'),
('BS Electrical Engineering', 3, 1, 'Power Systems'),
('BS Electrical Engineering', 3, 1, 'Electrical Machines'),
('BS Electrical Engineering', 3, 2, 'Power Electronics'),
('BS Electrical Engineering', 4, 1, 'Illumination Engineering'),
('BS Electrical Engineering', 4, 2, 'EE Design Project'),

('BS Electronics Engineering', 1, 1, 'Calculus 1'),
('BS Electronics Engineering', 1, 1, 'Physics'),
('BS Electronics Engineering', 1, 1, 'Chemistry'),
('BS Electronics Engineering', 1, 1, 'Engineering Drawing'),
('BS Electronics Engineering', 1, 2, 'Calculus 2'),
('BS Electronics Engineering', 1, 2, 'Computer Programming'),
('BS Electronics Engineering', 1, 2, 'Mathematics for Engineers'),
('BS Electronics Engineering', 1, 2, 'Engineering Economics'),
('BS Electronics Engineering', 2, 1, 'Calculus 3'),
('BS Electronics Engineering', 2, 1, 'Differential Equations'),
('BS Electronics Engineering', 2, 2, 'Electronics Circuits'),
('BS Electronics Engineering', 3, 1, 'Communications Engineering'),
('BS Electronics Engineering', 3, 1, 'Digital Signal Processing'),
('BS Electronics Engineering', 3, 2, 'Microelectronics'),
('BS Electronics Engineering', 4, 1, 'Instrumentation'),
('BS Electronics Engineering', 4, 2, 'ECE Design Project'),

('BS Industrial Engineering', 1, 1, 'Calculus 1'),
('BS Industrial Engineering', 1, 1, 'Physics'),
('BS Industrial Engineering', 1, 1, 'Chemistry'),
('BS Industrial Engineering', 1, 1, 'Engineering Drawing'),
('BS Industrial Engineering', 1, 2, 'Calculus 2'),
('BS Industrial Engineering', 1, 2, 'Computer Programming'),
('BS Industrial Engineering', 1, 2, 'Mathematics for Engineers'),
('BS Industrial Engineering', 1, 2, 'Engineering Economics'),
('BS Industrial Engineering', 2, 1, 'Calculus 3'),
('BS Industrial Engineering', 2, 1, 'Differential Equations'),
('BS Industrial Engineering', 2, 2, 'Operations Research'),
('BS Industrial Engineering', 3, 1, 'Ergonomics'),
('BS Industrial Engineering', 3, 1, 'Systems Engineering'),
('BS Industrial Engineering', 3, 2, 'Production Planning'),
('BS Industrial Engineering', 4, 1, 'Quality Control'),
('BS Industrial Engineering', 4, 2, 'Facilities Planning'),

('BS Chemical Engineering', 1, 1, 'Calculus 1'),
('BS Chemical Engineering', 1, 1, 'Physics'),
('BS Chemical Engineering', 1, 1, 'Chemistry'),
('BS Chemical Engineering', 1, 1, 'Engineering Drawing'),
('BS Chemical Engineering', 1, 2, 'Calculus 2'),
('BS Chemical Engineering', 1, 2, 'Computer Programming'),
('BS Chemical Engineering', 1, 2, 'Mathematics for Engineers'),
('BS Chemical Engineering', 1, 2, 'Engineering Economics'),
('BS Chemical Engineering', 2, 1, 'Calculus 3'),
('BS Chemical Engineering', 2, 1, 'Differential Equations'),
('BS Chemical Engineering', 2, 2, 'Chemical Process Principles'),
('BS Chemical Engineering', 3, 1, 'Thermodynamics'),
('BS Chemical Engineering', 3, 1, 'Transport Phenomena'),
('BS Chemical Engineering', 3, 2, 'Reaction Engineering'),
('BS Chemical Engineering', 4, 1, 'Plant Design'),
('BS Chemical Engineering', 4, 2, 'Process Control'),

('BS Aeronautical Engineering', 1, 1, 'Calculus 1'),
('BS Aeronautical Engineering', 1, 1, 'Physics'),
('BS Aeronautical Engineering', 1, 1, 'Chemistry'),
('BS Aeronautical Engineering', 1, 1, 'Engineering Drawing'),
('BS Aeronautical Engineering', 1, 2, 'Calculus 2'),
('BS Aeronautical Engineering', 1, 2, 'Computer Programming'),
('BS Aeronautical Engineering', 1, 2, 'Mathematics for Engineers'),
('BS Aeronautical Engineering', 1, 2, 'Engineering Economics'),
('BS Aeronautical Engineering', 2, 1, 'Calculus 3'),
('BS Aeronautical Engineering', 2, 1, 'Differential Equations'),
('BS Aeronautical Engineering', 2, 2, 'Aerodynamics'),
('BS Aeronautical Engineering', 3, 1, 'Aircraft Structures'),
('BS Aeronautical Engineering', 3, 1, 'Flight Mechanics'),
('BS Aeronautical Engineering', 3, 2, 'Aircraft Design'),
('BS Aeronautical Engineering', 4, 1, 'Propulsion Systems'),
('BS Aeronautical Engineering', 4, 2, 'Aviation Safety');

-- =========================
-- Health Science programs (inserted with default year/sem where not specified)
-- =========================
INSERT INTO `curriculum_subjects` (`course`, `year_level`, `semester`, `subject_name`) VALUES
('BS Nursing', 1, 1, 'Fundamentals of Nursing'),
('BS Nursing', 1, 1, 'Health Assessment'),
('BS Nursing', 1, 2, 'Anatomy and Physiology'),
('BS Nursing', 1, 2, 'Maternal and Child Nursing'),
('BS Nursing', 2, 1, 'Medical-Surgical Nursing'),
('BS Nursing', 2, 1, 'Community Health Nursing'),
('BS Nursing', 2, 2, 'Psychiatric Nursing'),
('BS Nursing', 2, 2, 'Nursing Research'),
('BS Nursing', 3, 1, 'Leadership and Management'),
('BS Nursing', 3, 2, 'Intensive Clinical Practicum'),

('BS Medical Technology', 1, 1, 'Clinical Chemistry'),
('BS Medical Technology', 1, 1, 'Hematology'),
('BS Medical Technology', 1, 1, 'Microbiology and Parasitology'),
('BS Medical Technology', 1, 1, 'Immunology and Serology'),
('BS Medical Technology', 1, 1, 'Histopathology'),
('BS Medical Technology', 1, 1, 'Blood Banking'),
('BS Medical Technology', 1, 1, 'Laboratory Management'),
('BS Medical Technology', 1, 1, 'Clinical Internship'),

('BS Pharmacy', 1, 1, 'Pharmaceutical Chemistry'),
('BS Pharmacy', 1, 1, 'Pharmacology'),
('BS Pharmacy', 1, 1, 'Pharmaceutics'),
('BS Pharmacy', 1, 1, 'Pharmacognosy'),
('BS Pharmacy', 1, 1, 'Clinical Pharmacy'),
('BS Pharmacy', 1, 1, 'Toxicology'),
('BS Pharmacy', 1, 1, 'Hospital Pharmacy'),
('BS Pharmacy', 1, 1, 'Community Pharmacy'),
('BS Pharmacy', 1, 1, 'Internship'),

('BS Physical Therapy', 1, 1, 'Human Anatomy'),
('BS Physical Therapy', 1, 1, 'Kinesiology'),
('BS Physical Therapy', 1, 1, 'Exercise Therapy'),
('BS Physical Therapy', 1, 1, 'Neurological Rehabilitation'),
('BS Physical Therapy', 1, 1, 'Orthopedic Rehabilitation'),
('BS Physical Therapy', 1, 1, 'Pediatric PT'),
('BS Physical Therapy', 1, 1, 'Geriatric PT'),
('BS Physical Therapy', 1, 1, 'Clinical Internship'),

('BS Psychology', 1, 1, 'Understanding the Self'),
('BS Psychology', 1, 1, 'General Psychology'),
('BS Psychology', 1, 1, 'Purposive Communication'),
('BS Psychology', 1, 1, 'Mathematics in the Modern World'),
('BS Psychology', 1, 1, 'NSTP 1'),
('BS Psychology', 1, 1, 'PE 1'),

('BS Psychology', 1, 2, 'Developmental Psychology'),
('BS Psychology', 1, 2, 'Readings in Philippine History'),
('BS Psychology', 1, 2, 'Science, Technology and Society'),
('BS Psychology', 1, 2, 'Statistics for Psychology'),
('BS Psychology', 1, 2, 'NSTP 2'),
('BS Psychology', 1, 2, 'PE 2'),

('BS Psychology', 2, 1, 'Theories of Personality'),
('BS Psychology', 2, 1, 'Psychological Statistics'),
('BS Psychology', 2, 1, 'Experimental Psychology'),
('BS Psychology', 2, 1, 'PE 3'),

('BS Psychology', 2, 2, 'Cognitive Psychology'),
('BS Psychology', 2, 2, 'Abnormal Psychology'),
('BS Psychology', 2, 2, 'Social Psychology'),
('BS Psychology', 2, 2, 'PE 4'),

('BS Psychology', 3, 1, 'Industrial Psychology'),
('BS Psychology', 3, 1, 'Psychological Assessment'),
('BS Psychology', 3, 1, 'Research Methods in Psychology'),

('BS Psychology', 3, 2, 'Clinical Psychology'),
('BS Psychology', 3, 2, 'Counseling Psychology'),
('BS Psychology', 3, 2, 'Psychological Assessment 2'),
('BS Psychology', 3, 2, 'Thesis 1'),

('BS Psychology', 4, 1, 'Thesis 2'),
('BS Psychology', 4, 1, 'Practicum/OJT'),

('BS Psychology', 4, 2, 'Seminar in Psychology'),
('BS Psychology', 4, 2, 'Professional Ethics'),
('BS Psychology', 4, 2, 'Case Study Presentation');
