-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 13, 2026 at 04:36 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.0.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `scheduling_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `entity_type`, `entity_id`, `details`, `created_at`) VALUES
(1, 1, 'Created new course', 'course', 1, 'Added course CP101 - Introduction to Programming', '2026-02-09 06:16:21'),
(2, 2, 'Updated course', 'course', 2, 'Updated credits for CP102', '2026-02-09 06:16:21'),
(3, 3, 'Deleted course', 'course', 3, 'Removed CP103 from catalog', '2026-02-09 06:16:21'),
(4, 4, 'Added new room', 'room', 1, 'Room R101 with projector and computers', '2026-02-09 06:16:21'),
(5, 5, 'Updated room capacity', 'room', 2, 'Increased capacity of R102 from 30 to 40', '2026-02-09 06:16:21'),
(6, 6, 'Deleted room', 'room', 3, 'Removed room R103', '2026-02-09 06:16:21'),
(7, 7, 'Assigned teacher to section', 'course_section', 1, 'Dr. Alice Johnson assigned to CP101 Sec 1', '2026-02-09 06:16:21'),
(8, 8, 'Updated teacher availability', 'teacher_availability', 2, 'Changed available hours for Dr. Bob Smith', '2026-02-09 06:16:21'),
(9, 9, 'Added timetable', 'timetable', 1, 'Generated timetable for CS department, Fall-2026', '2026-02-09 06:16:21'),
(10, 10, 'Updated timetable', 'timetable', 2, 'Added new slots to timetable for EE department', '2026-02-09 06:16:21'),
(11, 1, 'Resolved conflict', 'conflict', 1, 'Room overlap between slots 1 and 5 resolved', '2026-02-09 06:16:21'),
(12, 2, 'Ignored conflict', 'conflict', 2, 'Ignored teacher overlap for Dr. Charlie Brown', '2026-02-09 06:16:21'),
(13, 3, 'Added student', 'student', 1, 'New student John Doe added to Computer Science, Semester 1', '2026-02-09 06:16:21'),
(14, 4, 'Updated student', 'student', 2, 'Updated email for student Jane Smith', '2026-02-09 06:16:21'),
(15, 5, 'Deleted student', 'student', 3, 'Removed student Mike Johnson', '2026-02-09 06:16:21'),
(16, 6, 'Added HOD', 'hod', 1, 'Assigned Dr. Alice Johnson as HOD for CS department', '2026-02-09 06:16:21'),
(17, 7, 'Updated HOD', 'hod', 2, 'Changed HOD status to inactive for EE department', '2026-02-09 06:16:21'),
(18, 8, 'Added course assignment', 'course_assignment', 1, 'Assigned Dr. Alice Johnson to CP101 lecture', '2026-02-09 06:16:21'),
(19, 9, 'Updated course assignment', 'course_assignment', 2, 'Changed lab teacher for CP102 Section 2', '2026-02-09 06:16:21'),
(20, 10, 'Deleted course assignment', 'course_assignment', 3, 'Removed teacher from CP103 Section 3', '2026-02-09 06:16:21');

-- --------------------------------------------------------

--
-- Table structure for table `conflicts`
--

CREATE TABLE `conflicts` (
  `id` int(11) NOT NULL,
  `timetable_id` int(11) NOT NULL,
  `conflict_type` enum('room_overlap','teacher_overlap','student_overlap','capacity_exceeded') NOT NULL,
  `description` text NOT NULL,
  `slot_id_1` int(11) NOT NULL,
  `slot_id_2` int(11) NOT NULL,
  `status` enum('unresolved','resolved','ignored') DEFAULT 'unresolved',
  `detected_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `resolved_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `conflicts`
--

INSERT INTO `conflicts` (`id`, `timetable_id`, `conflict_type`, `description`, `slot_id_1`, `slot_id_2`, `status`, `detected_at`, `resolved_at`) VALUES
(1, 1, 'room_overlap', 'Room R101 double booked for two lectures', 1, 5, 'resolved', '2026-02-09 06:13:28', NULL),
(2, 1, 'teacher_overlap', 'Teacher Dr. Alice Johnson assigned to two slots simultaneously', 1, 11, 'resolved', '2026-02-09 06:13:28', NULL),
(3, 1, 'student_overlap', 'Students in CS101 scheduled for two lectures at the same time', 2, 6, 'unresolved', '2026-02-09 06:13:28', NULL),
(4, 2, 'capacity_exceeded', 'Room capacity exceeded for lecture CP380 Section 2', 2, 12, 'resolved', '2026-02-09 06:13:28', NULL),
(5, 2, 'room_overlap', 'Room R102 double booked', 3, 7, 'ignored', '2026-02-09 06:13:28', NULL),
(6, 2, 'teacher_overlap', 'Teacher Dr. Charlie Brown assigned to multiple slots', 3, 13, 'resolved', '2026-02-09 06:13:28', NULL),
(7, 3, 'student_overlap', 'Students in CP476 Section 1 scheduled for overlapping classes', 4, 8, 'resolved', '2026-02-09 06:13:28', NULL),
(8, 3, 'capacity_exceeded', 'Room capacity exceeded for lab CP380 Section 3', 4, 14, 'resolved', '2026-02-09 06:13:28', NULL),
(9, 3, 'room_overlap', 'Room R103 double booked', 5, 9, 'ignored', '2026-02-09 06:13:28', NULL),
(10, 4, 'teacher_overlap', 'Teacher Dr. Diana Prince double assigned', 5, 15, 'resolved', '2026-02-09 06:13:28', NULL),
(11, 4, 'student_overlap', 'Students in SE201 have overlapping lab slots', 6, 16, 'resolved', '2026-02-09 06:13:28', NULL),
(12, 4, 'capacity_exceeded', 'Room capacity exceeded for lecture CP101 Section 6', 6, 16, 'ignored', '2026-02-09 06:13:28', NULL),
(13, 5, 'room_overlap', 'Room R104 double booked for lecture and lab', 7, 17, 'resolved', '2026-02-09 06:13:28', NULL),
(14, 5, 'teacher_overlap', 'Teacher Dr. Fiona Gallagher double assigned', 7, 17, 'resolved', '2026-02-09 06:13:28', NULL),
(15, 5, 'student_overlap', 'Students in DS301 scheduled in two places', 8, 18, 'resolved', '2026-02-09 06:13:28', NULL),
(16, 5, 'capacity_exceeded', 'Room capacity exceeded for lab CP202 Section 8', 8, 18, 'ignored', '2026-02-09 06:13:28', NULL),
(17, 1, 'room_overlap', 'Room R105 double booked', 9, 19, 'resolved', '2026-02-09 06:13:28', NULL),
(18, 1, 'teacher_overlap', 'Teacher Dr. George Martin assigned to two labs', 9, 19, 'resolved', '2026-02-09 06:13:28', NULL),
(19, 2, 'student_overlap', 'Students in AI101 have overlapping lecture slots', 10, 20, 'unresolved', '2026-02-09 06:13:28', NULL),
(20, 2, 'capacity_exceeded', 'Room capacity exceeded for CP380 Section 10', 10, 20, 'resolved', '2026-02-09 06:13:28', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `code` varchar(20) NOT NULL,
  `name` varchar(150) NOT NULL,
  `department_id` int(11) NOT NULL,
  `credits` int(11) NOT NULL,
  `type` enum('lecture','lab','lecture_lab') NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive','archived') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `code`, `name`, `department_id`, `credits`, `type`, `description`, `status`, `created_at`) VALUES
(1, 'CS101', 'Introduction to Computer Science', 1, 3, 'lecture', 'Basics of programming and computer systems', 'active', '2026-02-09 05:39:33'),
(2, 'CS102', 'Data Structures', 1, 4, 'lecture_lab', 'Study of data organization and algorithms', 'active', '2026-02-09 05:39:33'),
(3, 'ICT201', 'Networking Fundamentals', 2, 3, 'lecture_lab', 'Basics of computer networks and protocols', 'active', '2026-02-09 05:39:33'),
(4, 'ICT202', 'Database Systems', 2, 3, 'lecture', 'Introduction to databases and SQL', 'active', '2026-02-09 05:39:33'),
(5, 'ECE301', 'Circuit Analysis', 3, 4, 'lecture_lab', 'Electrical circuits and analysis techniques', 'archived', '2026-02-09 05:39:33'),
(6, 'ECE302', 'Digital Electronics', 3, 3, 'lecture_lab', 'Logic circuits and digital design', 'inactive', '2026-02-09 05:39:33'),
(7, 'ME401', 'Thermodynamics', 4, 3, 'lecture', 'Principles of thermodynamics', 'active', '2026-02-09 05:39:33'),
(8, 'ME402', 'Mechanical Design', 4, 4, 'lecture_lab', 'Design and analysis of mechanical systems', 'active', '2026-02-09 05:39:33'),
(9, 'CE501', 'Structural Analysis', 5, 3, 'lecture', 'Study of structures and forces', 'active', '2026-02-09 05:39:33'),
(10, 'CE502', 'Construction Management', 5, 3, 'lecture', 'Managing construction projects effectively', 'active', '2026-02-09 05:39:33'),
(11, 'AI601', 'Machine Learning', 6, 4, 'lecture_lab', 'Introduction to AI and machine learning techniques', 'active', '2026-02-09 05:39:33'),
(12, 'AI602', 'Neural Networks', 6, 3, 'lecture', 'Deep learning and neural network architectures', 'archived', '2026-02-09 05:39:33'),
(13, 'DS701', 'Statistics for Data Science', 7, 3, 'lecture', 'Probability and statistics applied to data', 'inactive', '2026-02-09 05:39:33'),
(14, 'DS702', 'Data Visualization', 7, 3, 'lecture_lab', 'Techniques to visualize and interpret data', 'active', '2026-02-09 05:39:33'),
(15, 'SE801', 'Software Engineering Principles', 8, 3, 'lecture', 'Software development lifecycle and methodologies', 'active', '2026-02-09 05:39:33'),
(16, 'SE802', 'Software Project Lab', 8, 4, 'lab', 'Hands-on software development project', 'active', '2026-02-09 05:39:33'),
(17, 'EE901', 'Electronic Circuits', 9, 4, 'lecture_lab', 'Design and analysis of electronic circuits', 'active', '2026-02-09 05:39:33'),
(18, 'EE902', 'Microprocessors', 9, 3, 'lecture_lab', 'Study of microprocessor architecture and programming', 'inactive', '2026-02-09 05:39:33'),
(19, 'PSW100', 'Patient Care Fundamentals', 10, 3, 'lecture_lab', 'Introduction to patient support and healthcare', 'active', '2026-02-09 05:39:33'),
(20, 'PSW101', 'Healthcare Practices', 10, 3, 'lecture', 'Practical healthcare procedures and ethics', 'active', '2026-02-09 05:39:33');

-- --------------------------------------------------------

--
-- Table structure for table `course_assignments`
--

CREATE TABLE `course_assignments` (
  `id` int(11) NOT NULL,
  `course_section_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `component` enum('lecture','lab') NOT NULL,
  `assigned_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `course_assignments`
--

INSERT INTO `course_assignments` (`id`, `course_section_id`, `teacher_id`, `component`, `assigned_date`) VALUES
(1, 1, 1, 'lecture', '2026-02-09 06:04:04'),
(2, 2, 2, 'lecture', '2026-02-09 06:04:04'),
(3, 3, 3, 'lecture', '2026-02-09 06:04:04'),
(4, 4, 4, 'lecture', '2026-02-09 06:04:04'),
(5, 5, 5, 'lecture', '2026-02-09 06:04:04'),
(6, 6, 6, 'lecture', '2026-02-09 06:04:04'),
(7, 7, 7, 'lecture', '2026-02-09 06:04:04'),
(8, 8, 8, 'lecture', '2026-02-09 06:04:04'),
(9, 9, 9, 'lecture', '2026-02-09 06:04:04'),
(10, 10, 10, 'lecture', '2026-02-09 06:04:04'),
(11, 1, 11, 'lab', '2026-02-09 06:04:04'),
(12, 2, 12, 'lab', '2026-02-09 06:04:04'),
(13, 3, 13, 'lab', '2026-02-09 06:04:04'),
(14, 4, 14, 'lab', '2026-02-09 06:04:04'),
(15, 5, 15, 'lab', '2026-02-09 06:04:04'),
(16, 6, 16, 'lab', '2026-02-09 06:04:04'),
(17, 7, 17, 'lab', '2026-02-09 06:04:04'),
(18, 8, 18, 'lab', '2026-02-09 06:04:04'),
(19, 9, 1, 'lab', '2026-02-09 06:04:04'),
(20, 10, 2, 'lab', '2026-02-09 06:04:04');

-- --------------------------------------------------------

--
-- Table structure for table `course_sections`
--

CREATE TABLE `course_sections` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `section_number` int(11) NOT NULL,
  `term` varchar(20) NOT NULL,
  `year` int(11) NOT NULL,
  `max_students` int(11) NOT NULL,
  `enrolled_students` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `course_sections`
--

INSERT INTO `course_sections` (`id`, `course_id`, `section_number`, `term`, `year`, `max_students`, `enrolled_students`, `created_at`) VALUES
(1, 1, 1, 'Fall', 2026, 50, 25, '2026-02-09 05:49:06'),
(2, 2, 1, 'Fall', 2026, 40, 18, '2026-02-09 05:49:06'),
(3, 3, 1, 'Fall', 2026, 45, 30, '2026-02-09 05:49:06'),
(4, 4, 1, 'Fall', 2026, 35, 20, '2026-02-09 05:49:06'),
(5, 5, 1, 'Fall', 2026, 40, 22, '2026-02-09 05:49:06'),
(6, 6, 1, 'Fall', 2026, 30, 15, '2026-02-09 05:49:06'),
(7, 7, 1, 'Fall', 2026, 50, 40, '2026-02-09 05:49:06'),
(8, 8, 1, 'Fall', 2026, 45, 35, '2026-02-09 05:49:06'),
(9, 9, 1, 'Fall', 2026, 40, 25, '2026-02-09 05:49:06'),
(10, 10, 1, 'Fall', 2026, 35, 18, '2026-02-09 05:49:06'),
(11, 11, 1, 'Fall', 2026, 50, 45, '2026-02-09 05:49:06'),
(12, 12, 1, 'Fall', 2026, 40, 30, '2026-02-09 05:49:06'),
(13, 13, 1, 'Fall', 2026, 45, 32, '2026-02-09 05:49:06'),
(14, 14, 1, 'Fall', 2026, 30, 20, '2026-02-09 05:49:06'),
(15, 15, 1, 'Fall', 2026, 50, 48, '2026-02-09 05:49:06'),
(16, 16, 1, 'Fall', 2026, 40, 25, '2026-02-09 05:49:06'),
(17, 17, 1, 'Fall', 2026, 35, 28, '2026-02-09 05:49:06'),
(18, 18, 1, 'Fall', 2026, 45, 40, '2026-02-09 05:49:06'),
(19, 19, 1, 'Fall', 2026, 50, 50, '2026-02-09 05:49:06'),
(20, 20, 1, 'Fall', 2026, 40, 35, '2026-02-09 05:49:06');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `code` varchar(10) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `code`, `name`, `description`, `created_at`) VALUES
(1, 'CS', 'Computer Science', 'Programming and computing systems', '2026-02-09 04:43:31'),
(2, 'ICT', 'Information Technology', 'Networking and IT systems', '2026-02-09 04:43:31'),
(3, 'ECE', 'Electrical Engineering', 'Electrical and power engineering', '2026-02-09 04:43:31'),
(4, 'ME', 'Mechanical Engineering', 'Mechanical systems and design', '2026-02-09 04:43:31'),
(5, 'CE', 'Civil Engineering', 'Construction and infrastructure', '2026-02-09 04:43:31'),
(6, 'AI', 'Artificial Intelligence', 'AI and machine learning', '2026-02-09 04:43:31'),
(7, 'DS', 'Data Science', 'Data analysis and statistics', '2026-02-09 04:43:31'),
(8, 'SE', 'Software Engineering', 'Software design and development', '2026-02-09 04:43:31'),
(9, 'EE', 'Electronics Engineering', 'Electronic circuits and devices', '2026-02-09 04:43:31'),
(10, 'PSW', 'Personal Support Worker', 'Healthcare and patient support', '2026-02-09 04:43:31'),
(11, 'BUS', 'Business Administration', 'Business and management studies', '2026-02-09 04:43:31'),
(12, 'FIN', 'Finance', 'Accounting and finance', '2026-02-09 04:43:31'),
(13, 'HR', 'Human Resources', 'HR and organizational studies', '2026-02-09 04:43:31'),
(14, 'MKT', 'Marketing', 'Marketing and sales strategies', '2026-02-09 04:43:31'),
(15, 'BIO', 'Biotechnology', 'Biological technology', '2026-02-09 04:43:31'),
(16, 'CHE', 'Chemical Engineering', 'Chemical processes', '2026-02-09 04:43:31'),
(17, 'PHY', 'Physics', 'Applied and theoretical physics', '2026-02-09 04:43:31'),
(18, 'MAT', 'Mathematics', 'Pure and applied mathematics', '2026-02-09 04:43:31'),
(19, 'ENG', 'English', 'Language and literature', '2026-02-09 04:43:31'),
(20, 'EDU', 'Education', 'Teaching and pedagogy', '2026-02-09 04:43:31');

-- --------------------------------------------------------

--
-- Table structure for table `hods`
--

CREATE TABLE `hods` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `appointed_date` date NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hods`
--

INSERT INTO `hods` (`id`, `user_id`, `teacher_id`, `department_id`, `appointed_date`, `status`, `created_at`) VALUES
(21, 3, 1, 1, '2025-01-15', 'active', '2026-02-09 05:32:06'),
(22, 4, 2, 2, '2025-02-01', 'active', '2026-02-09 05:32:06'),
(23, 5, 3, 3, '2025-03-01', 'active', '2026-02-09 05:32:06');

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `room_number` varchar(20) NOT NULL,
  `building` varchar(50) NOT NULL,
  `type` enum('lecture','lab','seminar','hybrid') NOT NULL,
  `capacity` int(11) NOT NULL,
  `equipment` text DEFAULT NULL,
  `status` enum('active','inactive','maintenance') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `room_number`, `building`, `type`, `capacity`, `equipment`, `status`, `created_at`) VALUES
(1, 'R101', 'Main Building', 'lecture', 50, 'Projector, Whiteboard', 'active', '2026-02-09 05:54:03'),
(2, 'R102', 'Main Building', 'lab', 30, 'Computers, Projector', 'active', '2026-02-09 05:54:03'),
(3, 'R103', 'Main Building', 'seminar', 20, 'Projector', 'inactive', '2026-02-09 05:54:03'),
(4, 'R104', 'Main Building', 'hybrid', 40, 'Projector, Video Conferencing', 'active', '2026-02-09 05:54:03'),
(5, 'R201', 'Science Block', 'lecture', 60, 'Projector, Whiteboard', 'active', '2026-02-09 05:54:03'),
(6, 'R202', 'Science Block', 'lab', 35, 'Computers, Lab Equipment', 'maintenance', '2026-02-09 05:54:03'),
(7, 'R203', 'Science Block', 'seminar', 25, 'Whiteboard', 'active', '2026-02-09 05:54:03'),
(8, 'R204', 'Science Block', 'hybrid', 45, 'Projector, Computers', 'active', '2026-02-09 05:54:03'),
(9, 'R301', 'Engineering Wing', 'lecture', 70, 'Projector, Whiteboard', 'active', '2026-02-09 05:54:03'),
(10, 'R302', 'Engineering Wing', 'lab', 40, 'Computers, Lab Equipment', 'inactive', '2026-02-09 05:54:03'),
(11, 'R303', 'Engineering Wing', 'seminar', 30, 'Projector', 'active', '2026-02-09 05:54:03'),
(12, 'R304', 'Engineering Wing', 'hybrid', 50, 'Projector, Video Conferencing', 'active', '2026-02-09 05:54:03'),
(13, 'R401', 'Admin Block', 'lecture', 55, 'Projector, Whiteboard', 'active', '2026-02-09 05:54:03'),
(14, 'R402', 'Admin Block', 'lab', 35, 'Computers', 'active', '2026-02-09 05:54:03'),
(15, 'R403', 'Admin Block', 'seminar', 20, 'Projector, Whiteboard', 'inactive', '2026-02-09 05:54:03'),
(16, 'R404', 'Admin Block', 'hybrid', 40, 'Projector, Video Conferencing', 'active', '2026-02-09 05:54:03'),
(17, 'R501', 'Library Wing', 'lecture', 60, 'Projector', 'active', '2026-02-09 05:54:03'),
(18, 'R502', 'Library Wing', 'lab', 25, 'Computers', 'active', '2026-02-09 05:54:03'),
(19, 'R503', 'Library Wing', 'seminar', 15, 'Whiteboard', 'maintenance', '2026-02-09 05:54:03'),
(20, 'R504', 'Library Wing', 'hybrid', 35, 'Projector, Computers', 'active', '2026-02-09 05:54:03');

-- --------------------------------------------------------

--
-- Table structure for table `room_availability`
--

CREATE TABLE `room_availability` (
  `id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `status` enum('free','partial','locked') DEFAULT 'free',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room_availability`
--

INSERT INTO `room_availability` (`id`, `room_id`, `day_of_week`, `start_time`, `end_time`, `status`, `created_at`) VALUES
(1, 1, 'Monday', '08:00:00', '12:00:00', 'free', '2026-02-09 06:01:56'),
(2, 2, 'Monday', '09:00:00', '11:00:00', 'free', '2026-02-09 06:01:56'),
(3, 3, 'Monday', '10:00:00', '12:30:00', 'locked', '2026-02-09 06:01:56'),
(4, 4, 'Tuesday', '08:30:00', '12:30:00', 'free', '2026-02-09 06:01:56'),
(5, 5, 'Tuesday', '09:00:00', '11:30:00', 'free', '2026-02-09 06:01:56'),
(6, 6, 'Tuesday', '10:00:00', '13:00:00', 'partial', '2026-02-09 06:01:56'),
(7, 7, 'Wednesday', '08:00:00', '12:00:00', 'free', '2026-02-09 06:01:56'),
(8, 8, 'Wednesday', '09:00:00', '11:00:00', 'free', '2026-02-09 06:01:56'),
(9, 9, 'Wednesday', '10:00:00', '12:30:00', 'free', '2026-02-09 06:01:56'),
(10, 10, 'Thursday', '08:30:00', '12:30:00', 'free', '2026-02-09 06:01:56'),
(11, 11, 'Thursday', '09:00:00', '11:30:00', 'free', '2026-02-09 06:01:56'),
(12, 12, 'Thursday', '10:00:00', '13:00:00', 'locked', '2026-02-09 06:01:56'),
(13, 13, 'Friday', '08:00:00', '12:00:00', 'free', '2026-02-09 06:01:56'),
(14, 14, 'Friday', '09:00:00', '11:00:00', 'free', '2026-02-09 06:01:56'),
(15, 15, 'Friday', '10:00:00', '12:30:00', 'partial', '2026-02-09 06:01:56'),
(16, 16, 'Monday', '13:00:00', '17:00:00', 'free', '2026-02-09 06:01:56'),
(17, 17, 'Tuesday', '13:00:00', '16:00:00', 'free', '2026-02-09 06:01:56'),
(18, 18, 'Wednesday', '13:00:00', '16:30:00', 'free', '2026-02-09 06:01:56'),
(19, 19, 'Thursday', '14:00:00', '17:00:00', 'free', '2026-02-09 06:01:56'),
(20, 20, 'Friday', '13:00:00', '16:00:00', 'locked', '2026-02-09 06:01:56');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `roll_no` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `semester` int(11) NOT NULL,
  `status` enum('active','inactive','graduated') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `user_id`, `roll_no`, `name`, `email`, `department_id`, `semester`, `status`, `created_at`) VALUES
(1, 21, 'CS202601', 'Aisha Khan', 'aisha.khan@student.com', 1, 3, 'active', '2026-02-09 05:05:11'),
(2, 22, 'CS202602', 'Bob Smith', 'bob.smith@student.com', 1, 2, 'active', '2026-02-09 05:05:11'),
(3, 23, 'ICT202603', 'Kunal Shah', 'kunal.shah@student.com', 2, 4, 'active', '2026-02-09 05:05:11'),
(4, 24, 'ECE202604', 'Diana Prince', 'diana.prince@student.com', 3, 1, 'active', '2026-02-09 05:05:11'),
(5, 25, 'ME202605', 'Vikram Malhotra', 'vikram.malhotra@student.com', 4, 5, 'active', '2026-02-09 05:05:11'),
(6, 26, 'CE202606', 'Ethan Hunt', 'ethan.hunt@student.com', 5, 2, 'active', '2026-02-09 05:05:11'),
(7, 27, 'AI202607', 'Meera Reddy', 'meera.reddy@student.com', 6, 3, 'active', '2026-02-09 05:05:11'),
(8, 28, 'DS202608', 'Lucas Garcia', 'lucas.garcia@student.com', 7, 4, 'active', '2026-02-09 05:05:11'),
(9, 29, 'SE202609', 'Nisha Kapoor', 'nisha.kapoor@student.com', 8, 1, 'active', '2026-02-09 05:05:11'),
(10, 30, 'EE202610', 'Noah Johnson', 'noah.johnson@student.com', 9, 2, 'active', '2026-02-09 05:05:11'),
(11, 31, 'PSW202611', 'Sana Ahmed', 'sana.ahmed@student.com', 10, 1, 'active', '2026-02-09 05:05:11'),
(12, 32, 'BUS202612', 'Henry Martin', 'henry.martin@student.com', 11, 3, 'active', '2026-02-09 05:05:11'),
(13, 33, 'FIN202613', 'Manav Choudhary', 'manav.choudhary@student.com', 12, 4, 'active', '2026-02-09 05:05:11'),
(14, 34, 'HR202614', 'Isabella Rodriguez', 'isabella.rodriguez@student.com', 13, 2, 'active', '2026-02-09 05:05:11'),
(15, 35, 'MKT202615', 'Pooja Bansal', 'pooja.bansal@student.com', 14, 1, 'active', '2026-02-09 05:05:11'),
(16, 36, 'BIO202616', 'Liam Davis', 'liam.davis@student.com', 15, 3, 'active', '2026-02-09 05:05:11'),
(17, 37, 'CHE202617', 'Harsh Vardhan', 'harsh.vardhan@student.com', 16, 2, 'active', '2026-02-09 05:05:11'),
(18, 38, 'PHY202618', 'Amelia Clark', 'amelia.clark@student.com', 17, 4, 'active', '2026-02-09 05:05:11'),
(19, 39, 'MAT202619', 'Sneha Kulkarni', 'sneha.kulkarni@student.com', 18, 1, 'active', '2026-02-09 05:05:11'),
(20, 40, 'ENG202620', 'Benjamin White', 'benjamin.white@student.com', 19, 3, 'active', '2026-02-09 05:05:11');

-- --------------------------------------------------------

--
-- Table structure for table `student_course_registrations`
--

CREATE TABLE `student_course_registrations` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_section_id` int(11) NOT NULL,
  `status` enum('enrolled','waitlisted','dropped') DEFAULT 'enrolled',
  `registered_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_course_registrations`
--

INSERT INTO `student_course_registrations` (`id`, `student_id`, `course_section_id`, `status`, `registered_at`) VALUES
(1, 1, 1, 'enrolled', '2026-02-09 06:18:31'),
(2, 2, 1, 'enrolled', '2026-02-09 06:18:31'),
(3, 3, 2, 'waitlisted', '2026-02-09 06:18:31'),
(4, 4, 2, 'enrolled', '2026-02-09 06:18:31'),
(5, 5, 3, 'enrolled', '2026-02-09 06:18:31'),
(6, 6, 3, 'dropped', '2026-02-09 06:18:31'),
(7, 7, 4, 'enrolled', '2026-02-09 06:18:31'),
(8, 8, 4, 'waitlisted', '2026-02-09 06:18:31'),
(9, 9, 5, 'enrolled', '2026-02-09 06:18:31'),
(10, 10, 5, 'enrolled', '2026-02-09 06:18:31'),
(11, 11, 6, 'enrolled', '2026-02-09 06:18:31'),
(12, 12, 6, 'dropped', '2026-02-09 06:18:31'),
(13, 13, 7, 'enrolled', '2026-02-09 06:18:31'),
(14, 14, 7, 'waitlisted', '2026-02-09 06:18:31'),
(15, 15, 8, 'enrolled', '2026-02-09 06:18:31'),
(16, 16, 8, 'enrolled', '2026-02-09 06:18:31'),
(17, 17, 9, 'waitlisted', '2026-02-09 06:18:31'),
(18, 18, 9, 'enrolled', '2026-02-09 06:18:31'),
(19, 19, 10, 'enrolled', '2026-02-09 06:18:31'),
(20, 20, 10, 'enrolled', '2026-02-09 06:18:31');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `employee_id` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `department_id` int(11) NOT NULL,
  `status` enum('active','inactive','on_leave') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `user_id`, `employee_id`, `name`, `email`, `department_id`, `status`, `created_at`) VALUES
(1, 3, 'EMP1001', 'Rohan Sharma', 'rohan.sharma@hod.com', 1, 'active', '2026-02-09 05:23:19'),
(2, 4, 'EMP1002', 'David Miller', 'david.miller@hod.com', 2, 'active', '2026-02-09 05:23:19'),
(3, 5, 'EMP1003', 'Priya Desai', 'priya.desai@hod.com', 3, 'inactive', '2026-02-09 05:23:19'),
(4, 6, 'EMP1004', 'Ananya Iyer', 'ananya.iyer@professor.com', 4, 'active', '2026-02-09 05:23:19'),
(5, 7, 'EMP1005', 'James Wilson', 'james.wilson@professor.com', 5, 'active', '2026-02-09 05:23:19'),
(6, 8, 'EMP1006', 'Neha Mehta', 'neha.mehta@professor.com', 6, 'active', '2026-02-09 05:23:19'),
(7, 9, 'EMP1007', 'Daniel Anderson', 'daniel.anderson@professor.com', 7, 'inactive', '2026-02-09 05:23:19'),
(8, 10, 'EMP1008', 'Kavya Nair', 'kavya.nair@professor.com', 8, 'active', '2026-02-09 05:23:19'),
(9, 11, 'EMP1009', 'William Jackson', 'william.jackson@professor.com', 9, 'on_leave', '2026-02-09 05:23:19'),
(10, 12, 'EMP1010', 'Ishita Gupta', 'ishita.gupta@professor.com', 10, 'inactive', '2026-02-09 05:23:19'),
(11, 13, 'EMP1011', 'Arjun Singh', 'arjun.singh@professor.com', 11, 'active', '2026-02-09 05:23:19'),
(12, 14, 'EMP1012', 'Sophia Martinez', 'sophia.martinez@professor.com', 12, 'active', '2026-02-09 05:23:19'),
(13, 15, 'EMP1013', 'Aditya Verma', 'aditya.verma@professor.com', 13, 'inactive', '2026-02-09 05:23:19'),
(14, 16, 'EMP1014', 'Mia Thompson', 'mia.thompson@professor.com', 14, 'active', '2026-02-09 05:23:19'),
(15, 17, 'EMP1015', 'Rahul Khanna', 'rahul.khanna@professor.com', 15, 'active', '2026-02-09 05:23:19'),
(16, 18, 'EMP1016', 'Olivia Brown', 'olivia.brown@professor.com', 16, 'active', '2026-02-09 05:23:19'),
(17, 19, 'EMP1017', 'Siddharth Joshi', 'siddharth.joshi@professor.com', 17, 'on_leave', '2026-02-09 05:23:19'),
(18, 20, 'EMP1018', 'Charlotte Taylor', 'charlotte.taylor@professor.com', 18, 'active', '2026-02-09 05:23:19');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_availability`
--

CREATE TABLE `teacher_availability` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `term` varchar(20) NOT NULL,
  `year` int(11) NOT NULL DEFAULT 2026,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `max_hours_per_week` int(11) DEFAULT 40,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_availability`
--

INSERT INTO `teacher_availability` (`id`, `teacher_id`, `term`, `year`, `day_of_week`, `start_time`, `end_time`, `max_hours_per_week`, `created_at`) VALUES
(1, 1, 'Fall', 2026, 'Monday', '09:00:00', '12:00:00', 12, '2026-02-09 05:56:37'),
(2, 2, 'Fall', 2026, 'Tuesday', '10:00:00', '13:00:00', 12, '2026-02-09 05:56:37'),
(3, 3, 'Fall', 2026, 'Wednesday', '08:30:00', '11:30:00', 12, '2026-02-09 05:56:37'),
(4, 4, 'Fall', 2026, 'Thursday', '09:00:00', '12:00:00', 12, '2026-02-09 05:56:37'),
(5, 5, 'Fall', 2026, 'Friday', '10:00:00', '13:00:00', 12, '2026-02-09 05:56:37'),
(6, 6, 'Fall', 2026, 'Monday', '13:00:00', '16:00:00', 12, '2026-02-09 05:56:37'),
(7, 7, 'Fall', 2026, 'Tuesday', '09:30:00', '12:30:00', 12, '2026-02-09 05:56:37'),
(8, 8, 'Fall', 2026, 'Wednesday', '10:00:00', '13:00:00', 12, '2026-02-09 05:56:37'),
(9, 9, 'Fall', 2026, 'Thursday', '08:30:00', '11:30:00', 12, '2026-02-09 05:56:37'),
(10, 10, 'Fall', 2026, 'Friday', '09:00:00', '12:00:00', 12, '2026-02-09 05:56:37'),
(11, 11, 'Fall', 2026, 'Monday', '14:00:00', '17:00:00', 12, '2026-02-09 05:56:37'),
(12, 12, 'Fall', 2026, 'Tuesday', '11:00:00', '14:00:00', 12, '2026-02-09 05:56:37'),
(13, 13, 'Fall', 2026, 'Wednesday', '09:00:00', '12:00:00', 12, '2026-02-09 05:56:37'),
(14, 14, 'Fall', 2026, 'Thursday', '10:00:00', '13:00:00', 12, '2026-02-09 05:56:37'),
(15, 15, 'Fall', 2026, 'Friday', '08:30:00', '11:30:00', 12, '2026-02-09 05:56:37'),
(16, 16, 'Fall', 2026, 'Monday', '09:00:00', '12:00:00', 12, '2026-02-09 05:56:37'),
(17, 17, 'Fall', 2026, 'Tuesday', '10:00:00', '13:00:00', 12, '2026-02-09 05:56:37'),
(18, 18, 'Fall', 2026, 'Wednesday', '11:00:00', '14:00:00', 12, '2026-02-09 05:56:37'),
(19, 1, 'Fall', 2026, 'Thursday', '13:00:00', '16:00:00', 12, '2026-02-09 05:56:37'),
(20, 2, 'Fall', 2026, 'Friday', '14:00:00', '17:00:00', 12, '2026-02-09 05:56:37');

-- --------------------------------------------------------

--
-- Table structure for table `timetables`
--

CREATE TABLE `timetables` (
  `id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `term` varchar(20) NOT NULL,
  `year` int(11) NOT NULL,
  `semester` int(11) NOT NULL,
  `status` enum('draft','active','archived') DEFAULT 'draft',
  `generated_by` int(11) DEFAULT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `conflicts_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `timetables`
--

INSERT INTO `timetables` (`id`, `department_id`, `term`, `year`, `semester`, `status`, `generated_by`, `generated_at`, `conflicts_count`) VALUES
(1, 1, 'Fall', 2026, 1, 'draft', 3, '2026-02-09 06:07:15', 0),
(2, 2, 'Fall', 2026, 1, 'active', 4, '2026-02-09 06:07:15', 1),
(3, 3, 'Fall', 2026, 2, 'draft', 5, '2026-02-09 06:07:15', 0),
(4, 4, 'Fall', 2026, 2, 'archived', 3, '2026-02-09 06:07:15', 2),
(5, 5, 'Fall', 2026, 1, 'active', 4, '2026-02-09 06:07:15', 1),
(6, 6, 'Fall', 2026, 3, 'draft', 5, '2026-02-09 06:07:15', 0),
(7, 7, 'Fall', 2026, 3, 'archived', 3, '2026-02-09 06:07:15', 1),
(8, 8, 'Fall', 2026, 4, 'active', 4, '2026-02-09 06:07:15', 0),
(9, 9, 'Fall', 2026, 4, 'draft', 5, '2026-02-09 06:07:15', 0),
(10, 10, 'Fall', 2026, 2, 'archived', 3, '2026-02-09 06:07:15', 1),
(11, 1, 'Spring', 2027, 1, 'active', 4, '2026-02-09 06:07:15', 0),
(12, 2, 'Spring', 2027, 1, 'draft', 5, '2026-02-09 06:07:15', 0),
(13, 3, 'Spring', 2027, 2, 'active', 3, '2026-02-09 06:07:15', 1),
(14, 4, 'Spring', 2027, 2, 'archived', 4, '2026-02-09 06:07:15', 2),
(15, 5, 'Spring', 2027, 1, 'draft', 5, '2026-02-09 06:07:15', 0),
(16, 6, 'Spring', 2027, 3, 'active', 3, '2026-02-09 06:07:15', 1),
(17, 7, 'Spring', 2027, 3, 'draft', 4, '2026-02-09 06:07:15', 0),
(18, 8, 'Spring', 2027, 4, 'archived', 5, '2026-02-09 06:07:15', 1),
(19, 9, 'Spring', 2027, 4, 'active', 3, '2026-02-09 06:07:15', 0),
(20, 10, 'Spring', 2027, 2, 'draft', 4, '2026-02-09 06:07:15', 0);

-- --------------------------------------------------------

--
-- Table structure for table `timetable_slots`
--

CREATE TABLE `timetable_slots` (
  `id` int(11) NOT NULL,
  `timetable_id` int(11) NOT NULL,
  `course_section_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `component` enum('lecture','lab') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `timetable_slots`
--

INSERT INTO `timetable_slots` (`id`, `timetable_id`, `course_section_id`, `teacher_id`, `room_id`, `day_of_week`, `start_time`, `end_time`, `component`, `created_at`) VALUES
(1, 1, 1, 1, 1, 'Monday', '08:00:00', '09:30:00', 'lecture', '2026-02-09 06:10:03'),
(2, 1, 2, 2, 2, 'Monday', '09:30:00', '11:00:00', 'lecture', '2026-02-09 06:10:03'),
(3, 1, 3, 3, 3, 'Tuesday', '08:00:00', '09:30:00', 'lecture', '2026-02-09 06:10:03'),
(4, 1, 4, 4, 4, 'Tuesday', '09:30:00', '11:00:00', 'lecture', '2026-02-09 06:10:03'),
(5, 1, 1, 5, 5, 'Wednesday', '08:00:00', '09:30:00', 'lab', '2026-02-09 06:10:03'),
(6, 2, 2, 6, 6, 'Wednesday', '09:30:00', '11:00:00', 'lab', '2026-02-09 06:10:03'),
(7, 2, 3, 7, 7, 'Thursday', '08:00:00', '09:30:00', 'lab', '2026-02-09 06:10:03'),
(8, 2, 4, 8, 8, 'Thursday', '09:30:00', '11:00:00', 'lab', '2026-02-09 06:10:03'),
(9, 3, 5, 9, 9, 'Friday', '08:00:00', '09:30:00', 'lecture', '2026-02-09 06:10:03'),
(10, 3, 6, 10, 10, 'Friday', '09:30:00', '11:00:00', 'lecture', '2026-02-09 06:10:03'),
(11, 3, 5, 1, 1, 'Monday', '11:00:00', '12:30:00', 'lab', '2026-02-09 06:10:03'),
(12, 4, 6, 2, 2, 'Tuesday', '11:00:00', '12:30:00', 'lab', '2026-02-09 06:10:03'),
(13, 4, 7, 3, 3, 'Wednesday', '11:00:00', '12:30:00', 'lecture', '2026-02-09 06:10:03'),
(14, 4, 8, 4, 4, 'Thursday', '11:00:00', '12:30:00', 'lecture', '2026-02-09 06:10:03'),
(15, 4, 7, 5, 5, 'Friday', '11:00:00', '12:30:00', 'lab', '2026-02-09 06:10:03'),
(16, 5, 8, 6, 6, 'Monday', '13:00:00', '14:30:00', 'lab', '2026-02-09 06:10:03'),
(17, 5, 9, 7, 7, 'Tuesday', '13:00:00', '14:30:00', 'lecture', '2026-02-09 06:10:03'),
(18, 5, 10, 8, 8, 'Wednesday', '13:00:00', '14:30:00', 'lecture', '2026-02-09 06:10:03'),
(19, 5, 9, 9, 9, 'Thursday', '13:00:00', '14:30:00', 'lab', '2026-02-09 06:10:03'),
(20, 5, 10, 10, 10, 'Friday', '13:00:00', '14:30:00', 'lab', '2026-02-09 06:10:03');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('admin','hod','professor','student') NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `role`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Aarav Patel', '$2y$10$xlWPGz.xqL9aCUXq4vwLCOzzJufou22jYLZncSz.Wv7nZjet1ReyK', 'aarav.patel@admin.com', 'admin', 'active', '2026-02-09 04:45:56', '2026-02-13 08:20:25'),
(2, 'Emily Carter', '$2y$10$xlWPGz.xqL9aCUXq4vwLCOzzJufou22jYLZncSz.Wv7nZjet1ReyK', 'emily.carter@admin.com', 'admin', 'active', '2026-02-09 04:45:56', '2026-02-13 08:20:25'),
(3, 'Rohan Sharma', '$2y$10$xlWPGz.xqL9aCUXq4vwLCOzzJufou22jYLZncSz.Wv7nZjet1ReyK', 'rohan.sharma@hod.com', 'hod', 'active', '2026-02-09 04:45:56', '2026-02-13 08:20:25'),
(4, 'David Miller', '$2y$10$xlWPGz.xqL9aCUXq4vwLCOzzJufou22jYLZncSz.Wv7nZjet1ReyK', 'david.miller@hod.com', 'hod', 'active', '2026-02-09 04:45:56', '2026-02-13 08:20:25'),
(5, 'Priya Desai', '$2y$10$xlWPGz.xqL9aCUXq4vwLCOzzJufou22jYLZncSz.Wv7nZjet1ReyK', 'priya.desai@hod.com', 'hod', 'active', '2026-02-09 04:45:56', '2026-02-13 08:20:25'),
(6, 'Ananya Iyer', '$2y$10$xlWPGz.xqL9aCUXq4vwLCOzzJufou22jYLZncSz.Wv7nZjet1ReyK', 'ananya.iyer@professor.com', 'professor', 'active', '2026-02-09 04:45:56', '2026-02-13 08:20:25'),
(7, 'James Wilson', '$2y$10$xlWPGz.xqL9aCUXq4vwLCOzzJufou22jYLZncSz.Wv7nZjet1ReyK', 'james.wilson@professor.com', 'professor', 'active', '2026-02-09 04:45:56', '2026-02-13 08:20:25'),
(8, 'Neha Mehta', '$2y$10$xlWPGz.xqL9aCUXq4vwLCOzzJufou22jYLZncSz.Wv7nZjet1ReyK', 'neha.mehta@professor.com', 'professor', 'active', '2026-02-09 04:45:56', '2026-02-13 08:20:25'),
(9, 'Daniel Anderson', '$2y$10$xlWPGz.xqL9aCUXq4vwLCOzzJufou22jYLZncSz.Wv7nZjet1ReyK', 'daniel.anderson@professor.com', 'professor', 'active', '2026-02-09 04:45:56', '2026-02-13 08:20:25'),
(10, 'Kavya Nair', '$2y$10$xlWPGz.xqL9aCUXq4vwLCOzzJufou22jYLZncSz.Wv7nZjet1ReyK', 'kavya.nair@professor.com', 'professor', 'active', '2026-02-09 04:45:56', '2026-02-13 08:20:25'),
(11, 'William Jackson', '$2y$10$xlWPGz.xqL9aCUXq4vwLCOzzJufou22jYLZncSz.Wv7nZjet1ReyK', 'william.jackson@professor.com', 'professor', 'active', '2026-02-09 04:45:56', '2026-02-13 08:20:25'),
(12, 'Ishita Gupta', '$2y$10$xlWPGz.xqL9aCUXq4vwLCOzzJufou22jYLZncSz.Wv7nZjet1ReyK', 'ishita.gupta@professor.com', 'professor', 'active', '2026-02-09 04:45:56', '2026-02-13 08:20:25'),
(13, 'Arjun Singh', '$2y$10$xlWPGz.xqL9aCUXq4vwLCOzzJufou22jYLZncSz.Wv7nZjet1ReyK', 'arjun.singh@professor.com', 'professor', 'active', '2026-02-09 04:45:56', '2026-02-13 08:20:25'),
(14, 'Sophia Martinez', '$2y$10$xlWPGz.xqL9aCUXq4vwLCOzzJufou22jYLZncSz.Wv7nZjet1ReyK', 'sophia.martinez@professor.com', 'professor', 'active', '2026-02-09 04:45:56', '2026-02-13 08:20:25'),
(15, 'Aditya Verma', '$2y$10$xlWPGz.xqL9aCUXq4vwLCOzzJufou22jYLZncSz.Wv7nZjet1ReyK', 'aditya.verma@professor.com', 'professor', 'active', '2026-02-09 04:45:56', '2026-02-13 08:20:25'),
(16, 'Mia Thompson', '$2y$10$xlWPGz.xqL9aCUXq4vwLCOzzJufou22jYLZncSz.Wv7nZjet1ReyK', 'mia.thompson@professor.com', 'professor', 'active', '2026-02-09 04:45:56', '2026-02-13 08:20:25'),
(17, 'Rahul Khanna', '$2y$10$xlWPGz.xqL9aCUXq4vwLCOzzJufou22jYLZncSz.Wv7nZjet1ReyK', 'rahul.khanna@professor.com', 'professor', 'active', '2026-02-09 04:45:56', '2026-02-13 08:20:25'),
(18, 'Olivia Brown', '$2y$10$xlWPGz.xqL9aCUXq4vwLCOzzJufou22jYLZncSz.Wv7nZjet1ReyK', 'olivia.brown@professor.com', 'professor', 'active', '2026-02-09 04:45:56', '2026-02-13 08:20:25'),
(19, 'Siddharth Joshi', '$2y$10$xlWPGz.xqL9aCUXq4vwLCOzzJufou22jYLZncSz.Wv7nZjet1ReyK', 'siddharth.joshi@professor.com', 'professor', 'active', '2026-02-09 04:45:56', '2026-02-13 08:20:25'),
(20, 'Charlotte Taylor', '$2y$10$xlWPGz.xqL9aCUXq4vwLCOzzJufou22jYLZncSz.Wv7nZjet1ReyK', 'charlotte.taylor@professor.com', 'professor', 'active', '2026-02-09 04:45:56', '2026-02-13 08:20:25'),
(21, 'Aisha Khan', '$2y$10$xlWPGz.xqL9aCUXq4vwLCOzzJufou22jYLZncSz.Wv7nZjet1ReyK', 'aisha.khan@student.com', 'student', 'active', '2026-02-10 03:22:33', '2026-02-13 08:20:25'),
(22, 'Bob Smith', '$2y$10$xlWPGz.xqL9aCUXq4vwLCOzzJufou22jYLZncSz.Wv7nZjet1ReyK', 'bob.smith@student.com', 'student', 'active', '2026-02-10 03:22:33', '2026-02-13 08:20:25'),
(23, 'Kunal Shah', '$2y$10$xlWPGz.xqL9aCUXq4vwLCOzzJufou22jYLZncSz.Wv7nZjet1ReyK', 'kunal.shah@student.com', 'student', 'active', '2026-02-10 03:22:33', '2026-02-13 08:20:25'),
(24, 'Diana Prince', '$2y$10$xlWPGz.xqL9aCUXq4vwLCOzzJufou22jYLZncSz.Wv7nZjet1ReyK', 'diana.prince@student.com', 'student', 'active', '2026-02-10 03:22:33', '2026-02-13 08:20:25'),
(25, 'Vikram Malhotra', '$2y$10$xlWPGz.xqL9aCUXq4vwLCOzzJufou22jYLZncSz.Wv7nZjet1ReyK', 'vikram.malhotra@student.com', 'student', 'active', '2026-02-10 03:22:33', '2026-02-13 08:20:25'),
(26, 'Ethan Hunt', '$2y$10$xlWPGz.xqL9aCUXq4vwLCOzzJufou22jYLZncSz.Wv7nZjet1ReyK', 'ethan.hunt@student.com', 'student', 'active', '2026-02-10 03:22:33', '2026-02-13 08:20:25'),
(27, 'Meera Reddy', '$2y$10$xlWPGz.xqL9aCUXq4vwLCOzzJufou22jYLZncSz.Wv7nZjet1ReyK', 'meera.reddy@student.com', 'student', 'active', '2026-02-10 03:22:33', '2026-02-13 08:20:25'),
(28, 'Lucas Garcia', '$2y$10$xlWPGz.xqL9aCUXq4vwLCOzzJufou22jYLZncSz.Wv7nZjet1ReyK', 'lucas.garcia@student.com', 'student', 'active', '2026-02-10 03:22:33', '2026-02-13 08:20:25'),
(29, 'Nisha Kapoor', '$2y$10$xlWPGz.xqL9aCUXq4vwLCOzzJufou22jYLZncSz.Wv7nZjet1ReyK', 'nisha.kapoor@student.com', 'student', 'active', '2026-02-10 03:22:33', '2026-02-13 08:20:25'),
(30, 'Noah Johnson', '$2y$10$xlWPGz.xqL9aCUXq4vwLCOzzJufou22jYLZncSz.Wv7nZjet1ReyK', 'noah.johnson@student.com', 'student', 'active', '2026-02-10 03:22:33', '2026-02-13 08:20:25'),
(31, 'Sana Ahmed', '$2y$10$xlWPGz.xqL9aCUXq4vwLCOzzJufou22jYLZncSz.Wv7nZjet1ReyK', 'sana.ahmed@student.com', 'student', 'active', '2026-02-10 03:22:33', '2026-02-13 08:20:25'),
(32, 'Henry Martin', '$2y$10$xlWPGz.xqL9aCUXq4vwLCOzzJufou22jYLZncSz.Wv7nZjet1ReyK', 'henry.martin@student.com', 'student', 'active', '2026-02-10 03:22:33', '2026-02-13 08:20:25'),
(33, 'Manav Choudhary', '$2y$10$xlWPGz.xqL9aCUXq4vwLCOzzJufou22jYLZncSz.Wv7nZjet1ReyK', 'manav.choudhary@student.com', 'student', 'active', '2026-02-10 03:22:33', '2026-02-13 08:20:25'),
(34, 'Isabella Rodriguez', '$2y$10$xlWPGz.xqL9aCUXq4vwLCOzzJufou22jYLZncSz.Wv7nZjet1ReyK', 'isabella.rodriguez@student.com', 'student', 'active', '2026-02-10 03:22:33', '2026-02-13 08:20:25'),
(35, 'Pooja Bansal', '$2y$10$xlWPGz.xqL9aCUXq4vwLCOzzJufou22jYLZncSz.Wv7nZjet1ReyK', 'pooja.bansal@student.com', 'student', 'active', '2026-02-10 03:22:33', '2026-02-13 08:20:25'),
(36, 'Liam Davis', '$2y$10$xlWPGz.xqL9aCUXq4vwLCOzzJufou22jYLZncSz.Wv7nZjet1ReyK', 'liam.davis@student.com', 'student', 'active', '2026-02-10 03:22:33', '2026-02-13 08:20:25'),
(37, 'Harsh Vardhan', '$2y$10$xlWPGz.xqL9aCUXq4vwLCOzzJufou22jYLZncSz.Wv7nZjet1ReyK', 'harsh.vardhan@student.com', 'student', 'active', '2026-02-10 03:22:33', '2026-02-13 08:20:25'),
(38, 'Amelia Clark', '$2y$10$xlWPGz.xqL9aCUXq4vwLCOzzJufou22jYLZncSz.Wv7nZjet1ReyK', 'amelia.clark@student.com', 'student', 'active', '2026-02-10 03:22:33', '2026-02-13 08:20:25'),
(39, 'Sneha Kulkarni', '$2y$10$xlWPGz.xqL9aCUXq4vwLCOzzJufou22jYLZncSz.Wv7nZjet1ReyK', 'sneha.kulkarni@student.com', 'student', 'active', '2026-02-10 03:22:33', '2026-02-13 08:20:25'),
(40, 'Benjamin White', '$2y$10$xlWPGz.xqL9aCUXq4vwLCOzzJufou22jYLZncSz.Wv7nZjet1ReyK', 'benjamin.white@student.com', 'student', 'active', '2026-02-10 03:22:33', '2026-02-13 08:20:25');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `conflicts`
--
ALTER TABLE `conflicts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `timetable_id` (`timetable_id`),
  ADD KEY `slot_id_1` (`slot_id_1`),
  ADD KEY `slot_id_2` (`slot_id_2`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `course_assignments`
--
ALTER TABLE `course_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `course_section_id` (`course_section_id`,`component`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `course_sections`
--
ALTER TABLE `course_sections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `course_id` (`course_id`,`section_number`,`term`,`year`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `hods`
--
ALTER TABLE `hods`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `room_number` (`room_number`);

--
-- Indexes for table `room_availability`
--
ALTER TABLE `room_availability`
  ADD PRIMARY KEY (`id`),
  ADD KEY `room_id` (`room_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roll_no` (`roll_no`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `student_course_registrations`
--
ALTER TABLE `student_course_registrations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `course_section_id` (`course_section_id`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `teacher_availability`
--
ALTER TABLE `teacher_availability`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `timetables`
--
ALTER TABLE `timetables`
  ADD PRIMARY KEY (`id`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `generated_by` (`generated_by`);

--
-- Indexes for table `timetable_slots`
--
ALTER TABLE `timetable_slots`
  ADD PRIMARY KEY (`id`),
  ADD KEY `timetable_id` (`timetable_id`),
  ADD KEY `course_section_id` (`course_section_id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `room_id` (`room_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `conflicts`
--
ALTER TABLE `conflicts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `course_assignments`
--
ALTER TABLE `course_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `course_sections`
--
ALTER TABLE `course_sections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `hods`
--
ALTER TABLE `hods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `room_availability`
--
ALTER TABLE `room_availability`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `student_course_registrations`
--
ALTER TABLE `student_course_registrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `teacher_availability`
--
ALTER TABLE `teacher_availability`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `timetables`
--
ALTER TABLE `timetables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `timetable_slots`
--
ALTER TABLE `timetable_slots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `conflicts`
--
ALTER TABLE `conflicts`
  ADD CONSTRAINT `conflicts_ibfk_1` FOREIGN KEY (`timetable_id`) REFERENCES `timetables` (`id`),
  ADD CONSTRAINT `conflicts_ibfk_2` FOREIGN KEY (`slot_id_1`) REFERENCES `timetable_slots` (`id`),
  ADD CONSTRAINT `conflicts_ibfk_3` FOREIGN KEY (`slot_id_2`) REFERENCES `timetable_slots` (`id`);

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`);

--
-- Constraints for table `course_assignments`
--
ALTER TABLE `course_assignments`
  ADD CONSTRAINT `course_assignments_ibfk_1` FOREIGN KEY (`course_section_id`) REFERENCES `course_sections` (`id`),
  ADD CONSTRAINT `course_assignments_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`);

--
-- Constraints for table `course_sections`
--
ALTER TABLE `course_sections`
  ADD CONSTRAINT `course_sections_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`);

--
-- Constraints for table `hods`
--
ALTER TABLE `hods`
  ADD CONSTRAINT `hods_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `hods_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`),
  ADD CONSTRAINT `hods_ibfk_3` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`);

--
-- Constraints for table `room_availability`
--
ALTER TABLE `room_availability`
  ADD CONSTRAINT `room_availability_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`);

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`);

--
-- Constraints for table `student_course_registrations`
--
ALTER TABLE `student_course_registrations`
  ADD CONSTRAINT `student_course_registrations_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  ADD CONSTRAINT `student_course_registrations_ibfk_2` FOREIGN KEY (`course_section_id`) REFERENCES `course_sections` (`id`);

--
-- Constraints for table `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `teachers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `teachers_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`);

--
-- Constraints for table `teacher_availability`
--
ALTER TABLE `teacher_availability`
  ADD CONSTRAINT `teacher_availability_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`);

--
-- Constraints for table `timetables`
--
ALTER TABLE `timetables`
  ADD CONSTRAINT `timetables_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  ADD CONSTRAINT `timetables_ibfk_2` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `timetable_slots`
--
ALTER TABLE `timetable_slots`
  ADD CONSTRAINT `timetable_slots_ibfk_1` FOREIGN KEY (`timetable_id`) REFERENCES `timetables` (`id`),
  ADD CONSTRAINT `timetable_slots_ibfk_2` FOREIGN KEY (`course_section_id`) REFERENCES `course_sections` (`id`),
  ADD CONSTRAINT `timetable_slots_ibfk_3` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`),
  ADD CONSTRAINT `timetable_slots_ibfk_4` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
