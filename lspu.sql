CREATE DATABASE IF NOT EXISTS `lspu_portal`;
USE `lspu_portal`;

-- Core Identity Registry Table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `fullname` VARCHAR(255) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('student', 'professor') NOT NULL,
    `student_id` VARCHAR(50) DEFAULT NULL UNIQUE,
    `course` VARCHAR(100) DEFAULT NULL,
    `year_level` INT DEFAULT NULL,
    `section` VARCHAR(5) DEFAULT NULL,
    `enrollment_status` ENUM('Regular', 'Irregular', 'Dropped') DEFAULT 'Regular',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Extensive Student Background Records Table
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
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Curriculum Matrix Dictionary Table
CREATE TABLE IF NOT EXISTS `curriculum_subjects` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `course` VARCHAR(100) NOT NULL,
    `year_level` INT NOT NULL,
    `semester` INT NOT NULL,
    `subject_name` VARCHAR(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Enrollment Request Flow Control Table
CREATE TABLE IF NOT EXISTS `student_enrollments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `subject_id` INT NOT NULL,
    `semester` INT NOT NULL,
    `year_level` INT NOT NULL,
    `status` ENUM('pending', 'approved') DEFAULT 'pending',
    `schedule_day_time` VARCHAR(100) NOT NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`subject_id`) REFERENCES `curriculum_subjects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Student Academic Performance Matrix Table
CREATE TABLE IF NOT EXISTS `student_grades` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `subject_id` INT NOT NULL,
    `grade` DECIMAL(4,2) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_subject` (`user_id`, `subject_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`subject_id`) REFERENCES `curriculum_subjects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Data: Insert Comprehensive University Curriculum Matrix
INSERT INTO `curriculum_subjects` (`course`, `year_level`, `semester`, `subject_name`) VALUES
-- BS Psychology
('BS Psychology', 1, 1, 'Purposive Communication'), ('BS Psychology', 1, 1, 'Understanding the Self'), ('BS Psychology', 1, 1, 'Mathematics in the Modern World'), ('BS Psychology', 1, 1, 'Introduction to Psychology'), ('BS Psychology', 1, 1, 'NSTP 1'), ('BS Psychology', 1, 1, 'PE 1'), ('BS Psychology', 1, 1, 'General Biology'),
('BS Psychology', 1, 2, 'Readings in Philippine History'), ('BS Psychology', 1, 2, 'Ethics'), ('BS Psychology', 1, 2, 'The Contemporary World'), ('BS Psychology', 1, 2, 'Psychological Statistics'), ('BS Psychology', 1, 2, 'Developmental Psychology'), ('BS Psychology', 1, 2, 'NSTP 2'), ('BS Psychology', 1, 2, 'PE 2'),
('BS Psychology', 2, 1, 'Experimental Psychology'), ('BS Psychology', 2, 1, 'Cognitive Psychology'), ('BS Psychology', 2, 1, 'Filipino Psychology (Sikolohiyang Pilipino)'), ('BS Psychology', 2, 1, 'Biopsychology'), ('BS Psychology', 2, 1, 'PE 3'), ('BS Psychology', 2, 1, 'Elective'),
('BS Psychology', 2, 2, 'Abnormal Psychology'), ('BS Psychology', 2, 2, 'Social Psychology'), ('BS Psychology', 2, 2, 'Theories of Personality'), ('BS Psychology', 2, 2, 'Psychological Assessment'), ('BS Psychology', 2, 2, 'PE 4'), ('BS Psychology', 2, 2, 'Elective'),
('BS Psychology', 3, 1, 'Industrial/Organizational Psychology'), ('BS Psychology', 3, 1, 'Clinical Psychology'), ('BS Psychology', 3, 1, 'Research in Psychology 1'), ('BS Psychology', 3, 1, 'Counseling Psychology'), ('BS Psychology', 3, 1, 'Elective'),
('BS Psychology', 3, 2, 'Research in Psychology 2'), ('BS Psychology', 3, 2, 'Psychological Assessment 2'), ('BS Psychology', 3, 2, 'Human Resource Management'), ('BS Psychology', 3, 2, 'Community Psychology'), ('BS Psychology', 3, 2, 'Practicum Preparation'),
('BS Psychology', 4, 1, 'Practicum/OJT'), ('BS Psychology', 4, 1, 'Seminar in Psychology'), ('BS Psychology', 4, 1, 'Thesis Writing'), ('BS Psychology', 4, 1, 'Case Analysis'),
('BS Psychology', 4, 2, 'Advanced Psychological Assessment'), ('BS Psychology', 4, 2, 'Thesis Defense'), ('BS Psychology', 4, 2, 'Career and Professional Development'), ('BS Psychology', 4, 2, 'Comprehensive Review'),

-- Information Technology
('Information Technology', 1, 1, 'Introduction to Computing'), ('Information Technology', 1, 1, 'Computer Programming 1'), ('Information Technology', 1, 1, 'Mathematics in the Modern World'), ('Information Technology', 1, 1, 'Understanding the Self'), ('Information Technology', 1, 1, 'NSTP 1'), ('Information Technology', 1, 1, 'PE 1'),
('Information Technology', 1, 2, 'Computer Programming 2'), ('Information Technology', 1, 2, 'Discrete Mathematics'), ('Information Technology', 1, 2, 'Data Structures and Algorithms'), ('Information Technology', 1, 2, 'Purposive Communication'), ('Information Technology', 1, 2, 'NSTP 2'), ('Information Technology', 1, 2, 'PE 2'),
('Information Technology', 2, 1, 'Object-Oriented Programming'), ('Information Technology', 2, 1, 'Database Management Systems'), ('Information Technology', 2, 1, 'Human Computer Interaction'), ('Information Technology', 2, 1, 'Web Systems and Technologies'), ('Information Technology', 2, 1, 'PE 3'),
('Information Technology', 2, 2, 'Networking 1'), ('Information Technology', 2, 2, 'Information Management'), ('Information Technology', 2, 2, 'Event-Driven Programming'), ('Information Technology', 2, 2, 'Systems Analysis and Design'), ('Information Technology', 2, 2, 'PE 4'),
('Information Technology', 3, 1, 'Networking 2'), ('Information Technology', 3, 1, 'Information Assurance and Security'), ('Information Technology', 3, 1, 'Integrative Programming'), ('Information Technology', 3, 1, 'Quantitative Methods'), ('Information Technology', 3, 1, 'Elective'),
('Information Technology', 3, 2, 'Web Development'), ('Information Technology', 3, 2, 'Mobile Application Development'), ('Information Technology', 3, 2, 'IT Project Management'), ('Information Technology', 3, 2, 'Capstone Project 1'), ('Information Technology', 3, 2, 'Elective'),
('Information Technology', 4, 1, 'Capstone Project 2'), ('Information Technology', 4, 1, 'Systems Integration and Architecture'), ('Information Technology', 4, 1, 'Technopreneurship'), ('Information Technology', 4, 1, 'Practicum/OJT'),
('Information Technology', 4, 2, 'IT Seminar'), ('Information Technology', 4, 2, 'Emerging Technologies'), ('Information Technology', 4, 2, 'Professional Issues in IT'), ('Information Technology', 4, 2, 'Comprehensive Examination'),

-- Computer Science
('Computer Science', 1, 1, 'Introduction to Computing'), ('Computer Science', 1, 1, 'Computer Programming 1'), ('Computer Science', 1, 1, 'Calculus 1'), ('Computer Science', 1, 1, 'Understanding the Self'), ('Computer Science', 1, 1, 'NSTP 1'), ('Computer Science', 1, 1, 'PE 1'),
('Computer Science', 1, 2, 'Computer Programming 2'), ('Computer Science', 1, 2, 'Discrete Structures'), ('Computer Science', 1, 2, 'Calculus 2'), ('Computer Science', 1, 2, 'Purposive Communication'), ('Computer Science', 1, 2, 'NSTP 2'), ('Computer Science', 1, 2, 'PE 2'),
('Computer Science', 2, 1, 'Data Structures and Algorithms'), ('Computer Science', 2, 1, 'Object-Oriented Programming'), ('Computer Science', 2, 1, 'Linear Algebra'), ('Computer Science', 2, 1, 'Digital Logic Design'), ('Computer Science', 2, 1, 'PE 3'),
('Computer Science', 2, 2, 'Algorithms and Complexity'), ('Computer Science', 2, 2, 'Operating Systems'), ('Computer Science', 2, 2, 'Software Engineering'), ('Computer Science', 2, 2, 'Probability and Statistics'), ('Computer Science', 2, 2, 'PE 4'),
('Computer Science', 3, 1, 'Database Systems'), ('Computer Science', 3, 1, 'Automata Theory'), ('Computer Science', 3, 1, 'Computer Architecture'), ('Computer Science', 3, 1, 'Programming Languages'), ('Computer Science', 3, 1, 'Elective'),
('Computer Science', 3, 2, 'Artificial Intelligence'), ('Computer Science', 3, 2, 'Networking and Communications'), ('Computer Science', 3, 2, 'Web Development'), ('Computer Science', 3, 2, 'Research Methods'), ('Computer Science', 3, 2, 'Elective'),
('Computer Science', 4, 1, 'Machine Learning'), ('Computer Science', 4, 1, 'CS Thesis 1'), ('Computer Science', 4, 1, 'Information Security'), ('Computer Science', 4, 1, 'Practicum/OJT'),
('Computer Science', 4, 2, 'CS Thesis 2'), ('Computer Science', 4, 2, 'Parallel and Distributed Computing'), ('Computer Science', 4, 2, 'Emerging Technologies'), ('Computer Science', 4, 2, 'Comprehensive Examination');