-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 06, 2026 at 01:11 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

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
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `entity_type` varchar(255) DEFAULT NULL,
  `entity_id` bigint(20) UNSIGNED DEFAULT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `entity_type`, `entity_id`, `details`, `created_at`) VALUES
(1, 2, 'generate_timetable', 'timetable', 1, 'Generated timetable for dept=1 sem=1 Fall/2026. Scheduled=3 Unscheduled=0 Conflicts=0', '2026-04-06 02:35:42'),
(2, 2, 'generate_timetable', 'timetable', 2, 'Generated timetable for dept=1 sem=2 Fall/2026. Scheduled=3 Unscheduled=0 Conflicts=0', '2026-04-06 02:36:01'),
(3, 2, 'activate_timetable', 'timetable', 1, 'Activated timetable ID 1', '2026-04-06 02:36:06'),
(4, 2, 'activate_timetable', 'timetable', 2, 'Activated timetable ID 2', '2026-04-06 02:36:18'),
(5, 2, 'generate_timetable', 'timetable', 3, 'Generated timetable for dept=1 sem=3 Fall/2026. Scheduled=3 Unscheduled=0 Conflicts=0', '2026-04-06 02:36:28'),
(6, 2, 'activate_timetable', 'timetable', 3, 'Activated timetable ID 3', '2026-04-06 02:36:30'),
(7, 2, 'generate_timetable', 'timetable', 4, 'Generated timetable for dept=1 sem=4 Fall/2026. Scheduled=3 Unscheduled=0 Conflicts=0', '2026-04-06 02:37:06'),
(8, 2, 'activate_timetable', 'timetable', 4, 'Activated timetable ID 4', '2026-04-06 02:37:10'),
(9, 2, 'generate_timetable', 'timetable', 5, 'Generated timetable for dept=1 sem=5 Fall/2026. Scheduled=3 Unscheduled=0 Conflicts=0', '2026-04-06 02:37:22'),
(10, 2, 'activate_timetable', 'timetable', 5, 'Activated timetable ID 5', '2026-04-06 02:37:25'),
(11, 2, 'generate_timetable', 'timetable', 6, 'Generated timetable for dept=1 sem=6 Fall/2026. Scheduled=3 Unscheduled=0 Conflicts=0', '2026-04-06 02:37:37'),
(12, 2, 'activate_timetable', 'timetable', 6, 'Activated timetable ID 6', '2026-04-06 02:37:40'),
(13, 2, 'generate_timetable', 'timetable', 7, 'Generated timetable for dept=1 sem=6 Fall/2026. Scheduled=3 Unscheduled=0 Conflicts=0', '2026-04-06 02:38:13'),
(14, 2, 'update_timetable_slot', 'timetable_slot', 16, 'Manual reschedule: day=Monday 11:20:00–12:50:00 room=1 → day=Thursday 11:20:00–12:50:00 room=1', '2026-04-06 02:38:49'),
(15, 2, 'delete_timetable', 'timetable', 7, 'Deleted timetable ID 7', '2026-04-06 02:39:16'),
(16, 2, 'generate_timetable', 'timetable', 8, 'Generated timetable for dept=1 sem=7 Fall/2026. Scheduled=4 Unscheduled=0 Conflicts=0', '2026-04-06 02:39:39'),
(17, 2, 'activate_timetable', 'timetable', 8, 'Activated timetable ID 8', '2026-04-06 02:39:43'),
(18, 2, 'generate_timetable', 'timetable', 9, 'Generated timetable for dept=1 sem=8 Fall/2026. Scheduled=4 Unscheduled=0 Conflicts=0', '2026-04-06 02:39:58'),
(19, 2, 'activate_timetable', 'timetable', 9, 'Activated timetable ID 9', '2026-04-06 02:40:03');

-- --------------------------------------------------------

--
-- Table structure for table `conflicts`
--

CREATE TABLE `conflicts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `timetable_id` bigint(20) UNSIGNED NOT NULL,
  `conflict_type` enum('room_conflict','teacher_conflict') NOT NULL,
  `description` text NOT NULL,
  `slot_id_1` bigint(20) UNSIGNED DEFAULT NULL,
  `slot_id_2` bigint(20) UNSIGNED DEFAULT NULL,
  `status` enum('unresolved','resolved') NOT NULL DEFAULT 'unresolved',
  `detected_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `department_id` bigint(20) UNSIGNED NOT NULL,
  `semester` tinyint(3) UNSIGNED DEFAULT NULL,
  `prerequisite_course_code` varchar(255) DEFAULT NULL,
  `prerequisite_mandatory` tinyint(1) NOT NULL DEFAULT 0,
  `fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `credits` int(11) NOT NULL,
  `weekly_sessions` tinyint(3) UNSIGNED DEFAULT NULL COMMENT 'Override: how many 90-min sessions per week this course needs. NULL = derive from credits.',
  `type` enum('theory','lab','hybrid') NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `code`, `name`, `department_id`, `semester`, `prerequisite_course_code`, `prerequisite_mandatory`, `fee`, `credits`, `weekly_sessions`, `type`, `description`, `status`, `created_at`) VALUES
(1, 'CS101', 'Introduction to Programming', 1, 1, NULL, 0, 50.00, 3, NULL, 'theory', 'Fundamentals of programming using Python', 'active', '2024-01-15 05:00:00'),
(2, 'CS102', 'Mathematics I', 1, 1, NULL, 0, 50.00, 3, NULL, 'theory', 'Calculus and linear algebra for computer science', 'active', '2024-01-15 05:00:00'),
(3, 'CS103', 'English Communication', 1, 1, NULL, 0, 30.00, 2, NULL, 'theory', 'Technical writing and communication skills', 'active', '2024-01-15 05:00:00'),
(4, 'CS201', 'Data Structures', 1, 2, NULL, 0, 60.00, 3, NULL, 'lab', 'Arrays, linked lists, trees and graphs', 'active', '2024-01-15 05:00:00'),
(5, 'CS202', 'Mathematics II', 1, 2, NULL, 0, 50.00, 3, NULL, 'theory', 'Differential equations and discrete mathematics', 'active', '2024-01-15 05:00:00'),
(6, 'CS203', 'Discrete Mathematics', 1, 2, NULL, 0, 50.00, 3, NULL, 'theory', 'Logic, sets, combinatorics and graph theory', 'active', '2024-01-15 05:00:00'),
(7, 'CS301', 'Algorithm Design', 1, 3, NULL, 0, 60.00, 3, NULL, 'theory', 'Algorithm analysis and design paradigms', 'active', '2024-01-15 05:00:00'),
(8, 'CS302', 'Database Systems', 1, 3, NULL, 0, 60.00, 3, NULL, 'lab', 'Relational databases, SQL and normalization', 'active', '2024-01-15 05:00:00'),
(9, 'CS303', 'Operating Systems', 1, 3, NULL, 0, 50.00, 3, NULL, 'theory', 'Process management, memory and file systems', 'active', '2024-01-15 05:00:00'),
(10, 'CS401', 'Computer Networks', 1, 4, NULL, 0, 60.00, 3, NULL, 'theory', 'Network protocols, TCP/IP and security basics', 'active', '2024-01-15 05:00:00'),
(11, 'CS402', 'Software Engineering', 1, 4, NULL, 0, 50.00, 3, NULL, 'theory', 'Software development lifecycle and methodologies', 'active', '2024-01-15 05:00:00'),
(12, 'CS403', 'Web Development', 1, 4, NULL, 0, 60.00, 3, NULL, 'lab', 'Full-stack web development with frameworks', 'active', '2024-01-15 05:00:00'),
(13, 'CS501', 'Distributed Systems', 1, 5, NULL, 0, 60.00, 3, NULL, 'theory', 'Distributed computing principles and frameworks', 'active', '2024-01-15 05:00:00'),
(14, 'CS502', 'Machine Learning', 1, 5, NULL, 0, 60.00, 3, NULL, 'theory', 'Supervised and unsupervised learning algorithms', 'active', '2024-01-15 05:00:00'),
(15, 'CS503', 'Mobile App Development', 1, 5, NULL, 0, 60.00, 3, NULL, 'lab', 'iOS and Android application development', 'active', '2024-01-15 05:00:00'),
(16, 'CS601', 'Artificial Intelligence', 1, 6, NULL, 0, 70.00, 3, NULL, 'theory', 'Search algorithms and knowledge representation', 'active', '2024-01-15 05:00:00'),
(17, 'CS602', 'Cloud Computing', 1, 6, NULL, 0, 60.00, 3, NULL, 'theory', 'Cloud architecture, AWS, Azure and GCP', 'active', '2024-01-15 05:00:00'),
(18, 'CS603', 'Cybersecurity', 1, 6, NULL, 0, 60.00, 3, NULL, 'theory', 'Network security, cryptography and ethical hacking', 'active', '2024-01-15 05:00:00'),
(19, 'CS701', 'Research Methodology', 1, 7, NULL, 0, 50.00, 3, NULL, 'theory', 'Research design and academic writing', 'active', '2024-01-15 05:00:00'),
(20, 'CS702', 'Capstone Project I', 1, 7, NULL, 0, 80.00, 4, NULL, 'hybrid', 'First phase of the final year project', 'active', '2024-01-15 05:00:00'),
(21, 'CS703', 'Advanced Elective I', 1, 7, NULL, 0, 60.00, 3, NULL, 'theory', 'Selected topics in advanced computer science', 'active', '2024-01-15 05:00:00'),
(22, 'CS801', 'Capstone Project II', 1, 8, NULL, 0, 80.00, 4, NULL, 'hybrid', 'Final phase of the capstone project', 'active', '2024-01-15 05:00:00'),
(23, 'CS802', 'Industry Internship', 1, 8, NULL, 0, 50.00, 3, NULL, 'theory', 'Supervised industry attachment and report', 'active', '2024-01-15 05:00:00'),
(24, 'CS803', 'Advanced Elective II', 1, 8, NULL, 0, 60.00, 3, NULL, 'theory', 'Selected advanced topics in computer science', 'active', '2024-01-15 05:00:00'),
(25, 'IT101', 'Fundamentals of IT', 2, 1, NULL, 0, 50.00, 3, NULL, 'theory', 'Introduction to IT concepts and systems', 'active', '2024-01-15 05:00:00'),
(26, 'IT102', 'Mathematics for IT', 2, 1, NULL, 0, 50.00, 3, NULL, 'theory', 'Applied mathematics for IT professionals', 'active', '2024-01-15 05:00:00'),
(27, 'IT103', 'Technical Communication', 2, 1, NULL, 0, 30.00, 2, NULL, 'theory', 'Professional writing and presentation skills', 'active', '2024-01-15 05:00:00'),
(28, 'IT201', 'Object-Oriented Programming', 2, 2, NULL, 0, 60.00, 3, NULL, 'lab', 'OOP concepts using Java', 'active', '2024-01-15 05:00:00'),
(29, 'IT202', 'Web Technologies', 2, 2, NULL, 0, 60.00, 3, NULL, 'lab', 'HTML, CSS, JavaScript fundamentals', 'active', '2024-01-15 05:00:00'),
(30, 'IT203', 'Networking Fundamentals', 2, 2, NULL, 0, 50.00, 3, NULL, 'theory', 'Network topologies, protocols and OSI model', 'active', '2024-01-15 05:00:00'),
(31, 'IT301', 'Database Management', 2, 3, NULL, 0, 60.00, 3, NULL, 'lab', 'Database design and SQL programming', 'active', '2024-01-15 05:00:00'),
(32, 'IT302', 'Systems Analysis', 2, 3, NULL, 0, 50.00, 3, NULL, 'theory', 'Requirements gathering and system design', 'active', '2024-01-15 05:00:00'),
(33, 'IT303', 'Information Security', 2, 3, NULL, 0, 50.00, 3, NULL, 'theory', 'Fundamentals of cybersecurity and data protection', 'active', '2024-01-15 05:00:00'),
(34, 'IT401', 'Software Testing', 2, 4, NULL, 0, 60.00, 3, NULL, 'theory', 'Test planning, execution and automation basics', 'active', '2024-01-15 05:00:00'),
(35, 'IT402', 'Human Computer Interaction', 2, 4, NULL, 0, 50.00, 3, NULL, 'theory', 'UI/UX design principles and usability', 'active', '2024-01-15 05:00:00'),
(36, 'IT403', 'Enterprise Systems', 2, 4, NULL, 0, 60.00, 3, NULL, 'theory', 'ERP systems and enterprise architecture', 'active', '2024-01-15 05:00:00'),
(37, 'IT501', 'Cloud Services', 2, 5, NULL, 0, 60.00, 3, NULL, 'theory', 'Cloud deployment models and service providers', 'active', '2024-01-15 05:00:00'),
(38, 'IT502', 'Network Security', 2, 5, NULL, 0, 60.00, 3, NULL, 'theory', 'Advanced cybersecurity techniques and protocols', 'active', '2024-01-15 05:00:00'),
(39, 'IT503', 'DevOps Practices', 2, 5, NULL, 0, 60.00, 3, NULL, 'lab', 'CI/CD pipelines, Docker and Kubernetes', 'active', '2024-01-15 05:00:00'),
(40, 'IT601', 'Big Data Analytics', 2, 6, NULL, 0, 70.00, 3, NULL, 'lab', 'Hadoop, Spark and data pipeline tools', 'active', '2024-01-15 05:00:00'),
(41, 'IT602', 'IT Project Management', 2, 6, NULL, 0, 50.00, 3, NULL, 'theory', 'Agile, Scrum and IT project delivery', 'active', '2024-01-15 05:00:00'),
(42, 'IT603', 'Emerging Technologies', 2, 6, NULL, 0, 60.00, 3, NULL, 'theory', 'Blockchain, IoT and emerging digital trends', 'active', '2024-01-15 05:00:00'),
(43, 'IT701', 'Research in IT', 2, 7, NULL, 0, 50.00, 3, NULL, 'theory', 'IT research methods and case studies', 'active', '2024-01-15 05:00:00'),
(44, 'IT702', 'Final Year Project I', 2, 7, NULL, 0, 80.00, 4, NULL, 'hybrid', 'First phase of the final year IT project', 'active', '2024-01-15 05:00:00'),
(45, 'IT703', 'IT Elective I', 2, 7, NULL, 0, 60.00, 3, NULL, 'theory', 'Specialized topics in information technology', 'active', '2024-01-15 05:00:00'),
(46, 'IT801', 'Final Year Project II', 2, 8, NULL, 0, 80.00, 4, NULL, 'hybrid', 'Final phase of the IT project', 'active', '2024-01-15 05:00:00'),
(47, 'IT802', 'Industry Training', 2, 8, NULL, 0, 50.00, 3, NULL, 'theory', 'Industry attachment and practical training', 'active', '2024-01-15 05:00:00'),
(48, 'IT803', 'IT Elective II', 2, 8, NULL, 0, 60.00, 3, NULL, 'theory', 'Advanced specialized IT topics', 'active', '2024-01-15 05:00:00'),
(49, 'EE101', 'Circuit Theory I', 3, 1, NULL, 0, 50.00, 3, NULL, 'theory', 'Basic circuit analysis and Ohm\'s law', 'active', '2024-01-15 05:00:00'),
(50, 'EE102', 'Mathematics for Engineers', 3, 1, NULL, 0, 50.00, 3, NULL, 'theory', 'Calculus and linear algebra for engineers', 'active', '2024-01-15 05:00:00'),
(51, 'EE201', 'Circuit Theory II', 3, 2, NULL, 0, 60.00, 3, NULL, 'lab', 'AC circuits, phasors and resonance', 'active', '2024-01-15 05:00:00'),
(52, 'EE202', 'Electromagnetic Fields', 3, 2, NULL, 0, 50.00, 3, NULL, 'theory', 'Electric and magnetic field theory', 'active', '2024-01-15 05:00:00'),
(53, 'EE301', 'Digital Electronics', 3, 3, NULL, 0, 60.00, 3, NULL, 'lab', 'Logic gates, flip flops and digital circuits', 'active', '2024-01-15 05:00:00'),
(54, 'EE302', 'Signal Processing', 3, 3, NULL, 0, 60.00, 3, NULL, 'theory', 'Fourier analysis and signal filtering', 'active', '2024-01-15 05:00:00'),
(55, 'EE401', 'Power Systems I', 3, 4, NULL, 0, 60.00, 3, NULL, 'theory', 'Power generation, transmission and distribution', 'active', '2024-01-15 05:00:00'),
(56, 'EE402', 'Control Systems', 3, 4, NULL, 0, 60.00, 3, NULL, 'theory', 'Transfer functions, Bode plots and PID control', 'active', '2024-01-15 05:00:00'),
(57, 'EE501', 'Power Systems II', 3, 5, NULL, 0, 60.00, 3, NULL, 'theory', 'Advanced power systems analysis and design', 'active', '2024-01-15 05:00:00'),
(58, 'EE502', 'Electrical Machines', 3, 5, NULL, 0, 60.00, 3, NULL, 'lab', 'Motors, generators and transformers', 'active', '2024-01-15 05:00:00'),
(59, 'EE601', 'Renewable Energy Systems', 3, 6, NULL, 0, 70.00, 3, NULL, 'theory', 'Solar, wind and hybrid energy systems', 'active', '2024-01-15 05:00:00'),
(60, 'EE602', 'Industrial Electronics', 3, 6, NULL, 0, 60.00, 3, NULL, 'lab', 'Power electronics and variable speed drives', 'active', '2024-01-15 05:00:00'),
(61, 'EE701', 'Power Electronics', 3, 7, NULL, 0, 60.00, 3, NULL, 'theory', 'Advanced power conversion and control', 'active', '2024-01-15 05:00:00'),
(62, 'EE702', 'Final Project I', 3, 7, NULL, 0, 80.00, 4, NULL, 'hybrid', 'First phase of the electrical engineering project', 'active', '2024-01-15 05:00:00'),
(63, 'EE801', 'Electrical Systems Design', 3, 8, NULL, 0, 70.00, 3, NULL, 'theory', 'Design of complex electrical systems', 'active', '2024-01-15 05:00:00'),
(64, 'EE802', 'Final Project II', 3, 8, NULL, 0, 80.00, 4, NULL, 'hybrid', 'Final phase of the electrical engineering project', 'active', '2024-01-15 05:00:00'),
(65, 'ME101', 'Engineering Mechanics', 4, 1, NULL, 0, 50.00, 3, NULL, 'theory', 'Statics and dynamics fundamentals', 'active', '2024-01-15 05:00:00'),
(66, 'ME102', 'Engineering Mathematics', 4, 1, NULL, 0, 50.00, 3, NULL, 'theory', 'Mathematics for mechanical engineering', 'active', '2024-01-15 05:00:00'),
(67, 'ME201', 'Thermodynamics I', 4, 2, NULL, 0, 60.00, 3, NULL, 'theory', 'Energy, heat and work principles', 'active', '2024-01-15 05:00:00'),
(68, 'ME202', 'Material Science', 4, 2, NULL, 0, 50.00, 3, NULL, 'theory', 'Properties and selection of engineering materials', 'active', '2024-01-15 05:00:00'),
(69, 'ME301', 'Fluid Mechanics', 4, 3, NULL, 0, 60.00, 3, NULL, 'lab', 'Fluid statics, dynamics and pipe flow', 'active', '2024-01-15 05:00:00'),
(70, 'ME302', 'Thermodynamics II', 4, 3, NULL, 0, 60.00, 3, NULL, 'theory', 'Advanced thermodynamic cycles and applications', 'active', '2024-01-15 05:00:00'),
(71, 'ME401', 'Heat Transfer', 4, 4, NULL, 0, 60.00, 3, NULL, 'theory', 'Conduction, convection and radiation heat transfer', 'active', '2024-01-15 05:00:00'),
(72, 'ME402', 'Machine Design', 4, 4, NULL, 0, 60.00, 3, NULL, 'lab', 'Mechanical component design and stress analysis', 'active', '2024-01-15 05:00:00'),
(73, 'ME501', 'Manufacturing Processes', 4, 5, NULL, 0, 60.00, 3, NULL, 'lab', 'Machining, forming and joining processes', 'active', '2024-01-15 05:00:00'),
(74, 'ME502', 'Vibration Analysis', 4, 5, NULL, 0, 60.00, 3, NULL, 'theory', 'Mechanical vibration theory and active control', 'active', '2024-01-15 05:00:00'),
(75, 'ME601', 'Finite Element Analysis', 4, 6, NULL, 0, 70.00, 3, NULL, 'lab', 'FEA fundamentals using simulation software', 'active', '2024-01-15 05:00:00'),
(76, 'ME602', 'Mechatronics', 4, 6, NULL, 0, 60.00, 3, NULL, 'lab', 'Integration of mechanical and electronic systems', 'active', '2024-01-15 05:00:00'),
(77, 'ME701', 'Advanced Manufacturing', 4, 7, NULL, 0, 60.00, 3, NULL, 'theory', 'CNC machining, automation and Industry 4.0', 'active', '2024-01-15 05:00:00'),
(78, 'ME702', 'Final Design Project I', 4, 7, NULL, 0, 80.00, 4, NULL, 'hybrid', 'First phase of mechanical design project', 'active', '2024-01-15 05:00:00'),
(79, 'ME801', 'Advanced Topics in ME', 4, 8, NULL, 0, 70.00, 3, NULL, 'theory', 'Emerging trends in mechanical engineering', 'active', '2024-01-15 05:00:00'),
(80, 'ME802', 'Final Design Project II', 4, 8, NULL, 0, 80.00, 4, NULL, 'hybrid', 'Final phase of the mechanical design project', 'active', '2024-01-15 05:00:00'),
(81, 'BBA101', 'Principles of Management', 5, 1, NULL, 0, 40.00, 3, NULL, 'theory', 'Management theories and organizational behavior', 'active', '2024-01-15 05:00:00'),
(82, 'BBA102', 'Business Mathematics', 5, 1, NULL, 0, 40.00, 3, NULL, 'theory', 'Mathematical tools for business decision-making', 'active', '2024-01-15 05:00:00'),
(83, 'BBA201', 'Financial Accounting', 5, 2, NULL, 0, 50.00, 3, NULL, 'theory', 'Financial statements and accounting principles', 'active', '2024-01-15 05:00:00'),
(84, 'BBA202', 'Marketing Management', 5, 2, NULL, 0, 40.00, 3, NULL, 'theory', 'Marketing mix, consumer behavior and strategy', 'active', '2024-01-15 05:00:00'),
(85, 'BBA301', 'Managerial Economics', 5, 3, NULL, 0, 50.00, 3, NULL, 'theory', 'Economic analysis for business decisions', 'active', '2024-01-15 05:00:00'),
(86, 'BBA302', 'Human Resource Management', 5, 3, NULL, 0, 40.00, 3, NULL, 'theory', 'Recruitment, training and performance management', 'active', '2024-01-15 05:00:00'),
(87, 'BBA401', 'Corporate Finance', 5, 4, NULL, 0, 50.00, 3, NULL, 'theory', 'Capital structure, investment and valuation', 'active', '2024-01-15 05:00:00'),
(88, 'BBA402', 'Operations Management', 5, 4, NULL, 0, 50.00, 3, NULL, 'theory', 'Production planning, quality and supply chain', 'active', '2024-01-15 05:00:00'),
(89, 'BBA501', 'Strategic Management', 5, 5, NULL, 0, 50.00, 3, NULL, 'theory', 'Corporate strategy and competitive advantage', 'active', '2024-01-15 05:00:00'),
(90, 'BBA502', 'Business Ethics', 5, 5, NULL, 0, 40.00, 3, NULL, 'theory', 'Ethical decision-making in business contexts', 'active', '2024-01-15 05:00:00'),
(91, 'BBA601', 'Entrepreneurship', 5, 6, NULL, 0, 50.00, 3, NULL, 'theory', 'Business idea generation and startup planning', 'active', '2024-01-15 05:00:00'),
(92, 'BBA602', 'International Business', 5, 6, NULL, 0, 50.00, 3, NULL, 'theory', 'Global trade, FDI and international marketing', 'active', '2024-01-15 05:00:00'),
(93, 'BBA701', 'Business Research Methods', 5, 7, NULL, 0, 50.00, 3, NULL, 'theory', 'Quantitative and qualitative research in business', 'active', '2024-01-15 05:00:00'),
(94, 'BBA702', 'Capstone Business Project I', 5, 7, NULL, 0, 70.00, 4, NULL, 'hybrid', 'First phase of the business capstone project', 'active', '2024-01-15 05:00:00'),
(95, 'BBA801', 'Advanced Business Elective', 5, 8, NULL, 0, 50.00, 3, NULL, 'theory', 'Selected advanced topics in business administration', 'active', '2024-01-15 05:00:00'),
(96, 'BBA802', 'Capstone Business Project II', 5, 8, NULL, 0, 70.00, 4, NULL, 'hybrid', 'Final phase of the business capstone project', 'active', '2024-01-15 05:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `course_assignments`
--

CREATE TABLE `course_assignments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `course_section_id` bigint(20) UNSIGNED NOT NULL,
  `teacher_id` bigint(20) UNSIGNED NOT NULL,
  `component` enum('theory','lab') NOT NULL,
  `assigned_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `course_assignments`
--

INSERT INTO `course_assignments` (`id`, `course_section_id`, `teacher_id`, `component`, `assigned_date`) VALUES
(1, 1, 1, 'theory', '2025-12-01'),
(2, 2, 2, 'theory', '2025-12-01'),
(3, 3, 3, 'theory', '2025-12-01'),
(4, 4, 1, 'theory', '2025-12-01'),
(5, 4, 1, 'lab', '2025-12-01'),
(6, 5, 2, 'theory', '2025-12-01'),
(7, 6, 3, 'theory', '2025-12-01'),
(8, 7, 1, 'theory', '2025-12-01'),
(9, 8, 2, 'theory', '2025-12-01'),
(10, 8, 2, 'lab', '2025-12-01'),
(11, 9, 3, 'theory', '2025-12-01'),
(12, 10, 1, 'theory', '2025-12-01'),
(13, 11, 2, 'theory', '2025-12-01'),
(14, 12, 3, 'theory', '2025-12-01'),
(15, 12, 3, 'lab', '2025-12-01'),
(16, 13, 1, 'theory', '2025-12-01'),
(17, 14, 2, 'theory', '2025-12-01'),
(18, 15, 3, 'theory', '2025-12-01'),
(19, 15, 3, 'lab', '2025-12-01'),
(20, 16, 1, 'theory', '2025-12-01'),
(21, 17, 2, 'theory', '2025-12-01'),
(22, 18, 3, 'theory', '2025-12-01'),
(23, 19, 1, 'theory', '2025-12-01'),
(24, 20, 2, 'theory', '2025-12-01'),
(25, 20, 2, 'lab', '2025-12-01'),
(26, 21, 3, 'theory', '2025-12-01'),
(27, 22, 1, 'theory', '2025-12-01'),
(28, 22, 1, 'lab', '2025-12-01'),
(29, 23, 2, 'theory', '2025-12-01'),
(30, 24, 3, 'theory', '2025-12-01'),
(31, 25, 4, 'theory', '2025-12-01'),
(32, 26, 5, 'theory', '2025-12-01'),
(33, 27, 6, 'theory', '2025-12-01'),
(34, 28, 4, 'theory', '2025-12-01'),
(35, 28, 4, 'lab', '2025-12-01'),
(36, 29, 5, 'theory', '2025-12-01'),
(37, 29, 5, 'lab', '2025-12-01'),
(38, 30, 6, 'theory', '2025-12-01'),
(39, 31, 4, 'theory', '2025-12-01'),
(40, 31, 4, 'lab', '2025-12-01'),
(41, 32, 5, 'theory', '2025-12-01'),
(42, 33, 6, 'theory', '2025-12-01'),
(43, 34, 4, 'theory', '2025-12-01'),
(44, 35, 5, 'theory', '2025-12-01'),
(45, 36, 6, 'theory', '2025-12-01'),
(46, 37, 4, 'theory', '2025-12-01'),
(47, 38, 5, 'theory', '2025-12-01'),
(48, 39, 6, 'theory', '2025-12-01'),
(49, 39, 6, 'lab', '2025-12-01'),
(50, 40, 4, 'theory', '2025-12-01'),
(51, 40, 4, 'lab', '2025-12-01'),
(52, 41, 5, 'theory', '2025-12-01'),
(53, 42, 6, 'theory', '2025-12-01'),
(54, 43, 4, 'theory', '2025-12-01'),
(55, 44, 5, 'theory', '2025-12-01'),
(56, 44, 5, 'lab', '2025-12-01'),
(57, 45, 6, 'theory', '2025-12-01'),
(58, 46, 4, 'theory', '2025-12-01'),
(59, 46, 4, 'lab', '2025-12-01'),
(60, 47, 5, 'theory', '2025-12-01'),
(61, 48, 6, 'theory', '2025-12-01'),
(62, 49, 7, 'theory', '2025-12-01'),
(63, 50, 8, 'theory', '2025-12-01'),
(64, 51, 9, 'theory', '2025-12-01'),
(65, 51, 9, 'lab', '2025-12-01'),
(66, 52, 7, 'theory', '2025-12-01'),
(67, 53, 8, 'theory', '2025-12-01'),
(68, 53, 8, 'lab', '2025-12-01'),
(69, 54, 9, 'theory', '2025-12-01'),
(70, 55, 7, 'theory', '2025-12-01'),
(71, 56, 8, 'theory', '2025-12-01'),
(72, 57, 9, 'theory', '2025-12-01'),
(73, 58, 7, 'theory', '2025-12-01'),
(74, 58, 7, 'lab', '2025-12-01'),
(75, 59, 8, 'theory', '2025-12-01'),
(76, 60, 9, 'theory', '2025-12-01'),
(77, 60, 9, 'lab', '2025-12-01'),
(78, 61, 7, 'theory', '2025-12-01'),
(79, 62, 8, 'theory', '2025-12-01'),
(80, 62, 8, 'lab', '2025-12-01'),
(81, 63, 9, 'theory', '2025-12-01'),
(82, 64, 7, 'theory', '2025-12-01'),
(83, 64, 7, 'lab', '2025-12-01'),
(84, 65, 11, 'theory', '2025-12-01'),
(85, 66, 12, 'theory', '2025-12-01'),
(86, 67, 10, 'theory', '2025-12-01'),
(87, 68, 11, 'theory', '2025-12-01'),
(88, 69, 12, 'theory', '2025-12-01'),
(89, 69, 12, 'lab', '2025-12-01'),
(90, 70, 10, 'theory', '2025-12-01'),
(91, 71, 11, 'theory', '2025-12-01'),
(92, 72, 12, 'theory', '2025-12-01'),
(93, 72, 12, 'lab', '2025-12-01'),
(94, 73, 10, 'theory', '2025-12-01'),
(95, 73, 10, 'lab', '2025-12-01'),
(96, 74, 11, 'theory', '2025-12-01'),
(97, 75, 12, 'theory', '2025-12-01'),
(98, 75, 12, 'lab', '2025-12-01'),
(99, 76, 10, 'theory', '2025-12-01'),
(100, 76, 10, 'lab', '2025-12-01'),
(101, 77, 11, 'theory', '2025-12-01'),
(102, 78, 12, 'theory', '2025-12-01'),
(103, 78, 12, 'lab', '2025-12-01'),
(104, 79, 10, 'theory', '2025-12-01'),
(105, 80, 11, 'theory', '2025-12-01'),
(106, 80, 11, 'lab', '2025-12-01'),
(107, 81, 15, 'theory', '2025-12-01'),
(108, 82, 13, 'theory', '2025-12-01'),
(109, 83, 14, 'theory', '2025-12-01'),
(110, 84, 15, 'theory', '2025-12-01'),
(111, 85, 13, 'theory', '2025-12-01'),
(112, 86, 14, 'theory', '2025-12-01'),
(113, 87, 15, 'theory', '2025-12-01'),
(114, 88, 13, 'theory', '2025-12-01'),
(115, 89, 14, 'theory', '2025-12-01'),
(116, 90, 15, 'theory', '2025-12-01'),
(117, 91, 13, 'theory', '2025-12-01'),
(118, 92, 14, 'theory', '2025-12-01'),
(119, 93, 15, 'theory', '2025-12-01'),
(120, 94, 13, 'theory', '2025-12-01'),
(121, 94, 13, 'lab', '2025-12-01'),
(122, 95, 14, 'theory', '2025-12-01'),
(123, 96, 15, 'theory', '2025-12-01'),
(124, 96, 15, 'lab', '2025-12-01'),
(125, 97, 1, 'theory', '2025-12-01'),
(126, 98, 2, 'theory', '2025-12-01'),
(127, 99, 3, 'theory', '2025-12-01'),
(128, 100, 1, 'theory', '2025-12-01'),
(129, 100, 1, 'lab', '2025-12-01'),
(130, 101, 2, 'theory', '2025-12-01'),
(131, 102, 3, 'theory', '2025-12-01'),
(132, 103, 1, 'theory', '2025-12-01'),
(133, 104, 2, 'theory', '2025-12-01'),
(134, 104, 2, 'lab', '2025-12-01'),
(135, 105, 3, 'theory', '2025-12-01'),
(136, 106, 1, 'theory', '2025-12-01'),
(137, 107, 2, 'theory', '2025-12-01'),
(138, 108, 3, 'theory', '2025-12-01'),
(139, 108, 3, 'lab', '2025-12-01'),
(140, 109, 1, 'theory', '2025-12-01'),
(141, 110, 2, 'theory', '2025-12-01'),
(142, 111, 3, 'theory', '2025-12-01'),
(143, 111, 3, 'lab', '2025-12-01'),
(144, 112, 1, 'theory', '2025-12-01'),
(145, 113, 2, 'theory', '2025-12-01'),
(146, 114, 3, 'theory', '2025-12-01'),
(147, 115, 1, 'theory', '2025-12-01'),
(148, 116, 2, 'theory', '2025-12-01'),
(149, 116, 2, 'lab', '2025-12-01'),
(150, 117, 3, 'theory', '2025-12-01'),
(151, 118, 4, 'theory', '2025-12-01'),
(152, 119, 5, 'theory', '2025-12-01'),
(153, 120, 6, 'theory', '2025-12-01'),
(154, 121, 4, 'theory', '2025-12-01'),
(155, 121, 4, 'lab', '2025-12-01'),
(156, 122, 5, 'theory', '2025-12-01'),
(157, 122, 5, 'lab', '2025-12-01'),
(158, 123, 6, 'theory', '2025-12-01'),
(159, 124, 4, 'theory', '2025-12-01'),
(160, 124, 4, 'lab', '2025-12-01'),
(161, 125, 5, 'theory', '2025-12-01'),
(162, 126, 6, 'theory', '2025-12-01'),
(163, 127, 4, 'theory', '2025-12-01'),
(164, 128, 5, 'theory', '2025-12-01'),
(165, 129, 6, 'theory', '2025-12-01'),
(166, 130, 4, 'theory', '2025-12-01'),
(167, 131, 5, 'theory', '2025-12-01'),
(168, 132, 6, 'theory', '2025-12-01'),
(169, 132, 6, 'lab', '2025-12-01'),
(170, 133, 4, 'theory', '2025-12-01'),
(171, 133, 4, 'lab', '2025-12-01'),
(172, 134, 5, 'theory', '2025-12-01'),
(173, 135, 6, 'theory', '2025-12-01'),
(174, 136, 4, 'theory', '2025-12-01'),
(175, 137, 5, 'theory', '2025-12-01'),
(176, 137, 5, 'lab', '2025-12-01'),
(177, 138, 6, 'theory', '2025-12-01'),
(178, 139, 7, 'theory', '2025-12-01'),
(179, 140, 8, 'theory', '2025-12-01'),
(180, 141, 9, 'theory', '2025-12-01'),
(181, 141, 9, 'lab', '2025-12-01'),
(182, 142, 7, 'theory', '2025-12-01'),
(183, 143, 8, 'theory', '2025-12-01'),
(184, 143, 8, 'lab', '2025-12-01'),
(185, 144, 9, 'theory', '2025-12-01'),
(186, 145, 7, 'theory', '2025-12-01'),
(187, 146, 8, 'theory', '2025-12-01'),
(188, 147, 9, 'theory', '2025-12-01'),
(189, 148, 7, 'theory', '2025-12-01'),
(190, 148, 7, 'lab', '2025-12-01'),
(191, 149, 8, 'theory', '2025-12-01'),
(192, 150, 9, 'theory', '2025-12-01'),
(193, 150, 9, 'lab', '2025-12-01'),
(194, 151, 7, 'theory', '2025-12-01'),
(195, 152, 8, 'theory', '2025-12-01'),
(196, 152, 8, 'lab', '2025-12-01'),
(197, 153, 11, 'theory', '2025-12-01'),
(198, 154, 12, 'theory', '2025-12-01'),
(199, 155, 10, 'theory', '2025-12-01'),
(200, 156, 11, 'theory', '2025-12-01'),
(201, 157, 12, 'theory', '2025-12-01'),
(202, 157, 12, 'lab', '2025-12-01'),
(203, 158, 10, 'theory', '2025-12-01'),
(204, 159, 11, 'theory', '2025-12-01'),
(205, 160, 12, 'theory', '2025-12-01'),
(206, 160, 12, 'lab', '2025-12-01'),
(207, 161, 10, 'theory', '2025-12-01'),
(208, 161, 10, 'lab', '2025-12-01'),
(209, 162, 11, 'theory', '2025-12-01'),
(210, 163, 12, 'theory', '2025-12-01'),
(211, 163, 12, 'lab', '2025-12-01'),
(212, 164, 10, 'theory', '2025-12-01'),
(213, 164, 10, 'lab', '2025-12-01'),
(214, 165, 11, 'theory', '2025-12-01'),
(215, 166, 12, 'theory', '2025-12-01'),
(216, 166, 12, 'lab', '2025-12-01'),
(217, 167, 15, 'theory', '2025-12-01'),
(218, 168, 13, 'theory', '2025-12-01'),
(219, 169, 14, 'theory', '2025-12-01'),
(220, 170, 15, 'theory', '2025-12-01'),
(221, 171, 13, 'theory', '2025-12-01'),
(222, 172, 14, 'theory', '2025-12-01'),
(223, 173, 15, 'theory', '2025-12-01'),
(224, 174, 13, 'theory', '2025-12-01'),
(225, 175, 14, 'theory', '2025-12-01'),
(226, 176, 15, 'theory', '2025-12-01'),
(227, 177, 13, 'theory', '2025-12-01'),
(228, 178, 14, 'theory', '2025-12-01'),
(229, 179, 15, 'theory', '2025-12-01'),
(230, 180, 13, 'theory', '2025-12-01'),
(231, 180, 13, 'lab', '2025-12-01');

-- --------------------------------------------------------

--
-- Table structure for table `course_sections`
--

CREATE TABLE `course_sections` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `section_number` varchar(255) NOT NULL,
  `term` varchar(255) NOT NULL,
  `year` int(11) NOT NULL,
  `max_students` int(11) NOT NULL DEFAULT 30,
  `enrolled_students` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `course_sections`
--

INSERT INTO `course_sections` (`id`, `course_id`, `section_number`, `term`, `year`, `max_students`, `enrolled_students`, `created_at`) VALUES
(1, 1, '1', 'Winter', 2026, 35, 2, '2025-12-01 13:00:00'),
(2, 2, '1', 'Winter', 2026, 35, 2, '2025-12-01 13:00:00'),
(3, 3, '1', 'Winter', 2026, 35, 2, '2025-12-01 13:00:00'),
(4, 4, '1', 'Winter', 2026, 25, 2, '2025-12-01 13:00:00'),
(5, 5, '1', 'Winter', 2026, 35, 2, '2025-12-01 13:00:00'),
(6, 6, '1', 'Winter', 2026, 35, 3, '2025-12-01 13:00:00'),
(7, 7, '1', 'Winter', 2026, 35, 3, '2025-12-01 13:00:00'),
(8, 8, '1', 'Winter', 2026, 25, 2, '2025-12-01 13:00:00'),
(9, 9, '1', 'Winter', 2026, 35, 3, '2025-12-01 13:00:00'),
(10, 10, '1', 'Winter', 2026, 35, 2, '2025-12-01 13:00:00'),
(11, 11, '1', 'Winter', 2026, 35, 2, '2025-12-01 13:00:00'),
(12, 12, '1', 'Winter', 2026, 25, 2, '2025-12-01 13:00:00'),
(13, 13, '1', 'Winter', 2026, 35, 1, '2025-12-01 13:00:00'),
(14, 14, '1', 'Winter', 2026, 35, 1, '2025-12-01 13:00:00'),
(15, 15, '1', 'Winter', 2026, 25, 1, '2025-12-01 13:00:00'),
(16, 16, '1', 'Winter', 2026, 35, 2, '2025-12-01 13:00:00'),
(17, 17, '1', 'Winter', 2026, 35, 1, '2025-12-01 13:00:00'),
(18, 18, '1', 'Winter', 2026, 35, 1, '2025-12-01 13:00:00'),
(19, 19, '1', 'Winter', 2026, 35, 1, '2025-12-01 13:00:00'),
(20, 20, '1', 'Winter', 2026, 40, 1, '2025-12-01 13:00:00'),
(21, 21, '1', 'Winter', 2026, 35, 1, '2025-12-01 13:00:00'),
(22, 22, '1', 'Winter', 2026, 40, 1, '2025-12-01 13:00:00'),
(23, 23, '1', 'Winter', 2026, 35, 1, '2025-12-01 13:00:00'),
(24, 24, '1', 'Winter', 2026, 35, 1, '2025-12-01 13:00:00'),
(25, 25, '1', 'Winter', 2026, 35, 2, '2025-12-01 13:00:00'),
(26, 26, '1', 'Winter', 2026, 35, 2, '2025-12-01 13:00:00'),
(27, 27, '1', 'Winter', 2026, 35, 2, '2025-12-01 13:00:00'),
(28, 28, '1', 'Winter', 2026, 25, 2, '2025-12-01 13:00:00'),
(29, 29, '1', 'Winter', 2026, 25, 2, '2025-12-01 13:00:00'),
(30, 30, '1', 'Winter', 2026, 35, 2, '2025-12-01 13:00:00'),
(31, 31, '1', 'Winter', 2026, 25, 2, '2025-12-01 13:00:00'),
(32, 32, '1', 'Winter', 2026, 35, 2, '2025-12-01 13:00:00'),
(33, 33, '1', 'Winter', 2026, 35, 2, '2025-12-01 13:00:00'),
(34, 34, '1', 'Winter', 2026, 35, 2, '2025-12-01 13:00:00'),
(35, 35, '1', 'Winter', 2026, 35, 2, '2025-12-01 13:00:00'),
(36, 36, '1', 'Winter', 2026, 35, 2, '2025-12-01 13:00:00'),
(37, 37, '1', 'Winter', 2026, 35, 1, '2025-12-01 13:00:00'),
(38, 38, '1', 'Winter', 2026, 35, 1, '2025-12-01 13:00:00'),
(39, 39, '1', 'Winter', 2026, 25, 1, '2025-12-01 13:00:00'),
(40, 40, '1', 'Winter', 2026, 25, 1, '2025-12-01 13:00:00'),
(41, 41, '1', 'Winter', 2026, 35, 1, '2025-12-01 13:00:00'),
(42, 42, '1', 'Winter', 2026, 35, 1, '2025-12-01 13:00:00'),
(43, 43, '1', 'Winter', 2026, 35, 1, '2025-12-01 13:00:00'),
(44, 44, '1', 'Winter', 2026, 40, 1, '2025-12-01 13:00:00'),
(45, 45, '1', 'Winter', 2026, 35, 1, '2025-12-01 13:00:00'),
(46, 46, '1', 'Winter', 2026, 40, 1, '2025-12-01 13:00:00'),
(47, 47, '1', 'Winter', 2026, 35, 1, '2025-12-01 13:00:00'),
(48, 48, '1', 'Winter', 2026, 35, 1, '2025-12-01 13:00:00'),
(49, 49, '1', 'Winter', 2026, 35, 2, '2025-12-01 13:00:00'),
(50, 50, '1', 'Winter', 2026, 35, 2, '2025-12-01 13:00:00'),
(51, 51, '1', 'Winter', 2026, 25, 2, '2025-12-01 13:00:00'),
(52, 52, '1', 'Winter', 2026, 35, 2, '2025-12-01 13:00:00'),
(53, 53, '1', 'Winter', 2026, 25, 2, '2025-12-01 13:00:00'),
(54, 54, '1', 'Winter', 2026, 35, 2, '2025-12-01 13:00:00'),
(55, 55, '1', 'Winter', 2026, 35, 2, '2025-12-01 13:00:00'),
(56, 56, '1', 'Winter', 2026, 35, 2, '2025-12-01 13:00:00'),
(57, 57, '1', 'Winter', 2026, 35, 1, '2025-12-01 13:00:00'),
(58, 58, '1', 'Winter', 2026, 25, 1, '2025-12-01 13:00:00'),
(59, 59, '1', 'Winter', 2026, 35, 1, '2025-12-01 13:00:00'),
(60, 60, '1', 'Winter', 2026, 25, 1, '2025-12-01 13:00:00'),
(61, 61, '1', 'Winter', 2026, 35, 1, '2025-12-01 13:00:00'),
(62, 62, '1', 'Winter', 2026, 40, 1, '2025-12-01 13:00:00'),
(63, 63, '1', 'Winter', 2026, 35, 1, '2025-12-01 13:00:00'),
(64, 64, '1', 'Winter', 2026, 40, 1, '2025-12-01 13:00:00'),
(65, 65, '1', 'Winter', 2026, 35, 2, '2025-12-01 13:00:00'),
(66, 66, '1', 'Winter', 2026, 35, 2, '2025-12-01 13:00:00'),
(67, 67, '1', 'Winter', 2026, 35, 2, '2025-12-01 13:00:00'),
(68, 68, '1', 'Winter', 2026, 35, 2, '2025-12-01 13:00:00'),
(69, 69, '1', 'Winter', 2026, 25, 2, '2025-12-01 13:00:00'),
(70, 70, '1', 'Winter', 2026, 35, 2, '2025-12-01 13:00:00'),
(71, 71, '1', 'Winter', 2026, 35, 2, '2025-12-01 13:00:00'),
(72, 72, '1', 'Winter', 2026, 25, 2, '2025-12-01 13:00:00'),
(73, 73, '1', 'Winter', 2026, 25, 1, '2025-12-01 13:00:00'),
(74, 74, '1', 'Winter', 2026, 35, 1, '2025-12-01 13:00:00'),
(75, 75, '1', 'Winter', 2026, 25, 1, '2025-12-01 13:00:00'),
(76, 76, '1', 'Winter', 2026, 25, 1, '2025-12-01 13:00:00'),
(77, 77, '1', 'Winter', 2026, 35, 1, '2025-12-01 13:00:00'),
(78, 78, '1', 'Winter', 2026, 40, 1, '2025-12-01 13:00:00'),
(79, 79, '1', 'Winter', 2026, 35, 0, '2025-12-01 13:00:00'),
(80, 80, '1', 'Winter', 2026, 40, 0, '2025-12-01 13:00:00'),
(81, 81, '1', 'Winter', 2026, 35, 2, '2025-12-01 13:00:00'),
(82, 82, '1', 'Winter', 2026, 35, 2, '2025-12-01 13:00:00'),
(83, 83, '1', 'Winter', 2026, 35, 2, '2025-12-01 13:00:00'),
(84, 84, '1', 'Winter', 2026, 35, 2, '2025-12-01 13:00:00'),
(85, 85, '1', 'Winter', 2026, 35, 2, '2025-12-01 13:00:00'),
(86, 86, '1', 'Winter', 2026, 35, 2, '2025-12-01 13:00:00'),
(87, 87, '1', 'Winter', 2026, 35, 2, '2025-12-01 13:00:00'),
(88, 88, '1', 'Winter', 2026, 35, 2, '2025-12-01 13:00:00'),
(89, 89, '1', 'Winter', 2026, 35, 1, '2025-12-01 13:00:00'),
(90, 90, '1', 'Winter', 2026, 35, 1, '2025-12-01 13:00:00'),
(91, 91, '1', 'Winter', 2026, 35, 1, '2025-12-01 13:00:00'),
(92, 92, '1', 'Winter', 2026, 35, 1, '2025-12-01 13:00:00'),
(93, 93, '1', 'Winter', 2026, 35, 1, '2025-12-01 13:00:00'),
(94, 94, '1', 'Winter', 2026, 40, 1, '2025-12-01 13:00:00'),
(95, 95, '1', 'Winter', 2026, 35, 1, '2025-12-01 13:00:00'),
(96, 96, '1', 'Winter', 2026, 40, 1, '2025-12-01 13:00:00'),
(97, 1, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(98, 2, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(99, 3, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(100, 4, '1', 'Fall', 2025, 25, 0, '2025-08-01 12:00:00'),
(101, 5, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(102, 6, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(103, 7, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(104, 8, '1', 'Fall', 2025, 25, 0, '2025-08-01 12:00:00'),
(105, 9, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(106, 10, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(107, 11, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(108, 12, '1', 'Fall', 2025, 25, 0, '2025-08-01 12:00:00'),
(109, 13, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(110, 14, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(111, 15, '1', 'Fall', 2025, 25, 0, '2025-08-01 12:00:00'),
(112, 16, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(113, 17, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(114, 18, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(115, 19, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(116, 20, '1', 'Fall', 2025, 40, 0, '2025-08-01 12:00:00'),
(117, 21, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(118, 25, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(119, 26, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(120, 27, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(121, 28, '1', 'Fall', 2025, 25, 0, '2025-08-01 12:00:00'),
(122, 29, '1', 'Fall', 2025, 25, 0, '2025-08-01 12:00:00'),
(123, 30, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(124, 31, '1', 'Fall', 2025, 25, 0, '2025-08-01 12:00:00'),
(125, 32, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(126, 33, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(127, 34, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(128, 35, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(129, 36, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(130, 37, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(131, 38, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(132, 39, '1', 'Fall', 2025, 25, 0, '2025-08-01 12:00:00'),
(133, 40, '1', 'Fall', 2025, 25, 0, '2025-08-01 12:00:00'),
(134, 41, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(135, 42, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(136, 43, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(137, 44, '1', 'Fall', 2025, 40, 0, '2025-08-01 12:00:00'),
(138, 45, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(139, 49, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(140, 50, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(141, 51, '1', 'Fall', 2025, 25, 0, '2025-08-01 12:00:00'),
(142, 52, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(143, 53, '1', 'Fall', 2025, 25, 0, '2025-08-01 12:00:00'),
(144, 54, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(145, 55, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(146, 56, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(147, 57, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(148, 58, '1', 'Fall', 2025, 25, 0, '2025-08-01 12:00:00'),
(149, 59, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(150, 60, '1', 'Fall', 2025, 25, 0, '2025-08-01 12:00:00'),
(151, 61, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(152, 62, '1', 'Fall', 2025, 40, 0, '2025-08-01 12:00:00'),
(153, 65, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(154, 66, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(155, 67, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(156, 68, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(157, 69, '1', 'Fall', 2025, 25, 0, '2025-08-01 12:00:00'),
(158, 70, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(159, 71, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(160, 72, '1', 'Fall', 2025, 25, 0, '2025-08-01 12:00:00'),
(161, 73, '1', 'Fall', 2025, 25, 0, '2025-08-01 12:00:00'),
(162, 74, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(163, 75, '1', 'Fall', 2025, 25, 0, '2025-08-01 12:00:00'),
(164, 76, '1', 'Fall', 2025, 25, 0, '2025-08-01 12:00:00'),
(165, 77, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(166, 78, '1', 'Fall', 2025, 40, 0, '2025-08-01 12:00:00'),
(167, 81, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(168, 82, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(169, 83, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(170, 84, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(171, 85, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(172, 86, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(173, 87, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(174, 88, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(175, 89, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(176, 90, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(177, 91, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(178, 92, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(179, 93, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(180, 94, '1', 'Fall', 2025, 35, 0, '2025-08-01 12:00:00'),
(181, 1, '1', 'Fall', 2026, 60, 0, NULL),
(182, 2, '1', 'Fall', 2026, 60, 0, NULL),
(183, 3, '1', 'Fall', 2026, 60, 0, NULL),
(184, 4, '1', 'Fall', 2026, 60, 0, NULL),
(185, 5, '1', 'Fall', 2026, 60, 0, NULL),
(186, 6, '1', 'Fall', 2026, 60, 0, NULL),
(187, 7, '1', 'Fall', 2026, 60, 0, NULL),
(188, 8, '1', 'Fall', 2026, 60, 0, NULL),
(189, 9, '1', 'Fall', 2026, 60, 0, NULL),
(190, 10, '1', 'Fall', 2026, 60, 0, NULL),
(191, 11, '1', 'Fall', 2026, 60, 0, NULL),
(192, 12, '1', 'Fall', 2026, 60, 0, NULL),
(193, 13, '1', 'Fall', 2026, 60, 0, NULL),
(194, 14, '1', 'Fall', 2026, 60, 0, NULL),
(195, 15, '1', 'Fall', 2026, 60, 0, NULL),
(196, 16, '1', 'Fall', 2026, 60, 0, NULL),
(197, 17, '1', 'Fall', 2026, 60, 0, NULL),
(198, 18, '1', 'Fall', 2026, 60, 0, NULL),
(199, 19, '1', 'Fall', 2026, 60, 0, NULL),
(200, 20, '1', 'Fall', 2026, 60, 0, NULL),
(201, 21, '1', 'Fall', 2026, 60, 0, NULL),
(202, 22, '1', 'Fall', 2026, 60, 0, NULL),
(203, 23, '1', 'Fall', 2026, 60, 0, NULL),
(204, 24, '1', 'Fall', 2026, 60, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `registration_fee` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `code`, `name`, `description`, `registration_fee`, `created_at`) VALUES
(1, 'CS', 'Computer Science', 'Bachelor of Computer Science program', NULL, '2024-01-15 13:00:00'),
(2, 'IT', 'Information Technology', 'Bachelor of Information Technology program', NULL, '2024-01-15 13:00:00'),
(3, 'EE', 'Electrical Engineering', 'Bachelor of Electrical Engineering program', NULL, '2024-01-15 13:00:00'),
(4, 'ME', 'Mechanical Engineering', 'Bachelor of Mechanical Engineering program', NULL, '2024-01-15 13:00:00'),
(5, 'BBA', 'Business Administration', 'Bachelor of Business Administration program', NULL, '2024-01-15 13:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fee_payments`
--

CREATE TABLE `fee_payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `type` enum('regular','supplemental') NOT NULL DEFAULT 'regular',
  `semester` tinyint(3) UNSIGNED NOT NULL,
  `year` smallint(5) UNSIGNED NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `paid_amount` decimal(10,2) DEFAULT NULL,
  `status` enum('paid','pending','overdue','partial') NOT NULL DEFAULT 'pending',
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `course_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fee_payments`
--

INSERT INTO `fee_payments` (`id`, `student_id`, `type`, `semester`, `year`, `amount`, `paid_amount`, `status`, `paid_at`, `created_at`, `course_id`) VALUES
(1, 1, 'regular', 1, 2026, 500.00, 500.00, 'paid', '2026-01-15 14:00:00', '2025-12-01 13:00:00', NULL),
(2, 2, 'regular', 1, 2026, 500.00, 500.00, 'paid', '2025-12-20 14:00:00', '2025-12-01 13:00:00', NULL),
(3, 3, 'regular', 1, 2024, 500.00, 500.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(4, 3, 'regular', 2, 2026, 500.00, 500.00, 'paid', '2026-01-15 14:00:00', '2025-12-01 13:00:00', NULL),
(5, 4, 'regular', 1, 2024, 500.00, 500.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(6, 4, 'regular', 2, 2026, 500.00, 500.00, 'paid', '2026-01-15 14:00:00', '2025-12-01 13:00:00', NULL),
(7, 5, 'regular', 1, 2024, 500.00, 500.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(8, 5, 'regular', 2, 2024, 500.00, 500.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(9, 5, 'regular', 3, 2026, 500.00, 500.00, 'paid', '2025-12-18 14:00:00', '2025-12-01 13:00:00', NULL),
(10, 6, 'regular', 1, 2024, 500.00, 500.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(11, 6, 'regular', 2, 2024, 500.00, 500.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(12, 6, 'regular', 3, 2026, 500.00, 500.00, 'paid', '2025-12-18 14:00:00', '2025-12-01 13:00:00', NULL),
(13, 7, 'regular', 1, 2024, 500.00, 500.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(14, 7, 'regular', 2, 2024, 500.00, 500.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(15, 7, 'regular', 3, 2025, 500.00, 500.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(16, 7, 'regular', 4, 2026, 500.00, 500.00, 'paid', '2026-01-15 14:00:00', '2025-12-01 13:00:00', NULL),
(17, 8, 'regular', 1, 2024, 500.00, 500.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(18, 8, 'regular', 2, 2024, 500.00, 500.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(19, 8, 'regular', 3, 2025, 500.00, 500.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(20, 8, 'regular', 4, 2026, 500.00, 500.00, 'paid', '2026-01-15 14:00:00', '2025-12-01 13:00:00', NULL),
(21, 9, 'regular', 1, 2024, 500.00, 500.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(22, 9, 'regular', 2, 2024, 500.00, 500.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(23, 9, 'regular', 3, 2025, 500.00, 500.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(24, 9, 'regular', 4, 2025, 500.00, 500.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(25, 9, 'regular', 5, 2026, 500.00, 500.00, 'paid', '2026-01-15 14:00:00', '2025-12-01 13:00:00', NULL),
(26, 10, 'regular', 1, 2024, 500.00, 500.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(27, 10, 'regular', 2, 2024, 500.00, 500.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(28, 10, 'regular', 3, 2025, 500.00, 500.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(29, 10, 'regular', 4, 2025, 500.00, 500.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(30, 10, 'regular', 5, 2025, 500.00, 500.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(31, 10, 'regular', 6, 2026, 500.00, 500.00, 'paid', '2025-12-10 14:00:00', '2025-12-01 13:00:00', NULL),
(32, 11, 'regular', 1, 2024, 500.00, 500.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(33, 11, 'regular', 2, 2024, 500.00, 500.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(34, 11, 'regular', 3, 2025, 500.00, 500.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(35, 11, 'regular', 4, 2025, 500.00, 500.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(36, 11, 'regular', 5, 2025, 500.00, 500.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(37, 11, 'regular', 6, 2026, 500.00, 500.00, 'paid', '2026-05-01 12:00:00', '2026-04-20 12:00:00', NULL),
(38, 11, 'regular', 7, 2026, 500.00, 500.00, 'paid', '2025-12-10 14:00:00', '2025-12-01 13:00:00', NULL),
(39, 12, 'regular', 1, 2024, 500.00, 500.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(40, 12, 'regular', 2, 2024, 500.00, 500.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(41, 12, 'regular', 3, 2025, 500.00, 500.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(42, 12, 'regular', 4, 2025, 500.00, 500.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(43, 12, 'regular', 5, 2025, 500.00, 500.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(44, 12, 'regular', 6, 2026, 500.00, 500.00, 'paid', '2026-05-01 12:00:00', '2026-04-20 12:00:00', NULL),
(45, 12, 'regular', 7, 2026, 500.00, 500.00, 'paid', '2026-05-01 12:00:00', '2026-04-20 12:00:00', NULL),
(46, 12, 'regular', 8, 2026, 500.00, 500.00, 'paid', '2025-12-08 14:00:00', '2025-12-01 13:00:00', NULL),
(47, 13, 'regular', 1, 2026, 500.00, 500.00, 'paid', '2024-01-10 14:00:00', '2024-01-05 13:00:00', NULL),
(48, 14, 'regular', 1, 2026, 500.00, 500.00, 'paid', '2024-01-10 14:00:00', '2024-01-05 13:00:00', NULL),
(49, 15, 'regular', 1, 2024, 500.00, 500.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(50, 15, 'regular', 2, 2026, 500.00, 500.00, 'paid', '2024-01-10 14:00:00', '2024-01-05 13:00:00', NULL),
(51, 16, 'regular', 1, 2024, 500.00, 500.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(52, 16, 'regular', 2, 2026, 500.00, 500.00, 'paid', '2024-01-10 14:00:00', '2024-01-05 13:00:00', NULL),
(53, 17, 'regular', 1, 2024, 500.00, 500.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(54, 17, 'regular', 2, 2024, 500.00, 500.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(55, 17, 'regular', 3, 2026, 500.00, 500.00, 'paid', '2025-01-10 14:00:00', '2025-01-05 13:00:00', NULL),
(56, 18, 'regular', 1, 2024, 500.00, 500.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(57, 18, 'regular', 2, 2024, 500.00, 500.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(58, 18, 'regular', 3, 2026, 500.00, 500.00, 'paid', '2025-01-10 14:00:00', '2025-01-05 13:00:00', NULL),
(59, 19, 'regular', 1, 2024, 500.00, 500.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(60, 19, 'regular', 2, 2024, 500.00, 500.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(61, 19, 'regular', 3, 2025, 500.00, 500.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(62, 19, 'regular', 4, 2026, 500.00, 500.00, 'paid', '2025-01-10 14:00:00', '2025-01-05 13:00:00', NULL),
(63, 20, 'regular', 1, 2024, 500.00, 500.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(64, 20, 'regular', 2, 2024, 500.00, 500.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(65, 20, 'regular', 3, 2025, 500.00, 500.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(66, 20, 'regular', 4, 2026, 500.00, 500.00, 'paid', '2025-01-10 14:00:00', '2025-01-05 13:00:00', NULL),
(67, 21, 'regular', 1, 2024, 500.00, 500.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(68, 21, 'regular', 2, 2024, 500.00, 500.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(69, 21, 'regular', 3, 2025, 500.00, 500.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(70, 21, 'regular', 4, 2025, 500.00, 500.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(71, 21, 'regular', 5, 2026, 500.00, 500.00, 'paid', '2025-01-10 14:00:00', '2025-01-05 13:00:00', NULL),
(72, 22, 'regular', 1, 2024, 500.00, 500.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(73, 22, 'regular', 2, 2024, 500.00, 500.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(74, 22, 'regular', 3, 2025, 500.00, 500.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(75, 22, 'regular', 4, 2025, 500.00, 500.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(76, 22, 'regular', 5, 2025, 500.00, 500.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(77, 22, 'regular', 6, 2026, 500.00, 500.00, 'paid', '2026-01-10 14:00:00', '2026-01-05 13:00:00', NULL),
(78, 23, 'regular', 1, 2024, 500.00, 500.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(79, 23, 'regular', 2, 2024, 500.00, 500.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(80, 23, 'regular', 3, 2025, 500.00, 500.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(81, 23, 'regular', 4, 2025, 500.00, 500.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(82, 23, 'regular', 5, 2025, 500.00, 500.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(83, 23, 'regular', 6, 2026, 500.00, 500.00, 'paid', '2026-05-01 12:00:00', '2026-04-20 12:00:00', NULL),
(84, 23, 'regular', 7, 2026, 500.00, 500.00, 'paid', '2026-01-10 14:00:00', '2026-01-05 13:00:00', NULL),
(85, 24, 'regular', 1, 2024, 500.00, 500.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(86, 24, 'regular', 2, 2024, 500.00, 500.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(87, 24, 'regular', 3, 2025, 500.00, 500.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(88, 24, 'regular', 4, 2025, 500.00, 500.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(89, 24, 'regular', 5, 2025, 500.00, 500.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(90, 24, 'regular', 6, 2026, 500.00, 500.00, 'paid', '2026-05-01 12:00:00', '2026-04-20 12:00:00', NULL),
(91, 24, 'regular', 7, 2026, 500.00, 500.00, 'paid', '2026-05-01 12:00:00', '2026-04-20 12:00:00', NULL),
(92, 24, 'regular', 8, 2026, 500.00, 500.00, 'paid', '2026-01-10 14:00:00', '2026-01-05 13:00:00', NULL),
(93, 25, 'regular', 1, 2026, 550.00, 550.00, 'paid', '2024-01-10 14:00:00', '2024-01-05 13:00:00', NULL),
(94, 26, 'regular', 1, 2026, 550.00, 550.00, 'paid', '2024-01-10 14:00:00', '2024-01-05 13:00:00', NULL),
(95, 27, 'regular', 1, 2024, 550.00, 550.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(96, 27, 'regular', 2, 2026, 550.00, 550.00, 'paid', '2024-01-10 14:00:00', '2024-01-05 13:00:00', NULL),
(97, 28, 'regular', 1, 2024, 550.00, 550.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(98, 28, 'regular', 2, 2026, 550.00, 550.00, 'paid', '2024-01-10 14:00:00', '2024-01-05 13:00:00', NULL),
(99, 29, 'regular', 1, 2024, 550.00, 550.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(100, 29, 'regular', 2, 2024, 550.00, 550.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(101, 29, 'regular', 3, 2026, 550.00, 550.00, 'paid', '2025-01-10 14:00:00', '2025-01-05 13:00:00', NULL),
(102, 30, 'regular', 1, 2024, 550.00, 550.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(103, 30, 'regular', 2, 2024, 550.00, 550.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(104, 30, 'regular', 3, 2026, 550.00, 550.00, 'paid', '2025-01-10 14:00:00', '2025-01-05 13:00:00', NULL),
(105, 31, 'regular', 1, 2024, 550.00, 550.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(106, 31, 'regular', 2, 2024, 550.00, 550.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(107, 31, 'regular', 3, 2025, 550.00, 550.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(108, 31, 'regular', 4, 2026, 550.00, 550.00, 'paid', '2025-01-10 14:00:00', '2025-01-05 13:00:00', NULL),
(109, 32, 'regular', 1, 2024, 550.00, 550.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(110, 32, 'regular', 2, 2024, 550.00, 550.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(111, 32, 'regular', 3, 2025, 550.00, 550.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(112, 32, 'regular', 4, 2026, 550.00, 550.00, 'paid', '2025-01-10 14:00:00', '2025-01-05 13:00:00', NULL),
(113, 33, 'regular', 1, 2024, 550.00, 550.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(114, 33, 'regular', 2, 2024, 550.00, 550.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(115, 33, 'regular', 3, 2025, 550.00, 550.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(116, 33, 'regular', 4, 2025, 550.00, 550.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(117, 33, 'regular', 5, 2026, 550.00, 550.00, 'paid', '2025-01-10 14:00:00', '2025-01-05 13:00:00', NULL),
(118, 34, 'regular', 1, 2024, 550.00, 550.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(119, 34, 'regular', 2, 2024, 550.00, 550.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(120, 34, 'regular', 3, 2025, 550.00, 550.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(121, 34, 'regular', 4, 2025, 550.00, 550.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(122, 34, 'regular', 5, 2025, 550.00, 550.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(123, 34, 'regular', 6, 2026, 550.00, 550.00, 'paid', '2026-01-10 14:00:00', '2026-01-05 13:00:00', NULL),
(124, 35, 'regular', 1, 2024, 550.00, 550.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(125, 35, 'regular', 2, 2024, 550.00, 550.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(126, 35, 'regular', 3, 2025, 550.00, 550.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(127, 35, 'regular', 4, 2025, 550.00, 550.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(128, 35, 'regular', 5, 2025, 550.00, 550.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(129, 35, 'regular', 6, 2026, 550.00, 550.00, 'paid', '2026-05-01 12:00:00', '2026-04-20 12:00:00', NULL),
(130, 35, 'regular', 7, 2026, 550.00, 550.00, 'paid', '2026-01-10 14:00:00', '2026-01-05 13:00:00', NULL),
(131, 36, 'regular', 1, 2024, 550.00, 550.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(132, 36, 'regular', 2, 2024, 550.00, 550.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(133, 36, 'regular', 3, 2025, 550.00, 550.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(134, 36, 'regular', 4, 2025, 550.00, 550.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(135, 36, 'regular', 5, 2025, 550.00, 550.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(136, 36, 'regular', 6, 2026, 550.00, 550.00, 'paid', '2026-05-01 12:00:00', '2026-04-20 12:00:00', NULL),
(137, 36, 'regular', 7, 2026, 550.00, 550.00, 'paid', '2026-05-01 12:00:00', '2026-04-20 12:00:00', NULL),
(138, 36, 'regular', 8, 2026, 550.00, 550.00, 'paid', '2026-01-10 14:00:00', '2026-01-05 13:00:00', NULL),
(139, 37, 'regular', 1, 2026, 550.00, 550.00, 'paid', '2024-01-10 14:00:00', '2024-01-05 13:00:00', NULL),
(140, 38, 'regular', 1, 2026, 550.00, 550.00, 'paid', '2024-01-10 14:00:00', '2024-01-05 13:00:00', NULL),
(141, 39, 'regular', 1, 2024, 550.00, 550.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(142, 39, 'regular', 2, 2026, 550.00, 550.00, 'paid', '2024-01-10 14:00:00', '2024-01-05 13:00:00', NULL),
(143, 40, 'regular', 1, 2024, 550.00, 550.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(144, 40, 'regular', 2, 2026, 550.00, 550.00, 'paid', '2024-01-10 14:00:00', '2024-01-05 13:00:00', NULL),
(145, 41, 'regular', 1, 2024, 550.00, 550.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(146, 41, 'regular', 2, 2024, 550.00, 550.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(147, 41, 'regular', 3, 2026, 550.00, 550.00, 'paid', '2025-01-10 14:00:00', '2025-01-05 13:00:00', NULL),
(148, 42, 'regular', 1, 2024, 550.00, 550.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(149, 42, 'regular', 2, 2024, 550.00, 550.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(150, 42, 'regular', 3, 2026, 550.00, 550.00, 'paid', '2025-01-10 14:00:00', '2025-01-05 13:00:00', NULL),
(151, 43, 'regular', 1, 2024, 550.00, 550.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(152, 43, 'regular', 2, 2024, 550.00, 550.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(153, 43, 'regular', 3, 2025, 550.00, 550.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(154, 43, 'regular', 4, 2026, 550.00, 550.00, 'paid', '2025-01-10 14:00:00', '2025-01-05 13:00:00', NULL),
(155, 44, 'regular', 1, 2024, 550.00, 550.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(156, 44, 'regular', 2, 2024, 550.00, 550.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(157, 44, 'regular', 3, 2025, 550.00, 550.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(158, 44, 'regular', 4, 2026, 550.00, 550.00, 'paid', '2025-01-10 14:00:00', '2025-01-05 13:00:00', NULL),
(159, 45, 'regular', 1, 2024, 550.00, 550.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(160, 45, 'regular', 2, 2024, 550.00, 550.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(161, 45, 'regular', 3, 2025, 550.00, 550.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(162, 45, 'regular', 4, 2025, 550.00, 550.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(163, 45, 'regular', 5, 2026, 550.00, 550.00, 'paid', '2025-01-10 14:00:00', '2025-01-05 13:00:00', NULL),
(164, 46, 'regular', 1, 2024, 550.00, 550.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(165, 46, 'regular', 2, 2024, 550.00, 550.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(166, 46, 'regular', 3, 2025, 550.00, 550.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(167, 46, 'regular', 4, 2025, 550.00, 550.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(168, 46, 'regular', 5, 2025, 550.00, 550.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(169, 46, 'regular', 6, 2026, 550.00, 550.00, 'paid', '2026-01-10 14:00:00', '2026-01-05 13:00:00', NULL),
(170, 47, 'regular', 1, 2024, 550.00, 550.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(171, 47, 'regular', 2, 2024, 550.00, 550.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(172, 47, 'regular', 3, 2025, 550.00, 550.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(173, 47, 'regular', 4, 2025, 550.00, 550.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(174, 47, 'regular', 5, 2025, 550.00, 550.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(175, 47, 'regular', 6, 2026, 550.00, 550.00, 'paid', '2026-05-01 12:00:00', '2026-04-20 12:00:00', NULL),
(176, 47, 'regular', 7, 2026, 550.00, 550.00, 'paid', '2026-01-10 14:00:00', '2026-01-05 13:00:00', NULL),
(177, 49, 'regular', 1, 2026, 400.00, 400.00, 'paid', '2024-01-10 14:00:00', '2024-01-05 13:00:00', NULL),
(178, 50, 'regular', 1, 2026, 400.00, 400.00, 'paid', '2024-01-10 14:00:00', '2024-01-05 13:00:00', NULL),
(179, 51, 'regular', 1, 2024, 400.00, 400.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(180, 51, 'regular', 2, 2026, 400.00, 400.00, 'paid', '2024-01-10 14:00:00', '2024-01-05 13:00:00', NULL),
(181, 52, 'regular', 1, 2024, 400.00, 400.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(182, 52, 'regular', 2, 2026, 400.00, 400.00, 'paid', '2024-01-10 14:00:00', '2024-01-05 13:00:00', NULL),
(183, 53, 'regular', 1, 2024, 400.00, 400.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(184, 53, 'regular', 2, 2024, 400.00, 400.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(185, 53, 'regular', 3, 2026, 400.00, 400.00, 'paid', '2025-01-10 14:00:00', '2025-01-05 13:00:00', NULL),
(186, 54, 'regular', 1, 2024, 400.00, 400.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(187, 54, 'regular', 2, 2024, 400.00, 400.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(188, 54, 'regular', 3, 2026, 400.00, 400.00, 'paid', '2025-01-10 14:00:00', '2025-01-05 13:00:00', NULL),
(189, 55, 'regular', 1, 2024, 400.00, 400.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(190, 55, 'regular', 2, 2024, 400.00, 400.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(191, 55, 'regular', 3, 2025, 400.00, 400.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(192, 55, 'regular', 4, 2026, 400.00, 400.00, 'paid', '2025-01-10 14:00:00', '2025-01-05 13:00:00', NULL),
(193, 56, 'regular', 1, 2024, 400.00, 400.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(194, 56, 'regular', 2, 2024, 400.00, 400.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(195, 56, 'regular', 3, 2025, 400.00, 400.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(196, 56, 'regular', 4, 2026, 400.00, 400.00, 'paid', '2025-01-10 14:00:00', '2025-01-05 13:00:00', NULL),
(197, 57, 'regular', 1, 2024, 400.00, 400.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(198, 57, 'regular', 2, 2024, 400.00, 400.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(199, 57, 'regular', 3, 2025, 400.00, 400.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(200, 57, 'regular', 4, 2025, 400.00, 400.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(201, 57, 'regular', 5, 2026, 400.00, 400.00, 'paid', '2025-01-10 14:00:00', '2025-01-05 13:00:00', NULL),
(202, 58, 'regular', 1, 2024, 400.00, 400.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(203, 58, 'regular', 2, 2024, 400.00, 400.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(204, 58, 'regular', 3, 2025, 400.00, 400.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(205, 58, 'regular', 4, 2025, 400.00, 400.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(206, 58, 'regular', 5, 2025, 400.00, 400.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(207, 58, 'regular', 6, 2026, 400.00, 400.00, 'paid', '2026-01-10 14:00:00', '2026-01-05 13:00:00', NULL),
(208, 59, 'regular', 1, 2024, 400.00, 400.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(209, 59, 'regular', 2, 2024, 400.00, 400.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(210, 59, 'regular', 3, 2025, 400.00, 400.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(211, 59, 'regular', 4, 2025, 400.00, 400.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(212, 59, 'regular', 5, 2025, 400.00, 400.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(213, 59, 'regular', 6, 2026, 400.00, 400.00, 'paid', '2026-05-01 12:00:00', '2026-04-20 12:00:00', NULL),
(214, 59, 'regular', 7, 2026, 400.00, 400.00, 'paid', '2026-01-10 14:00:00', '2026-01-05 13:00:00', NULL),
(215, 60, 'regular', 1, 2024, 400.00, 400.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(216, 60, 'regular', 2, 2024, 400.00, 400.00, 'paid', '2024-05-01 12:00:00', '2024-04-20 12:00:00', NULL),
(217, 60, 'regular', 3, 2025, 400.00, 400.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(218, 60, 'regular', 4, 2025, 400.00, 400.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(219, 60, 'regular', 5, 2025, 400.00, 400.00, 'paid', '2025-05-01 12:00:00', '2025-04-20 12:00:00', NULL),
(220, 60, 'regular', 6, 2026, 400.00, 400.00, 'paid', '2026-05-01 12:00:00', '2026-04-20 12:00:00', NULL),
(221, 60, 'regular', 7, 2026, 400.00, 400.00, 'paid', '2026-05-01 12:00:00', '2026-04-20 12:00:00', NULL),
(222, 60, 'regular', 8, 2026, 400.00, 400.00, 'paid', '2026-01-10 14:00:00', '2026-01-05 13:00:00', NULL),
(223, 8, 'supplemental', 4, 2026, 60.00, 60.00, 'paid', '2026-01-20 15:00:00', '2025-12-15 13:00:00', 7),
(224, 9, 'supplemental', 5, 2026, 50.00, 50.00, 'paid', '2026-01-20 15:00:00', '2025-12-15 13:00:00', 6),
(225, 9, 'supplemental', 5, 2026, 50.00, 50.00, 'paid', '2026-01-20 15:00:00', '2025-12-15 13:00:00', 9),
(226, 10, 'supplemental', 6, 2026, 60.00, 60.00, 'paid', '2025-08-20 13:00:00', '2025-08-15 12:00:00', 8),
(227, 48, 'regular', 8, 2026, 550.00, 550.00, 'paid', '2026-01-15 14:00:00', '2026-01-01 13:00:00', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `hods`
--

CREATE TABLE `hods` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `teacher_id` bigint(20) UNSIGNED NOT NULL,
  `department_id` bigint(20) UNSIGNED NOT NULL,
  `appointed_date` date DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hods`
--

INSERT INTO `hods` (`id`, `user_id`, `teacher_id`, `department_id`, `appointed_date`, `status`, `created_at`) VALUES
(1, 2, 1, 1, '2024-01-15', 'active', '2024-01-15 13:00:00'),
(2, 3, 4, 2, '2024-01-15', 'active', '2024-01-15 13:00:00'),
(3, 4, 7, 3, '2024-01-15', 'active', '2024-01-15 13:00:00'),
(4, 5, 10, 4, '2024-01-15', 'active', '2024-01-15 13:00:00'),
(5, 6, 13, 5, '2024-01-15', 'active', '2024-01-15 13:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2014_10_12_000000_create_users_table', 1),
(2, '2014_10_12_100000_create_password_resets_table', 1),
(3, '2019_08_19_000000_create_failed_jobs_table', 1),
(4, '2019_12_14_000001_create_personal_access_tokens_table', 1),
(5, '2025_01_01_000001_create_departments_table', 1),
(6, '2025_01_01_000002_create_teachers_table', 1),
(7, '2025_01_01_000003_create_students_table', 1),
(8, '2025_01_01_000004_create_courses_table', 1),
(9, '2025_01_01_000005_create_rooms_table', 1),
(10, '2025_01_01_000006_create_hods_table', 1),
(11, '2025_01_01_000007_create_course_sections_table', 1),
(12, '2025_01_01_000008_create_course_assignments_table', 1),
(13, '2025_01_01_000009_create_timetables_table', 1),
(14, '2025_01_01_000010_create_timetable_slots_table', 1),
(15, '2025_01_01_000011_create_conflicts_table', 1),
(16, '2025_01_01_000012_create_activity_logs_table', 1),
(17, '2025_01_01_000013_create_student_course_registrations_table', 1),
(18, '2025_01_01_000014_create_teacher_availability_table', 1),
(19, '2025_01_01_000015_add_semester_to_courses_table', 1),
(20, '2025_01_01_000015_create_room_availability_table', 1),
(21, '2025_01_01_000016_create_fee_payments_table', 1),
(22, '2025_01_01_000017_add_fee_to_courses_table', 1),
(23, '2025_01_01_000018_add_partial_to_fee_payments', 1),
(24, '2025_01_01_000019_add_registration_fee_to_departments_table', 1),
(25, '2026_03_25_000002_add_semester_1_result_to_students_table', 1),
(26, '2026_03_25_000003_add_prerequisite_course_code_to_courses_table', 1),
(27, '2026_03_25_000004_add_result_to_student_course_registrations_table', 1),
(28, '2026_03_25_000005_add_type_and_course_id_to_fee_payments_table', 1),
(29, '2026_03_25_153057_remove_resolved_at_and_student_conflict_from_conflicts_table', 1),
(30, '2026_03_26_000001_add_prerequisite_mandatory_to_courses_table', 1),
(31, '2026_04_04_000001_improve_conflicts_table', 1),
(32, '2026_04_04_000002_add_indexes_to_timetable_slots', 1),
(33, '2026_04_04_000003_add_weekly_sessions_to_courses', 1);

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `room_number` varchar(255) NOT NULL,
  `building` varchar(255) NOT NULL,
  `type` enum('classroom','lab','seminar_hall') NOT NULL,
  `capacity` int(11) NOT NULL,
  `equipment` text DEFAULT NULL,
  `status` enum('available','unavailable','maintenance') NOT NULL DEFAULT 'available',
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `room_number`, `building`, `type`, `capacity`, `equipment`, `status`, `created_at`) VALUES
(1, 'A101', 'Building A', 'classroom', 35, 'Projector, Whiteboard', 'available', '2024-01-15 13:00:00'),
(2, 'A102', 'Building A', 'classroom', 35, 'Projector, Whiteboard', 'available', '2024-01-15 13:00:00'),
(3, 'A201', 'Building A', 'classroom', 40, 'Projector, Smart Board', 'available', '2024-01-15 13:00:00'),
(4, 'A202', 'Building A', 'classroom', 40, 'Projector, Smart Board', 'available', '2024-01-15 13:00:00'),
(5, 'A301', 'Building A', 'classroom', 30, 'Projector', 'maintenance', '2024-01-15 13:00:00'),
(6, 'B101', 'Building B', 'lab', 25, 'Computers x25, Projector', 'available', '2024-01-15 13:00:00'),
(7, 'B102', 'Building B', 'lab', 25, 'Computers x25, Projector', 'available', '2024-01-15 13:00:00'),
(8, 'B201', 'Building B', 'lab', 20, 'Computers x20, Oscilloscopes', 'available', '2024-01-15 13:00:00'),
(9, 'B202', 'Building B', 'lab', 20, 'Computers x20, Network Equipment', 'available', '2024-01-15 13:00:00'),
(10, 'B301', 'Building B', 'lab', 24, 'Computers x24, Printers', 'available', '2024-01-15 13:00:00'),
(11, 'C101', 'Building C', 'seminar_hall', 60, 'Projector, Sound System', 'available', '2024-01-15 13:00:00'),
(12, 'C102', 'Building C', 'seminar_hall', 55, 'Projector, Sound System', 'available', '2024-01-15 13:00:00'),
(13, 'C201', 'Building C', 'seminar_hall', 50, 'Projector, Smart Board', 'available', '2024-01-15 13:00:00'),
(14, 'C202', 'Building C', 'seminar_hall', 65, 'Projector, Video Conferencing', 'available', '2024-01-15 13:00:00'),
(15, 'C301', 'Building C', 'seminar_hall', 50, 'Projector', 'unavailable', '2024-01-15 13:00:00'),
(16, 'D101', 'Building D', 'classroom', 45, 'Projector, Whiteboard', 'available', '2024-01-15 13:00:00'),
(17, 'D102', 'Building D', 'classroom', 45, 'Projector, Smart Board', 'available', '2024-01-15 13:00:00'),
(18, 'D201', 'Building D', 'classroom', 50, 'Projector, Smart Board', 'available', '2024-01-15 13:00:00'),
(19, 'D202', 'Building D', 'seminar_hall', 80, 'Projector, Sound System', 'available', '2024-01-15 13:00:00'),
(20, 'E101', 'Building E', 'lab', 30, 'Computers x30, Projector', 'available', '2024-01-15 13:00:00'),
(21, 'E102', 'Building E', 'lab', 30, 'Computers x30, Networking Kits', 'available', '2024-01-15 13:00:00'),
(22, 'E201', 'Building E', 'lab', 28, 'Computers x28, Electronics Kits', 'available', '2024-01-15 13:00:00'),
(23, 'F101', 'Building F', 'classroom', 60, 'Projector, Smart Board', 'available', '2024-01-15 13:00:00'),
(24, 'F201', 'Building F', 'seminar_hall', 100, 'Projector, Video Conferencing, Sound System', 'available', '2024-01-15 13:00:00'),
(25, 'G101', 'Building G', 'classroom', 40, 'Projector, Whiteboard', 'available', '2024-01-15 13:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `room_availability`
--

CREATE TABLE `room_availability` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `room_id` bigint(20) UNSIGNED NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `status` enum('available','unavailable') NOT NULL DEFAULT 'available',
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `room_availability`
--

INSERT INTO `room_availability` (`id`, `room_id`, `day_of_week`, `start_time`, `end_time`, `status`, `created_at`) VALUES
(1, 1, 'Monday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(2, 1, 'Tuesday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(3, 1, 'Wednesday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(4, 1, 'Thursday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(5, 1, 'Friday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(6, 2, 'Monday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(7, 2, 'Tuesday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(8, 2, 'Wednesday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(9, 2, 'Thursday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(10, 2, 'Friday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(11, 3, 'Monday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(12, 3, 'Tuesday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(13, 3, 'Wednesday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(14, 3, 'Thursday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(15, 3, 'Friday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(16, 4, 'Monday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(17, 4, 'Tuesday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(18, 4, 'Wednesday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(19, 4, 'Thursday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(20, 4, 'Friday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(21, 5, 'Monday', '08:00:00', '17:00:00', 'unavailable', '2025-12-01 13:00:00'),
(22, 5, 'Tuesday', '08:00:00', '17:00:00', 'unavailable', '2025-12-01 13:00:00'),
(23, 5, 'Wednesday', '08:00:00', '17:00:00', 'unavailable', '2025-12-01 13:00:00'),
(24, 5, 'Thursday', '08:00:00', '17:00:00', 'unavailable', '2025-12-01 13:00:00'),
(25, 5, 'Friday', '08:00:00', '17:00:00', 'unavailable', '2025-12-01 13:00:00'),
(26, 6, 'Monday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(27, 6, 'Tuesday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(28, 6, 'Wednesday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(29, 6, 'Thursday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(30, 6, 'Friday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(31, 7, 'Monday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(32, 7, 'Tuesday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(33, 7, 'Wednesday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(34, 7, 'Thursday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(35, 7, 'Friday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(36, 8, 'Monday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(37, 8, 'Tuesday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(38, 8, 'Wednesday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(39, 8, 'Thursday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(40, 8, 'Friday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(41, 9, 'Monday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(42, 9, 'Tuesday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(43, 9, 'Wednesday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(44, 9, 'Thursday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(45, 9, 'Friday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(46, 10, 'Monday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(47, 10, 'Tuesday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(48, 10, 'Wednesday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(49, 10, 'Thursday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(50, 10, 'Friday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(51, 11, 'Monday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(52, 11, 'Tuesday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(53, 11, 'Wednesday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(54, 11, 'Thursday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(55, 11, 'Friday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(56, 12, 'Monday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(57, 12, 'Tuesday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(58, 12, 'Wednesday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(59, 12, 'Thursday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(60, 12, 'Friday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(61, 13, 'Monday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(62, 13, 'Tuesday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(63, 13, 'Wednesday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(64, 13, 'Thursday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(65, 13, 'Friday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(66, 14, 'Monday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(67, 14, 'Tuesday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(68, 14, 'Wednesday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(69, 14, 'Thursday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(70, 14, 'Friday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(71, 15, 'Monday', '08:00:00', '17:00:00', 'unavailable', '2025-12-01 13:00:00'),
(72, 15, 'Tuesday', '08:00:00', '17:00:00', 'unavailable', '2025-12-01 13:00:00'),
(73, 15, 'Wednesday', '08:00:00', '17:00:00', 'unavailable', '2025-12-01 13:00:00'),
(74, 15, 'Thursday', '08:00:00', '17:00:00', 'unavailable', '2025-12-01 13:00:00'),
(75, 15, 'Friday', '08:00:00', '17:00:00', 'unavailable', '2025-12-01 13:00:00'),
(76, 16, 'Monday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(77, 16, 'Tuesday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(78, 16, 'Wednesday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(79, 16, 'Thursday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(80, 16, 'Friday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(81, 17, 'Monday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(82, 17, 'Tuesday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(83, 17, 'Wednesday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(84, 17, 'Thursday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(85, 17, 'Friday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(86, 18, 'Monday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(87, 18, 'Tuesday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(88, 18, 'Wednesday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(89, 18, 'Thursday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(90, 18, 'Friday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(91, 19, 'Monday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(92, 19, 'Tuesday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(93, 19, 'Wednesday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(94, 19, 'Thursday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(95, 19, 'Friday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(96, 20, 'Monday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(97, 20, 'Tuesday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(98, 20, 'Wednesday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(99, 20, 'Thursday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(100, 20, 'Friday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(101, 21, 'Monday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(102, 21, 'Tuesday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(103, 21, 'Wednesday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(104, 21, 'Thursday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(105, 21, 'Friday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(106, 22, 'Monday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(107, 22, 'Tuesday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(108, 22, 'Wednesday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(109, 22, 'Thursday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(110, 22, 'Friday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(111, 23, 'Monday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(112, 23, 'Tuesday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(113, 23, 'Wednesday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(114, 23, 'Thursday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(115, 23, 'Friday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(116, 24, 'Monday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(117, 24, 'Tuesday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(118, 24, 'Wednesday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(119, 24, 'Thursday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(120, 24, 'Friday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(121, 25, 'Monday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(122, 25, 'Tuesday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(123, 25, 'Wednesday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(124, 25, 'Thursday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00'),
(125, 25, 'Friday', '08:00:00', '17:00:00', 'available', '2025-12-01 13:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `roll_no` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `department_id` bigint(20) UNSIGNED NOT NULL,
  `semester` int(11) NOT NULL,
  `semester_1_result` varchar(255) NOT NULL DEFAULT 'N/A',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `user_id`, `roll_no`, `name`, `email`, `department_id`, `semester`, `semester_1_result`, `status`, `created_at`) VALUES
(1, 22, 'CS20240001', 'Alice Smith', 'alice.smith@student.university.edu', 1, 1, 'N/A', 'active', '2024-08-20 12:00:00'),
(2, 23, 'CS20240002', 'Bob Johnson', 'bob.johnson@student.university.edu', 1, 1, 'N/A', 'active', '2024-08-20 12:00:00'),
(3, 24, 'CS20240003', 'Charlie Williams', 'charlie.williams@student.university.edu', 1, 2, 'N/A', 'active', '2024-08-20 12:00:00'),
(4, 25, 'CS20240004', 'Diana Brown', 'diana.brown@student.university.edu', 1, 2, 'N/A', 'active', '2024-08-20 12:00:00'),
(5, 26, 'CS20240005', 'Edward Jones', 'edward.jones@student.university.edu', 1, 3, 'N/A', 'active', '2024-08-20 12:00:00'),
(6, 27, 'CS20240006', 'Fatima Garcia', 'fatima.garcia@student.university.edu', 1, 3, 'N/A', 'active', '2024-08-20 12:00:00'),
(7, 28, 'CS20240007', 'George Miller', 'george.miller@student.university.edu', 1, 4, 'N/A', 'active', '2024-08-20 12:00:00'),
(8, 29, 'CS20240008', 'Hannah Davis', 'hannah.davis@student.university.edu', 1, 4, 'N/A', 'active', '2024-08-20 12:00:00'),
(9, 30, 'CS20230009', 'Ivan Wilson', 'ivan.wilson@student.university.edu', 1, 5, 'N/A', 'active', '2024-08-20 12:00:00'),
(10, 31, 'CS20230010', 'Julia Moore', 'julia.moore@student.university.edu', 1, 6, 'N/A', 'active', '2024-08-20 12:00:00'),
(11, 32, 'CS20230011', 'Kevin Taylor', 'kevin.taylor@student.university.edu', 1, 7, 'N/A', 'active', '2024-08-20 12:00:00'),
(12, 33, 'CS20230012', 'Laura Anderson', 'laura.anderson@student.university.edu', 1, 8, 'N/A', 'active', '2024-08-20 12:00:00'),
(13, 34, 'IT20240013', 'Mohammed Thomas', 'mohammed.thomas@student.university.edu', 2, 1, 'N/A', 'active', '2024-08-20 12:00:00'),
(14, 35, 'IT20240014', 'Natalie Jackson', 'natalie.jackson@student.university.edu', 2, 1, 'N/A', 'active', '2024-08-20 12:00:00'),
(15, 36, 'IT20240015', 'Oscar White', 'oscar.white@student.university.edu', 2, 2, 'N/A', 'active', '2024-08-20 12:00:00'),
(16, 37, 'IT20240016', 'Patricia Harris', 'patricia.harris@student.university.edu', 2, 2, 'N/A', 'active', '2024-08-20 12:00:00'),
(17, 38, 'IT20240017', 'Quinn Martin', 'quinn.martin@student.university.edu', 2, 3, 'N/A', 'active', '2024-08-20 12:00:00'),
(18, 39, 'IT20240018', 'Rachel Thompson', 'rachel.thompson@student.university.edu', 2, 3, 'N/A', 'active', '2024-08-20 12:00:00'),
(19, 40, 'IT20240019', 'Samuel Robinson', 'samuel.robinson@student.university.edu', 2, 4, 'N/A', 'active', '2024-08-20 12:00:00'),
(20, 41, 'IT20240020', 'Teresa Clark', 'teresa.clark@student.university.edu', 2, 4, 'N/A', 'active', '2024-08-20 12:00:00'),
(21, 42, 'IT20230021', 'Uma Lewis', 'uma.lewis@student.university.edu', 2, 5, 'N/A', 'active', '2024-08-20 12:00:00'),
(22, 43, 'IT20230022', 'Victor Lee', 'victor.lee@student.university.edu', 2, 6, 'N/A', 'active', '2024-08-20 12:00:00'),
(23, 44, 'IT20230023', 'Wendy Walker', 'wendy.walker@student.university.edu', 2, 7, 'N/A', 'active', '2024-08-20 12:00:00'),
(24, 45, 'IT20230024', 'Xavier Hall', 'xavier.hall@student.university.edu', 2, 8, 'N/A', 'active', '2024-08-20 12:00:00'),
(25, 46, 'EE20240025', 'Yolanda Allen', 'yolanda.allen@student.university.edu', 3, 1, 'N/A', 'active', '2024-08-20 12:00:00'),
(26, 47, 'EE20240026', 'Zachary Young', 'zachary.young@student.university.edu', 3, 1, 'N/A', 'active', '2024-08-20 12:00:00'),
(27, 48, 'EE20240027', 'Amy King', 'amy.king@student.university.edu', 3, 2, 'N/A', 'active', '2024-08-20 12:00:00'),
(28, 49, 'EE20240028', 'Brian Wright', 'brian.wright@student.university.edu', 3, 2, 'N/A', 'active', '2024-08-20 12:00:00'),
(29, 50, 'EE20240029', 'Clara Hill', 'clara.hill@student.university.edu', 3, 3, 'N/A', 'active', '2024-08-20 12:00:00'),
(30, 51, 'EE20240030', 'Derek Scott', 'derek.scott@student.university.edu', 3, 3, 'N/A', 'active', '2024-08-20 12:00:00'),
(31, 52, 'EE20240031', 'Elena Green', 'elena.green@student.university.edu', 3, 4, 'N/A', 'active', '2024-08-20 12:00:00'),
(32, 53, 'EE20240032', 'Frank Adams', 'frank.adams@student.university.edu', 3, 4, 'N/A', 'active', '2024-08-20 12:00:00'),
(33, 54, 'EE20230033', 'Grace Baker', 'grace.baker@student.university.edu', 3, 5, 'N/A', 'active', '2024-08-20 12:00:00'),
(34, 55, 'EE20230034', 'Henry Nelson', 'henry.nelson@student.university.edu', 3, 6, 'N/A', 'active', '2024-08-20 12:00:00'),
(35, 56, 'EE20230035', 'Iris Carter', 'iris.carter@student.university.edu', 3, 7, 'N/A', 'active', '2024-08-20 12:00:00'),
(36, 57, 'EE20230036', 'Jack Mitchell', 'jack.mitchell@student.university.edu', 3, 8, 'N/A', 'active', '2024-08-20 12:00:00'),
(37, 58, 'ME20240037', 'Karen Perez', 'karen.perez@student.university.edu', 4, 1, 'N/A', 'active', '2024-08-20 12:00:00'),
(38, 59, 'ME20240038', 'Louis Roberts', 'louis.roberts@student.university.edu', 4, 1, 'N/A', 'active', '2024-08-20 12:00:00'),
(39, 60, 'ME20240039', 'Maria Turner', 'maria.turner@student.university.edu', 4, 2, 'N/A', 'active', '2024-08-20 12:00:00'),
(40, 61, 'ME20240040', 'Nathan Phillips', 'nathan.phillips@student.university.edu', 4, 2, 'N/A', 'active', '2024-08-20 12:00:00'),
(41, 62, 'ME20240041', 'Olivia Campbell', 'olivia.campbell@student.university.edu', 4, 3, 'N/A', 'active', '2024-08-20 12:00:00'),
(42, 63, 'ME20240042', 'Peter Parker', 'peter.parker@student.university.edu', 4, 3, 'N/A', 'active', '2024-08-20 12:00:00'),
(43, 64, 'ME20240043', 'Queenie Evans', 'queenie.evans@student.university.edu', 4, 4, 'N/A', 'active', '2024-08-20 12:00:00'),
(44, 65, 'ME20240044', 'Ryan Edwards', 'ryan.edwards@student.university.edu', 4, 4, 'N/A', 'active', '2024-08-20 12:00:00'),
(45, 66, 'ME20230045', 'Sara Collins', 'sara.collins@student.university.edu', 4, 5, 'N/A', 'active', '2024-08-20 12:00:00'),
(46, 67, 'ME20230046', 'Tom Stewart', 'tom.stewart@student.university.edu', 4, 6, 'N/A', 'active', '2024-08-20 12:00:00'),
(47, 68, 'ME20230047', 'Ursula Morris', 'ursula.morris@student.university.edu', 4, 7, 'N/A', 'active', '2024-08-20 12:00:00'),
(48, 69, 'ME20230048', 'Vincent Reed', 'vincent.reed@student.university.edu', 4, 8, 'N/A', 'inactive', '2024-08-20 12:00:00'),
(49, 70, 'BBA20240049', 'Wanda Cook', 'wanda.cook@student.university.edu', 5, 1, 'N/A', 'active', '2024-08-20 12:00:00'),
(50, 71, 'BBA20240050', 'Xander Rogers', 'xander.rogers@student.university.edu', 5, 1, 'N/A', 'active', '2024-08-20 12:00:00'),
(51, 72, 'BBA20240051', 'Yvonne Morgan', 'yvonne.morgan@student.university.edu', 5, 2, 'N/A', 'active', '2024-08-20 12:00:00'),
(52, 73, 'BBA20240052', 'Zara Bell', 'zara.bell@student.university.edu', 5, 2, 'N/A', 'active', '2024-08-20 12:00:00'),
(53, 74, 'BBA20240053', 'Aaron Murphy', 'aaron.murphy@student.university.edu', 5, 3, 'N/A', 'active', '2024-08-20 12:00:00'),
(54, 75, 'BBA20240054', 'Betty Bailey', 'betty.bailey@student.university.edu', 5, 3, 'N/A', 'active', '2024-08-20 12:00:00'),
(55, 76, 'BBA20240055', 'Carlos Rivera', 'carlos.rivera@student.university.edu', 5, 4, 'N/A', 'active', '2024-08-20 12:00:00'),
(56, 77, 'BBA20240056', 'Dorothy Cooper', 'dorothy.cooper@student.university.edu', 5, 4, 'N/A', 'active', '2024-08-20 12:00:00'),
(57, 78, 'BBA20230057', 'Ethan Richardson', 'ethan.richardson@student.university.edu', 5, 5, 'N/A', 'active', '2024-08-20 12:00:00'),
(58, 79, 'BBA20230058', 'Florence Cox', 'florence.cox@student.university.edu', 5, 6, 'N/A', 'active', '2024-08-20 12:00:00'),
(59, 80, 'BBA20230059', 'Gabe Howard', 'gabe.howard@student.university.edu', 5, 7, 'N/A', 'active', '2024-08-20 12:00:00'),
(60, 81, 'BBA20230060', 'Hina Ward', 'hina.ward@student.university.edu', 5, 8, 'N/A', 'active', '2024-08-20 12:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `student_course_registrations`
--

CREATE TABLE `student_course_registrations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `course_section_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('enrolled','dropped','completed') NOT NULL DEFAULT 'enrolled',
  `result` enum('pass','fail') DEFAULT NULL,
  `registered_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_course_registrations`
--

INSERT INTO `student_course_registrations` (`id`, `student_id`, `course_section_id`, `status`, `result`, `registered_at`) VALUES
(1, 1, 181, 'enrolled', NULL, '2025-12-15 15:00:00'),
(2, 1, 182, 'enrolled', NULL, '2025-12-15 15:00:00'),
(3, 1, 183, 'enrolled', NULL, '2025-12-15 15:00:00'),
(4, 2, 181, 'enrolled', NULL, '2025-12-15 15:00:00'),
(5, 2, 182, 'enrolled', NULL, '2025-12-15 15:00:00'),
(6, 2, 183, 'enrolled', NULL, '2025-12-15 15:00:00'),
(7, 3, 97, 'completed', 'pass', '2025-01-15 13:00:00'),
(8, 3, 98, 'completed', 'pass', '2025-01-15 13:00:00'),
(9, 3, 99, 'completed', 'pass', '2025-01-15 13:00:00'),
(10, 3, 184, 'enrolled', NULL, '2025-12-15 15:00:00'),
(11, 3, 185, 'enrolled', NULL, '2025-12-15 15:00:00'),
(12, 3, 186, 'enrolled', NULL, '2025-12-15 15:00:00'),
(13, 4, 97, 'completed', 'pass', '2025-01-15 13:00:00'),
(14, 4, 98, 'completed', 'pass', '2025-01-15 13:00:00'),
(15, 4, 99, 'completed', 'pass', '2025-01-15 13:00:00'),
(16, 4, 184, 'enrolled', NULL, '2025-12-15 15:00:00'),
(17, 4, 185, 'enrolled', NULL, '2025-12-15 15:00:00'),
(18, 4, 186, 'enrolled', NULL, '2025-12-15 15:00:00'),
(19, 5, 97, 'completed', 'pass', '2025-01-15 13:00:00'),
(20, 5, 98, 'completed', 'pass', '2025-01-15 13:00:00'),
(21, 5, 99, 'completed', 'pass', '2025-01-15 13:00:00'),
(22, 5, 100, 'completed', 'pass', '2025-08-15 12:00:00'),
(23, 5, 101, 'completed', 'pass', '2025-08-15 12:00:00'),
(24, 5, 102, 'completed', 'pass', '2025-08-15 12:00:00'),
(25, 5, 187, 'enrolled', NULL, '2025-12-15 15:00:00'),
(26, 5, 188, 'enrolled', NULL, '2025-12-15 15:00:00'),
(27, 5, 189, 'enrolled', NULL, '2025-12-15 15:00:00'),
(28, 6, 97, 'completed', 'pass', '2025-01-15 13:00:00'),
(29, 6, 98, 'completed', 'pass', '2025-01-15 13:00:00'),
(30, 6, 99, 'completed', 'pass', '2025-01-15 13:00:00'),
(31, 6, 100, 'completed', 'pass', '2025-08-15 12:00:00'),
(32, 6, 101, 'completed', 'pass', '2025-08-15 12:00:00'),
(33, 6, 102, 'completed', 'pass', '2025-08-15 12:00:00'),
(34, 6, 187, 'enrolled', NULL, '2025-12-15 15:00:00'),
(35, 6, 188, 'enrolled', NULL, '2025-12-15 15:00:00'),
(36, 6, 189, 'enrolled', NULL, '2025-12-15 15:00:00'),
(37, 7, 97, 'completed', 'pass', '2025-01-15 13:00:00'),
(38, 7, 98, 'completed', 'pass', '2025-01-15 13:00:00'),
(39, 7, 99, 'completed', 'pass', '2025-01-15 13:00:00'),
(40, 7, 100, 'completed', 'pass', '2025-08-15 12:00:00'),
(41, 7, 101, 'completed', 'pass', '2025-08-15 12:00:00'),
(42, 7, 102, 'completed', 'pass', '2025-08-15 12:00:00'),
(43, 7, 103, 'completed', 'pass', '2025-08-15 12:00:00'),
(44, 7, 104, 'completed', 'pass', '2025-08-15 12:00:00'),
(45, 7, 105, 'completed', 'pass', '2025-08-15 12:00:00'),
(46, 7, 190, 'enrolled', NULL, '2025-12-15 15:00:00'),
(47, 7, 191, 'enrolled', NULL, '2025-12-15 15:00:00'),
(48, 7, 192, 'enrolled', NULL, '2025-12-15 15:00:00'),
(49, 8, 97, 'completed', 'pass', '2025-01-15 13:00:00'),
(50, 8, 98, 'completed', 'pass', '2025-01-15 13:00:00'),
(51, 8, 99, 'completed', 'pass', '2025-01-15 13:00:00'),
(52, 8, 100, 'completed', 'pass', '2025-08-15 12:00:00'),
(53, 8, 101, 'completed', 'pass', '2025-08-15 12:00:00'),
(54, 8, 102, 'completed', 'pass', '2025-08-15 12:00:00'),
(55, 8, 103, 'completed', 'fail', '2025-08-15 12:00:00'),
(56, 8, 104, 'completed', 'pass', '2025-08-15 12:00:00'),
(57, 8, 105, 'completed', 'pass', '2025-08-15 12:00:00'),
(58, 8, 187, 'enrolled', NULL, '2025-12-15 15:00:00'),
(59, 8, 190, 'enrolled', NULL, '2025-12-15 15:00:00'),
(60, 8, 191, 'enrolled', NULL, '2025-12-15 15:00:00'),
(61, 8, 192, 'enrolled', NULL, '2025-12-15 15:00:00'),
(62, 9, 97, 'completed', 'pass', '2025-01-15 13:00:00'),
(63, 9, 98, 'completed', 'pass', '2025-01-15 13:00:00'),
(64, 9, 99, 'completed', 'pass', '2025-01-15 13:00:00'),
(65, 9, 100, 'completed', 'pass', '2025-08-15 12:00:00'),
(66, 9, 101, 'completed', 'pass', '2025-08-15 12:00:00'),
(67, 9, 102, 'completed', 'fail', '2025-08-15 12:00:00'),
(68, 9, 103, 'completed', 'pass', '2025-08-15 12:00:00'),
(69, 9, 104, 'completed', 'pass', '2025-08-15 12:00:00'),
(70, 9, 105, 'completed', 'fail', '2025-08-15 12:00:00'),
(71, 9, 106, 'completed', 'pass', '2025-08-15 12:00:00'),
(72, 9, 107, 'completed', 'pass', '2025-08-15 12:00:00'),
(73, 9, 108, 'completed', 'pass', '2025-08-15 12:00:00'),
(74, 9, 193, 'enrolled', NULL, '2025-12-15 15:00:00'),
(75, 9, 194, 'enrolled', NULL, '2025-12-15 15:00:00'),
(76, 9, 195, 'enrolled', NULL, '2025-12-15 15:00:00'),
(77, 9, 186, 'enrolled', NULL, '2025-12-15 15:00:00'),
(78, 9, 189, 'enrolled', NULL, '2025-12-15 15:00:00'),
(79, 10, 97, 'completed', 'pass', '2025-01-15 13:00:00'),
(80, 10, 98, 'completed', 'pass', '2025-01-15 13:00:00'),
(81, 10, 99, 'completed', 'pass', '2025-01-15 13:00:00'),
(82, 10, 100, 'completed', 'pass', '2025-08-15 12:00:00'),
(83, 10, 101, 'completed', 'pass', '2025-08-15 12:00:00'),
(84, 10, 102, 'completed', 'pass', '2025-08-15 12:00:00'),
(85, 10, 103, 'completed', 'pass', '2025-08-15 12:00:00'),
(86, 10, 104, 'completed', 'fail', '2025-01-15 13:00:00'),
(87, 10, 8, 'completed', 'pass', '2025-08-15 12:00:00'),
(88, 10, 105, 'completed', 'pass', '2025-08-15 12:00:00'),
(89, 10, 106, 'completed', 'pass', '2025-08-15 12:00:00'),
(90, 10, 107, 'completed', 'pass', '2025-08-15 12:00:00'),
(91, 10, 108, 'completed', 'pass', '2025-08-15 12:00:00'),
(92, 10, 109, 'completed', 'pass', '2025-08-15 12:00:00'),
(93, 10, 110, 'completed', 'pass', '2025-08-15 12:00:00'),
(94, 10, 111, 'completed', 'pass', '2025-08-15 12:00:00'),
(95, 10, 196, 'enrolled', NULL, '2025-12-15 15:00:00'),
(96, 10, 197, 'enrolled', NULL, '2025-12-15 15:00:00'),
(97, 10, 198, 'enrolled', NULL, '2025-12-15 15:00:00'),
(98, 11, 97, 'completed', 'pass', '2025-01-15 13:00:00'),
(99, 11, 98, 'completed', 'pass', '2025-01-15 13:00:00'),
(100, 11, 99, 'completed', 'pass', '2025-01-15 13:00:00'),
(101, 11, 100, 'completed', 'pass', '2025-08-15 12:00:00'),
(102, 11, 101, 'completed', 'pass', '2025-08-15 12:00:00'),
(103, 11, 102, 'completed', 'pass', '2025-08-15 12:00:00'),
(104, 11, 103, 'completed', 'pass', '2025-08-15 12:00:00'),
(105, 11, 104, 'completed', 'pass', '2025-08-15 12:00:00'),
(106, 11, 105, 'completed', 'pass', '2025-08-15 12:00:00'),
(107, 11, 106, 'completed', 'pass', '2025-08-15 12:00:00'),
(108, 11, 107, 'completed', 'pass', '2025-08-15 12:00:00'),
(109, 11, 108, 'completed', 'pass', '2025-08-15 12:00:00'),
(110, 11, 109, 'completed', 'pass', '2025-08-15 12:00:00'),
(111, 11, 110, 'completed', 'pass', '2025-08-15 12:00:00'),
(112, 11, 111, 'completed', 'pass', '2025-08-15 12:00:00'),
(113, 11, 112, 'completed', 'pass', '2025-08-15 12:00:00'),
(114, 11, 113, 'completed', 'pass', '2025-08-15 12:00:00'),
(115, 11, 114, 'completed', 'pass', '2025-08-15 12:00:00'),
(116, 11, 196, 'enrolled', NULL, '2025-12-15 15:00:00'),
(117, 11, 199, 'enrolled', NULL, '2025-12-15 15:00:00'),
(118, 11, 200, 'enrolled', NULL, '2025-12-15 15:00:00'),
(119, 11, 201, 'enrolled', NULL, '2025-12-15 15:00:00'),
(120, 12, 97, 'completed', 'pass', '2025-01-15 13:00:00'),
(121, 12, 98, 'completed', 'pass', '2025-01-15 13:00:00'),
(122, 12, 99, 'completed', 'pass', '2025-01-15 13:00:00'),
(123, 12, 100, 'completed', 'pass', '2025-08-15 12:00:00'),
(124, 12, 101, 'completed', 'pass', '2025-08-15 12:00:00'),
(125, 12, 102, 'completed', 'pass', '2025-08-15 12:00:00'),
(126, 12, 103, 'completed', 'pass', '2025-08-15 12:00:00'),
(127, 12, 104, 'completed', 'pass', '2025-08-15 12:00:00'),
(128, 12, 105, 'completed', 'pass', '2025-08-15 12:00:00'),
(129, 12, 106, 'completed', 'pass', '2025-08-15 12:00:00'),
(130, 12, 107, 'completed', 'pass', '2025-08-15 12:00:00'),
(131, 12, 108, 'completed', 'pass', '2025-08-15 12:00:00'),
(132, 12, 109, 'completed', 'pass', '2025-08-15 12:00:00'),
(133, 12, 110, 'completed', 'pass', '2025-08-15 12:00:00'),
(134, 12, 111, 'completed', 'pass', '2025-08-15 12:00:00'),
(135, 12, 112, 'completed', 'pass', '2025-08-15 12:00:00'),
(136, 12, 113, 'completed', 'pass', '2025-08-15 12:00:00'),
(137, 12, 114, 'completed', 'pass', '2025-08-15 12:00:00'),
(138, 12, 115, 'completed', 'pass', '2025-08-15 12:00:00'),
(139, 12, 116, 'completed', 'pass', '2025-08-15 12:00:00'),
(140, 12, 117, 'completed', 'pass', '2025-08-15 12:00:00'),
(141, 12, 202, 'enrolled', NULL, '2025-12-15 15:00:00'),
(142, 12, 203, 'enrolled', NULL, '2025-12-15 15:00:00'),
(143, 12, 204, 'enrolled', NULL, '2025-12-15 15:00:00'),
(144, 13, 25, 'enrolled', NULL, '2025-12-15 15:00:00'),
(145, 13, 26, 'enrolled', NULL, '2025-12-15 15:00:00'),
(146, 13, 27, 'enrolled', NULL, '2025-12-15 15:00:00'),
(147, 14, 25, 'enrolled', NULL, '2025-12-15 15:00:00'),
(148, 14, 26, 'enrolled', NULL, '2025-12-15 15:00:00'),
(149, 14, 27, 'enrolled', NULL, '2025-12-15 15:00:00'),
(150, 15, 118, 'completed', 'pass', '2025-08-15 12:00:00'),
(151, 15, 119, 'completed', 'pass', '2025-08-15 12:00:00'),
(152, 15, 120, 'completed', 'pass', '2025-08-15 12:00:00'),
(153, 15, 28, 'enrolled', NULL, '2025-12-15 15:00:00'),
(154, 15, 29, 'enrolled', NULL, '2025-12-15 15:00:00'),
(155, 15, 30, 'enrolled', NULL, '2025-12-15 15:00:00'),
(156, 16, 118, 'completed', 'pass', '2025-08-15 12:00:00'),
(157, 16, 119, 'completed', 'pass', '2025-08-15 12:00:00'),
(158, 16, 120, 'completed', 'pass', '2025-08-15 12:00:00'),
(159, 16, 28, 'enrolled', NULL, '2025-12-15 15:00:00'),
(160, 16, 29, 'enrolled', NULL, '2025-12-15 15:00:00'),
(161, 16, 30, 'enrolled', NULL, '2025-12-15 15:00:00'),
(162, 17, 118, 'completed', 'pass', '2025-08-15 12:00:00'),
(163, 17, 119, 'completed', 'pass', '2025-08-15 12:00:00'),
(164, 17, 120, 'completed', 'pass', '2025-08-15 12:00:00'),
(165, 17, 121, 'completed', 'pass', '2025-08-15 12:00:00'),
(166, 17, 122, 'completed', 'pass', '2025-08-15 12:00:00'),
(167, 17, 123, 'completed', 'pass', '2025-08-15 12:00:00'),
(168, 17, 31, 'enrolled', NULL, '2025-12-15 15:00:00'),
(169, 17, 32, 'enrolled', NULL, '2025-12-15 15:00:00'),
(170, 17, 33, 'enrolled', NULL, '2025-12-15 15:00:00'),
(171, 18, 118, 'completed', 'pass', '2025-08-15 12:00:00'),
(172, 18, 119, 'completed', 'pass', '2025-08-15 12:00:00'),
(173, 18, 120, 'completed', 'pass', '2025-08-15 12:00:00'),
(174, 18, 121, 'completed', 'pass', '2025-08-15 12:00:00'),
(175, 18, 122, 'completed', 'pass', '2025-08-15 12:00:00'),
(176, 18, 123, 'completed', 'pass', '2025-08-15 12:00:00'),
(177, 18, 31, 'enrolled', NULL, '2025-12-15 15:00:00'),
(178, 18, 32, 'enrolled', NULL, '2025-12-15 15:00:00'),
(179, 18, 33, 'enrolled', NULL, '2025-12-15 15:00:00'),
(180, 19, 118, 'completed', 'pass', '2025-08-15 12:00:00'),
(181, 19, 119, 'completed', 'pass', '2025-08-15 12:00:00'),
(182, 19, 120, 'completed', 'pass', '2025-08-15 12:00:00'),
(183, 19, 121, 'completed', 'pass', '2025-08-15 12:00:00'),
(184, 19, 122, 'completed', 'pass', '2025-08-15 12:00:00'),
(185, 19, 123, 'completed', 'pass', '2025-08-15 12:00:00'),
(186, 19, 124, 'completed', 'pass', '2025-08-15 12:00:00'),
(187, 19, 125, 'completed', 'pass', '2025-08-15 12:00:00'),
(188, 19, 126, 'completed', 'pass', '2025-08-15 12:00:00'),
(189, 19, 34, 'enrolled', NULL, '2025-12-15 15:00:00'),
(190, 19, 35, 'enrolled', NULL, '2025-12-15 15:00:00'),
(191, 19, 36, 'enrolled', NULL, '2025-12-15 15:00:00'),
(192, 20, 118, 'completed', 'pass', '2025-08-15 12:00:00'),
(193, 20, 119, 'completed', 'pass', '2025-08-15 12:00:00'),
(194, 20, 120, 'completed', 'pass', '2025-08-15 12:00:00'),
(195, 20, 121, 'completed', 'pass', '2025-08-15 12:00:00'),
(196, 20, 122, 'completed', 'pass', '2025-08-15 12:00:00'),
(197, 20, 123, 'completed', 'pass', '2025-08-15 12:00:00'),
(198, 20, 124, 'completed', 'pass', '2025-08-15 12:00:00'),
(199, 20, 125, 'completed', 'pass', '2025-08-15 12:00:00'),
(200, 20, 126, 'completed', 'pass', '2025-08-15 12:00:00'),
(201, 20, 34, 'enrolled', NULL, '2025-12-15 15:00:00'),
(202, 20, 35, 'enrolled', NULL, '2025-12-15 15:00:00'),
(203, 20, 36, 'enrolled', NULL, '2025-12-15 15:00:00'),
(204, 21, 118, 'completed', 'pass', '2025-08-15 12:00:00'),
(205, 21, 119, 'completed', 'pass', '2025-08-15 12:00:00'),
(206, 21, 120, 'completed', 'pass', '2025-08-15 12:00:00'),
(207, 21, 121, 'completed', 'pass', '2025-08-15 12:00:00'),
(208, 21, 122, 'completed', 'pass', '2025-08-15 12:00:00'),
(209, 21, 123, 'completed', 'pass', '2025-08-15 12:00:00'),
(210, 21, 124, 'completed', 'pass', '2025-08-15 12:00:00'),
(211, 21, 125, 'completed', 'pass', '2025-08-15 12:00:00'),
(212, 21, 126, 'completed', 'pass', '2025-08-15 12:00:00'),
(213, 21, 127, 'completed', 'pass', '2025-08-15 12:00:00'),
(214, 21, 128, 'completed', 'pass', '2025-08-15 12:00:00'),
(215, 21, 129, 'completed', 'pass', '2025-08-15 12:00:00'),
(216, 21, 37, 'enrolled', NULL, '2025-12-15 15:00:00'),
(217, 21, 38, 'enrolled', NULL, '2025-12-15 15:00:00'),
(218, 21, 39, 'enrolled', NULL, '2025-12-15 15:00:00'),
(219, 22, 118, 'completed', 'pass', '2025-08-15 12:00:00'),
(220, 22, 119, 'completed', 'pass', '2025-08-15 12:00:00'),
(221, 22, 120, 'completed', 'pass', '2025-08-15 12:00:00'),
(222, 22, 121, 'completed', 'pass', '2025-08-15 12:00:00'),
(223, 22, 122, 'completed', 'pass', '2025-08-15 12:00:00'),
(224, 22, 123, 'completed', 'pass', '2025-08-15 12:00:00'),
(225, 22, 124, 'completed', 'pass', '2025-08-15 12:00:00'),
(226, 22, 125, 'completed', 'pass', '2025-08-15 12:00:00'),
(227, 22, 126, 'completed', 'pass', '2025-08-15 12:00:00'),
(228, 22, 127, 'completed', 'pass', '2025-08-15 12:00:00'),
(229, 22, 128, 'completed', 'pass', '2025-08-15 12:00:00'),
(230, 22, 129, 'completed', 'pass', '2025-08-15 12:00:00'),
(231, 22, 130, 'completed', 'pass', '2025-08-15 12:00:00'),
(232, 22, 131, 'completed', 'pass', '2025-08-15 12:00:00'),
(233, 22, 132, 'completed', 'pass', '2025-08-15 12:00:00'),
(234, 22, 40, 'enrolled', NULL, '2025-12-15 15:00:00'),
(235, 22, 41, 'enrolled', NULL, '2025-12-15 15:00:00'),
(236, 22, 42, 'enrolled', NULL, '2025-12-15 15:00:00'),
(237, 23, 118, 'completed', 'pass', '2025-08-15 12:00:00'),
(238, 23, 119, 'completed', 'pass', '2025-08-15 12:00:00'),
(239, 23, 120, 'completed', 'pass', '2025-08-15 12:00:00'),
(240, 23, 121, 'completed', 'pass', '2025-08-15 12:00:00'),
(241, 23, 122, 'completed', 'pass', '2025-08-15 12:00:00'),
(242, 23, 123, 'completed', 'pass', '2025-08-15 12:00:00'),
(243, 23, 124, 'completed', 'pass', '2025-08-15 12:00:00'),
(244, 23, 125, 'completed', 'pass', '2025-08-15 12:00:00'),
(245, 23, 126, 'completed', 'pass', '2025-08-15 12:00:00'),
(246, 23, 127, 'completed', 'pass', '2025-08-15 12:00:00'),
(247, 23, 128, 'completed', 'pass', '2025-08-15 12:00:00'),
(248, 23, 129, 'completed', 'pass', '2025-08-15 12:00:00'),
(249, 23, 130, 'completed', 'pass', '2025-08-15 12:00:00'),
(250, 23, 131, 'completed', 'pass', '2025-08-15 12:00:00'),
(251, 23, 132, 'completed', 'pass', '2025-08-15 12:00:00'),
(252, 23, 133, 'completed', 'pass', '2025-08-15 12:00:00'),
(253, 23, 134, 'completed', 'pass', '2025-08-15 12:00:00'),
(254, 23, 135, 'completed', 'pass', '2025-08-15 12:00:00'),
(255, 23, 43, 'enrolled', NULL, '2025-12-15 15:00:00'),
(256, 23, 44, 'enrolled', NULL, '2025-12-15 15:00:00'),
(257, 23, 45, 'enrolled', NULL, '2025-12-15 15:00:00'),
(258, 24, 118, 'completed', 'pass', '2025-08-15 12:00:00'),
(259, 24, 119, 'completed', 'pass', '2025-08-15 12:00:00'),
(260, 24, 120, 'completed', 'pass', '2025-08-15 12:00:00'),
(261, 24, 121, 'completed', 'pass', '2025-08-15 12:00:00'),
(262, 24, 122, 'completed', 'pass', '2025-08-15 12:00:00'),
(263, 24, 123, 'completed', 'pass', '2025-08-15 12:00:00'),
(264, 24, 124, 'completed', 'pass', '2025-08-15 12:00:00'),
(265, 24, 125, 'completed', 'pass', '2025-08-15 12:00:00'),
(266, 24, 126, 'completed', 'pass', '2025-08-15 12:00:00'),
(267, 24, 127, 'completed', 'pass', '2025-08-15 12:00:00'),
(268, 24, 128, 'completed', 'pass', '2025-08-15 12:00:00'),
(269, 24, 129, 'completed', 'pass', '2025-08-15 12:00:00'),
(270, 24, 130, 'completed', 'pass', '2025-08-15 12:00:00'),
(271, 24, 131, 'completed', 'pass', '2025-08-15 12:00:00'),
(272, 24, 132, 'completed', 'pass', '2025-08-15 12:00:00'),
(273, 24, 133, 'completed', 'pass', '2025-08-15 12:00:00'),
(274, 24, 134, 'completed', 'pass', '2025-08-15 12:00:00'),
(275, 24, 135, 'completed', 'pass', '2025-08-15 12:00:00'),
(276, 24, 136, 'completed', 'pass', '2025-08-15 12:00:00'),
(277, 24, 137, 'completed', 'pass', '2025-08-15 12:00:00'),
(278, 24, 138, 'completed', 'pass', '2025-08-15 12:00:00'),
(279, 24, 46, 'enrolled', NULL, '2025-12-15 15:00:00'),
(280, 24, 47, 'enrolled', NULL, '2025-12-15 15:00:00'),
(281, 24, 48, 'enrolled', NULL, '2025-12-15 15:00:00'),
(282, 25, 49, 'enrolled', NULL, '2025-12-15 15:00:00'),
(283, 25, 50, 'enrolled', NULL, '2025-12-15 15:00:00'),
(284, 26, 49, 'enrolled', NULL, '2025-12-15 15:00:00'),
(285, 26, 50, 'enrolled', NULL, '2025-12-15 15:00:00'),
(286, 27, 139, 'completed', 'pass', '2025-08-15 12:00:00'),
(287, 27, 140, 'completed', 'pass', '2025-08-15 12:00:00'),
(288, 27, 51, 'enrolled', NULL, '2025-12-15 15:00:00'),
(289, 27, 52, 'enrolled', NULL, '2025-12-15 15:00:00'),
(290, 28, 139, 'completed', 'pass', '2025-08-15 12:00:00'),
(291, 28, 140, 'completed', 'pass', '2025-08-15 12:00:00'),
(292, 28, 51, 'enrolled', NULL, '2025-12-15 15:00:00'),
(293, 28, 52, 'enrolled', NULL, '2025-12-15 15:00:00'),
(294, 29, 139, 'completed', 'pass', '2025-08-15 12:00:00'),
(295, 29, 140, 'completed', 'pass', '2025-08-15 12:00:00'),
(296, 29, 141, 'completed', 'pass', '2025-08-15 12:00:00'),
(297, 29, 142, 'completed', 'pass', '2025-08-15 12:00:00'),
(298, 29, 53, 'enrolled', NULL, '2025-12-15 15:00:00'),
(299, 29, 54, 'enrolled', NULL, '2025-12-15 15:00:00'),
(300, 30, 139, 'completed', 'pass', '2025-08-15 12:00:00'),
(301, 30, 140, 'completed', 'pass', '2025-08-15 12:00:00'),
(302, 30, 141, 'completed', 'pass', '2025-08-15 12:00:00'),
(303, 30, 142, 'completed', 'pass', '2025-08-15 12:00:00'),
(304, 30, 53, 'enrolled', NULL, '2025-12-15 15:00:00'),
(305, 30, 54, 'enrolled', NULL, '2025-12-15 15:00:00'),
(306, 31, 139, 'completed', 'pass', '2025-08-15 12:00:00'),
(307, 31, 140, 'completed', 'pass', '2025-08-15 12:00:00'),
(308, 31, 141, 'completed', 'pass', '2025-08-15 12:00:00'),
(309, 31, 142, 'completed', 'pass', '2025-08-15 12:00:00'),
(310, 31, 143, 'completed', 'pass', '2025-08-15 12:00:00'),
(311, 31, 144, 'completed', 'pass', '2025-08-15 12:00:00'),
(312, 31, 55, 'enrolled', NULL, '2025-12-15 15:00:00'),
(313, 31, 56, 'enrolled', NULL, '2025-12-15 15:00:00'),
(314, 32, 139, 'completed', 'pass', '2025-08-15 12:00:00'),
(315, 32, 140, 'completed', 'pass', '2025-08-15 12:00:00'),
(316, 32, 141, 'completed', 'pass', '2025-08-15 12:00:00'),
(317, 32, 142, 'completed', 'pass', '2025-08-15 12:00:00'),
(318, 32, 143, 'completed', 'pass', '2025-08-15 12:00:00'),
(319, 32, 144, 'completed', 'pass', '2025-08-15 12:00:00'),
(320, 32, 55, 'enrolled', NULL, '2025-12-15 15:00:00'),
(321, 32, 56, 'enrolled', NULL, '2025-12-15 15:00:00'),
(322, 33, 139, 'completed', 'pass', '2025-08-15 12:00:00'),
(323, 33, 140, 'completed', 'pass', '2025-08-15 12:00:00'),
(324, 33, 141, 'completed', 'pass', '2025-08-15 12:00:00'),
(325, 33, 142, 'completed', 'pass', '2025-08-15 12:00:00'),
(326, 33, 143, 'completed', 'pass', '2025-08-15 12:00:00'),
(327, 33, 144, 'completed', 'pass', '2025-08-15 12:00:00'),
(328, 33, 145, 'completed', 'pass', '2025-08-15 12:00:00'),
(329, 33, 146, 'completed', 'pass', '2025-08-15 12:00:00'),
(330, 33, 57, 'enrolled', NULL, '2025-12-15 15:00:00'),
(331, 33, 58, 'enrolled', NULL, '2025-12-15 15:00:00'),
(332, 34, 139, 'completed', 'pass', '2025-08-15 12:00:00'),
(333, 34, 140, 'completed', 'pass', '2025-08-15 12:00:00'),
(334, 34, 141, 'completed', 'pass', '2025-08-15 12:00:00'),
(335, 34, 142, 'completed', 'pass', '2025-08-15 12:00:00'),
(336, 34, 143, 'completed', 'pass', '2025-08-15 12:00:00'),
(337, 34, 144, 'completed', 'pass', '2025-08-15 12:00:00'),
(338, 34, 145, 'completed', 'pass', '2025-08-15 12:00:00'),
(339, 34, 146, 'completed', 'pass', '2025-08-15 12:00:00'),
(340, 34, 147, 'completed', 'pass', '2025-08-15 12:00:00'),
(341, 34, 148, 'completed', 'pass', '2025-08-15 12:00:00'),
(342, 34, 59, 'enrolled', NULL, '2025-12-15 15:00:00'),
(343, 34, 60, 'enrolled', NULL, '2025-12-15 15:00:00'),
(344, 35, 139, 'completed', 'pass', '2025-08-15 12:00:00'),
(345, 35, 140, 'completed', 'pass', '2025-08-15 12:00:00'),
(346, 35, 141, 'completed', 'pass', '2025-08-15 12:00:00'),
(347, 35, 142, 'completed', 'pass', '2025-08-15 12:00:00'),
(348, 35, 143, 'completed', 'pass', '2025-08-15 12:00:00'),
(349, 35, 144, 'completed', 'pass', '2025-08-15 12:00:00'),
(350, 35, 145, 'completed', 'pass', '2025-08-15 12:00:00'),
(351, 35, 146, 'completed', 'pass', '2025-08-15 12:00:00'),
(352, 35, 147, 'completed', 'pass', '2025-08-15 12:00:00'),
(353, 35, 148, 'completed', 'pass', '2025-08-15 12:00:00'),
(354, 35, 149, 'completed', 'pass', '2025-08-15 12:00:00'),
(355, 35, 150, 'completed', 'pass', '2025-08-15 12:00:00'),
(356, 35, 61, 'enrolled', NULL, '2025-12-15 15:00:00'),
(357, 35, 62, 'enrolled', NULL, '2025-12-15 15:00:00'),
(358, 36, 139, 'completed', 'pass', '2025-08-15 12:00:00'),
(359, 36, 140, 'completed', 'pass', '2025-08-15 12:00:00'),
(360, 36, 141, 'completed', 'pass', '2025-08-15 12:00:00'),
(361, 36, 142, 'completed', 'pass', '2025-08-15 12:00:00'),
(362, 36, 143, 'completed', 'pass', '2025-08-15 12:00:00'),
(363, 36, 144, 'completed', 'pass', '2025-08-15 12:00:00'),
(364, 36, 145, 'completed', 'pass', '2025-08-15 12:00:00'),
(365, 36, 146, 'completed', 'pass', '2025-08-15 12:00:00'),
(366, 36, 147, 'completed', 'pass', '2025-08-15 12:00:00'),
(367, 36, 148, 'completed', 'pass', '2025-08-15 12:00:00'),
(368, 36, 149, 'completed', 'pass', '2025-08-15 12:00:00'),
(369, 36, 150, 'completed', 'pass', '2025-08-15 12:00:00'),
(370, 36, 151, 'completed', 'pass', '2025-08-15 12:00:00'),
(371, 36, 152, 'completed', 'pass', '2025-08-15 12:00:00'),
(372, 36, 63, 'enrolled', NULL, '2025-12-15 15:00:00'),
(373, 36, 64, 'enrolled', NULL, '2025-12-15 15:00:00'),
(374, 37, 65, 'enrolled', NULL, '2025-12-15 15:00:00'),
(375, 37, 66, 'enrolled', NULL, '2025-12-15 15:00:00'),
(376, 38, 65, 'enrolled', NULL, '2025-12-15 15:00:00'),
(377, 38, 66, 'enrolled', NULL, '2025-12-15 15:00:00'),
(378, 39, 153, 'completed', 'pass', '2025-08-15 12:00:00'),
(379, 39, 154, 'completed', 'pass', '2025-08-15 12:00:00'),
(380, 39, 67, 'enrolled', NULL, '2025-12-15 15:00:00'),
(381, 39, 68, 'enrolled', NULL, '2025-12-15 15:00:00'),
(382, 40, 153, 'completed', 'pass', '2025-08-15 12:00:00'),
(383, 40, 154, 'completed', 'pass', '2025-08-15 12:00:00'),
(384, 40, 67, 'enrolled', NULL, '2025-12-15 15:00:00'),
(385, 40, 68, 'enrolled', NULL, '2025-12-15 15:00:00'),
(386, 41, 153, 'completed', 'pass', '2025-08-15 12:00:00'),
(387, 41, 154, 'completed', 'pass', '2025-08-15 12:00:00'),
(388, 41, 155, 'completed', 'pass', '2025-08-15 12:00:00'),
(389, 41, 156, 'completed', 'pass', '2025-08-15 12:00:00'),
(390, 41, 69, 'enrolled', NULL, '2025-12-15 15:00:00'),
(391, 41, 70, 'enrolled', NULL, '2025-12-15 15:00:00'),
(392, 42, 153, 'completed', 'pass', '2025-08-15 12:00:00'),
(393, 42, 154, 'completed', 'pass', '2025-08-15 12:00:00'),
(394, 42, 155, 'completed', 'pass', '2025-08-15 12:00:00'),
(395, 42, 156, 'completed', 'pass', '2025-08-15 12:00:00'),
(396, 42, 69, 'enrolled', NULL, '2025-12-15 15:00:00'),
(397, 42, 70, 'enrolled', NULL, '2025-12-15 15:00:00'),
(398, 43, 153, 'completed', 'pass', '2025-08-15 12:00:00'),
(399, 43, 154, 'completed', 'pass', '2025-08-15 12:00:00'),
(400, 43, 155, 'completed', 'pass', '2025-08-15 12:00:00'),
(401, 43, 156, 'completed', 'pass', '2025-08-15 12:00:00'),
(402, 43, 157, 'completed', 'pass', '2025-08-15 12:00:00'),
(403, 43, 158, 'completed', 'pass', '2025-08-15 12:00:00'),
(404, 43, 71, 'enrolled', NULL, '2025-12-15 15:00:00'),
(405, 43, 72, 'enrolled', NULL, '2025-12-15 15:00:00'),
(406, 44, 153, 'completed', 'pass', '2025-08-15 12:00:00'),
(407, 44, 154, 'completed', 'pass', '2025-08-15 12:00:00'),
(408, 44, 155, 'completed', 'pass', '2025-08-15 12:00:00'),
(409, 44, 156, 'completed', 'pass', '2025-08-15 12:00:00'),
(410, 44, 157, 'completed', 'pass', '2025-08-15 12:00:00'),
(411, 44, 158, 'completed', 'pass', '2025-08-15 12:00:00'),
(412, 44, 71, 'enrolled', NULL, '2025-12-15 15:00:00'),
(413, 44, 72, 'enrolled', NULL, '2025-12-15 15:00:00'),
(414, 45, 153, 'completed', 'pass', '2025-08-15 12:00:00'),
(415, 45, 154, 'completed', 'pass', '2025-08-15 12:00:00'),
(416, 45, 155, 'completed', 'pass', '2025-08-15 12:00:00'),
(417, 45, 156, 'completed', 'pass', '2025-08-15 12:00:00'),
(418, 45, 157, 'completed', 'pass', '2025-08-15 12:00:00'),
(419, 45, 158, 'completed', 'pass', '2025-08-15 12:00:00'),
(420, 45, 159, 'completed', 'pass', '2025-08-15 12:00:00'),
(421, 45, 160, 'completed', 'pass', '2025-08-15 12:00:00'),
(422, 45, 73, 'enrolled', NULL, '2025-12-15 15:00:00'),
(423, 45, 74, 'enrolled', NULL, '2025-12-15 15:00:00'),
(424, 46, 153, 'completed', 'pass', '2025-08-15 12:00:00'),
(425, 46, 154, 'completed', 'pass', '2025-08-15 12:00:00'),
(426, 46, 155, 'completed', 'pass', '2025-08-15 12:00:00'),
(427, 46, 156, 'completed', 'pass', '2025-08-15 12:00:00'),
(428, 46, 157, 'completed', 'pass', '2025-08-15 12:00:00'),
(429, 46, 158, 'completed', 'pass', '2025-08-15 12:00:00'),
(430, 46, 159, 'completed', 'pass', '2025-08-15 12:00:00'),
(431, 46, 160, 'completed', 'pass', '2025-08-15 12:00:00'),
(432, 46, 161, 'completed', 'pass', '2025-08-15 12:00:00'),
(433, 46, 162, 'completed', 'pass', '2025-08-15 12:00:00'),
(434, 46, 75, 'enrolled', NULL, '2025-12-15 15:00:00'),
(435, 46, 76, 'enrolled', NULL, '2025-12-15 15:00:00'),
(436, 47, 153, 'completed', 'pass', '2025-08-15 12:00:00'),
(437, 47, 154, 'completed', 'pass', '2025-08-15 12:00:00'),
(438, 47, 155, 'completed', 'pass', '2025-08-15 12:00:00'),
(439, 47, 156, 'completed', 'pass', '2025-08-15 12:00:00'),
(440, 47, 157, 'completed', 'pass', '2025-08-15 12:00:00'),
(441, 47, 158, 'completed', 'pass', '2025-08-15 12:00:00'),
(442, 47, 159, 'completed', 'pass', '2025-08-15 12:00:00'),
(443, 47, 160, 'completed', 'pass', '2025-08-15 12:00:00'),
(444, 47, 161, 'completed', 'pass', '2025-08-15 12:00:00'),
(445, 47, 162, 'completed', 'pass', '2025-08-15 12:00:00'),
(446, 47, 163, 'completed', 'pass', '2025-08-15 12:00:00'),
(447, 47, 164, 'completed', 'pass', '2025-08-15 12:00:00'),
(448, 47, 77, 'enrolled', NULL, '2025-12-15 15:00:00'),
(449, 47, 78, 'enrolled', NULL, '2025-12-15 15:00:00'),
(450, 49, 81, 'enrolled', NULL, '2025-12-15 15:00:00'),
(451, 49, 82, 'enrolled', NULL, '2025-12-15 15:00:00'),
(452, 50, 81, 'enrolled', NULL, '2025-12-15 15:00:00'),
(453, 50, 82, 'enrolled', NULL, '2025-12-15 15:00:00'),
(454, 51, 167, 'completed', 'pass', '2025-08-15 12:00:00'),
(455, 51, 168, 'completed', 'pass', '2025-08-15 12:00:00'),
(456, 51, 83, 'enrolled', NULL, '2025-12-15 15:00:00'),
(457, 51, 84, 'enrolled', NULL, '2025-12-15 15:00:00'),
(458, 52, 167, 'completed', 'pass', '2025-08-15 12:00:00'),
(459, 52, 168, 'completed', 'pass', '2025-08-15 12:00:00'),
(460, 52, 83, 'enrolled', NULL, '2025-12-15 15:00:00'),
(461, 52, 84, 'enrolled', NULL, '2025-12-15 15:00:00'),
(462, 53, 167, 'completed', 'pass', '2025-08-15 12:00:00'),
(463, 53, 168, 'completed', 'pass', '2025-08-15 12:00:00'),
(464, 53, 169, 'completed', 'pass', '2025-08-15 12:00:00'),
(465, 53, 170, 'completed', 'pass', '2025-08-15 12:00:00'),
(466, 53, 85, 'enrolled', NULL, '2025-12-15 15:00:00'),
(467, 53, 86, 'enrolled', NULL, '2025-12-15 15:00:00'),
(468, 54, 167, 'completed', 'pass', '2025-08-15 12:00:00'),
(469, 54, 168, 'completed', 'pass', '2025-08-15 12:00:00'),
(470, 54, 169, 'completed', 'pass', '2025-08-15 12:00:00'),
(471, 54, 170, 'completed', 'pass', '2025-08-15 12:00:00'),
(472, 54, 85, 'enrolled', NULL, '2025-12-15 15:00:00'),
(473, 54, 86, 'enrolled', NULL, '2025-12-15 15:00:00'),
(474, 55, 167, 'completed', 'pass', '2025-08-15 12:00:00'),
(475, 55, 168, 'completed', 'pass', '2025-08-15 12:00:00'),
(476, 55, 169, 'completed', 'pass', '2025-08-15 12:00:00'),
(477, 55, 170, 'completed', 'pass', '2025-08-15 12:00:00'),
(478, 55, 171, 'completed', 'pass', '2025-08-15 12:00:00'),
(479, 55, 172, 'completed', 'pass', '2025-08-15 12:00:00'),
(480, 55, 87, 'enrolled', NULL, '2025-12-15 15:00:00'),
(481, 55, 88, 'enrolled', NULL, '2025-12-15 15:00:00'),
(482, 56, 167, 'completed', 'pass', '2025-08-15 12:00:00'),
(483, 56, 168, 'completed', 'pass', '2025-08-15 12:00:00'),
(484, 56, 169, 'completed', 'pass', '2025-08-15 12:00:00'),
(485, 56, 170, 'completed', 'pass', '2025-08-15 12:00:00'),
(486, 56, 171, 'completed', 'pass', '2025-08-15 12:00:00'),
(487, 56, 172, 'completed', 'pass', '2025-08-15 12:00:00'),
(488, 56, 87, 'enrolled', NULL, '2025-12-15 15:00:00'),
(489, 56, 88, 'enrolled', NULL, '2025-12-15 15:00:00'),
(490, 57, 167, 'completed', 'pass', '2025-08-15 12:00:00'),
(491, 57, 168, 'completed', 'pass', '2025-08-15 12:00:00'),
(492, 57, 169, 'completed', 'pass', '2025-08-15 12:00:00'),
(493, 57, 170, 'completed', 'pass', '2025-08-15 12:00:00'),
(494, 57, 171, 'completed', 'pass', '2025-08-15 12:00:00'),
(495, 57, 172, 'completed', 'pass', '2025-08-15 12:00:00'),
(496, 57, 173, 'completed', 'pass', '2025-08-15 12:00:00'),
(497, 57, 174, 'completed', 'pass', '2025-08-15 12:00:00'),
(498, 57, 89, 'enrolled', NULL, '2025-12-15 15:00:00'),
(499, 57, 90, 'enrolled', NULL, '2025-12-15 15:00:00'),
(500, 58, 167, 'completed', 'pass', '2025-08-15 12:00:00'),
(501, 58, 168, 'completed', 'pass', '2025-08-15 12:00:00'),
(502, 58, 169, 'completed', 'pass', '2025-08-15 12:00:00'),
(503, 58, 170, 'completed', 'pass', '2025-08-15 12:00:00'),
(504, 58, 171, 'completed', 'pass', '2025-08-15 12:00:00'),
(505, 58, 172, 'completed', 'pass', '2025-08-15 12:00:00'),
(506, 58, 173, 'completed', 'pass', '2025-08-15 12:00:00'),
(507, 58, 174, 'completed', 'pass', '2025-08-15 12:00:00'),
(508, 58, 175, 'completed', 'pass', '2025-08-15 12:00:00'),
(509, 58, 176, 'completed', 'pass', '2025-08-15 12:00:00'),
(510, 58, 91, 'enrolled', NULL, '2025-12-15 15:00:00'),
(511, 58, 92, 'enrolled', NULL, '2025-12-15 15:00:00'),
(512, 59, 167, 'completed', 'pass', '2025-08-15 12:00:00'),
(513, 59, 168, 'completed', 'pass', '2025-08-15 12:00:00'),
(514, 59, 169, 'completed', 'pass', '2025-08-15 12:00:00'),
(515, 59, 170, 'completed', 'pass', '2025-08-15 12:00:00'),
(516, 59, 171, 'completed', 'pass', '2025-08-15 12:00:00'),
(517, 59, 172, 'completed', 'pass', '2025-08-15 12:00:00'),
(518, 59, 173, 'completed', 'pass', '2025-08-15 12:00:00'),
(519, 59, 174, 'completed', 'pass', '2025-08-15 12:00:00'),
(520, 59, 175, 'completed', 'pass', '2025-08-15 12:00:00'),
(521, 59, 176, 'completed', 'pass', '2025-08-15 12:00:00'),
(522, 59, 177, 'completed', 'pass', '2025-08-15 12:00:00'),
(523, 59, 178, 'completed', 'pass', '2025-08-15 12:00:00'),
(524, 59, 93, 'enrolled', NULL, '2025-12-15 15:00:00'),
(525, 59, 94, 'enrolled', NULL, '2025-12-15 15:00:00'),
(526, 60, 167, 'completed', 'pass', '2025-08-15 12:00:00'),
(527, 60, 168, 'completed', 'pass', '2025-08-15 12:00:00'),
(528, 60, 169, 'completed', 'pass', '2025-08-15 12:00:00'),
(529, 60, 170, 'completed', 'pass', '2025-08-15 12:00:00'),
(530, 60, 171, 'completed', 'pass', '2025-08-15 12:00:00'),
(531, 60, 172, 'completed', 'pass', '2025-08-15 12:00:00'),
(532, 60, 173, 'completed', 'pass', '2025-08-15 12:00:00'),
(533, 60, 174, 'completed', 'pass', '2025-08-15 12:00:00'),
(534, 60, 175, 'completed', 'pass', '2025-08-15 12:00:00'),
(535, 60, 176, 'completed', 'pass', '2025-08-15 12:00:00'),
(536, 60, 177, 'completed', 'pass', '2025-08-15 12:00:00'),
(537, 60, 178, 'completed', 'pass', '2025-08-15 12:00:00'),
(538, 60, 179, 'completed', 'pass', '2025-08-15 12:00:00'),
(539, 60, 180, 'completed', 'pass', '2025-08-15 12:00:00'),
(540, 60, 95, 'enrolled', NULL, '2025-12-15 15:00:00'),
(541, 60, 96, 'enrolled', NULL, '2025-12-15 15:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `department_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `user_id`, `employee_id`, `name`, `email`, `department_id`, `status`, `created_at`) VALUES
(1, 7, 'T001', 'Dr. David Miller', 'david.miller@university.edu', 1, 'active', '2024-01-20 14:00:00'),
(2, 8, 'T002', 'Dr. Jennifer Lee', 'jennifer.lee@university.edu', 1, 'active', '2024-01-20 14:00:00'),
(3, 9, 'T003', 'Dr. Kevin Zhang', 'kevin.zhang@university.edu', 1, 'active', '2024-01-20 14:00:00'),
(4, 10, 'T004', 'Dr. Lisa Patel', 'lisa.patel@university.edu', 2, 'active', '2024-01-20 14:00:00'),
(5, 11, 'T005', 'Dr. James Brown', 'james.brown@university.edu', 2, 'active', '2024-01-20 14:00:00'),
(6, 12, 'T006', 'Dr. Anna Garcia', 'anna.garcia@university.edu', 2, 'active', '2024-01-20 14:00:00'),
(7, 13, 'T007', 'Dr. Mark Wilson', 'mark.wilson@university.edu', 3, 'active', '2024-01-20 14:00:00'),
(8, 14, 'T008', 'Dr. Nina Kumar', 'nina.kumar@university.edu', 3, 'active', '2024-01-20 14:00:00'),
(9, 15, 'T009', 'Dr. Steven Park', 'steven.park@university.edu', 3, 'active', '2024-01-20 14:00:00'),
(10, 16, 'T010', 'Dr. Catherine White', 'catherine.white@university.edu', 4, 'active', '2024-01-20 14:00:00'),
(11, 17, 'T011', 'Dr. Daniel Reed', 'daniel.reed@university.edu', 4, 'active', '2024-01-20 14:00:00'),
(12, 18, 'T012', 'Dr. Michelle Adams', 'michelle.adams@university.edu', 4, 'active', '2024-01-20 14:00:00'),
(13, 19, 'T013', 'Dr. Thomas Scott', 'thomas.scott@university.edu', 5, 'active', '2024-01-20 14:00:00'),
(14, 20, 'T014', 'Dr. Rachel Morris', 'rachel.morris@university.edu', 5, 'active', '2024-01-20 14:00:00'),
(15, 21, 'T015', 'Dr. Brian Taylor', 'brian.taylor@university.edu', 5, 'active', '2024-01-20 14:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_availability`
--

CREATE TABLE `teacher_availability` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `teacher_id` bigint(20) UNSIGNED NOT NULL,
  `term` varchar(255) NOT NULL,
  `year` int(11) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `max_hours_per_week` int(11) NOT NULL DEFAULT 20,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `teacher_availability`
--

INSERT INTO `teacher_availability` (`id`, `teacher_id`, `term`, `year`, `day_of_week`, `start_time`, `end_time`, `max_hours_per_week`, `created_at`) VALUES
(1, 1, 'Winter', 2026, 'Monday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(2, 1, 'Winter', 2026, 'Tuesday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(3, 1, 'Winter', 2026, 'Wednesday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(4, 1, 'Winter', 2026, 'Thursday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(5, 1, 'Winter', 2026, 'Friday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(6, 2, 'Winter', 2026, 'Monday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(7, 2, 'Winter', 2026, 'Tuesday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(8, 2, 'Winter', 2026, 'Wednesday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(9, 2, 'Winter', 2026, 'Thursday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(10, 2, 'Winter', 2026, 'Friday', '08:00:00', '13:00:00', 20, '2025-12-01 13:00:00'),
(11, 3, 'Winter', 2026, 'Monday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(12, 3, 'Winter', 2026, 'Tuesday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(13, 3, 'Winter', 2026, 'Wednesday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(14, 3, 'Winter', 2026, 'Thursday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(15, 3, 'Winter', 2026, 'Friday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(16, 4, 'Winter', 2026, 'Monday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(17, 4, 'Winter', 2026, 'Tuesday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(18, 4, 'Winter', 2026, 'Wednesday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(19, 4, 'Winter', 2026, 'Thursday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(20, 4, 'Winter', 2026, 'Friday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(21, 5, 'Winter', 2026, 'Monday', '13:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(22, 5, 'Winter', 2026, 'Tuesday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(23, 5, 'Winter', 2026, 'Wednesday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(24, 5, 'Winter', 2026, 'Thursday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(25, 5, 'Winter', 2026, 'Friday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(26, 6, 'Winter', 2026, 'Monday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(27, 6, 'Winter', 2026, 'Tuesday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(28, 6, 'Winter', 2026, 'Wednesday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(29, 6, 'Winter', 2026, 'Thursday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(30, 6, 'Winter', 2026, 'Friday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(31, 7, 'Winter', 2026, 'Monday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(32, 7, 'Winter', 2026, 'Tuesday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(33, 7, 'Winter', 2026, 'Wednesday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(34, 7, 'Winter', 2026, 'Thursday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(35, 7, 'Winter', 2026, 'Friday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(36, 8, 'Winter', 2026, 'Monday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(37, 8, 'Winter', 2026, 'Tuesday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(38, 8, 'Winter', 2026, 'Wednesday', '08:00:00', '11:00:00', 20, '2025-12-01 13:00:00'),
(39, 8, 'Winter', 2026, 'Thursday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(40, 8, 'Winter', 2026, 'Friday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(41, 9, 'Winter', 2026, 'Monday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(42, 9, 'Winter', 2026, 'Tuesday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(43, 9, 'Winter', 2026, 'Wednesday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(44, 9, 'Winter', 2026, 'Thursday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(45, 9, 'Winter', 2026, 'Friday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(46, 10, 'Winter', 2026, 'Monday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(47, 10, 'Winter', 2026, 'Tuesday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(48, 10, 'Winter', 2026, 'Wednesday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(49, 10, 'Winter', 2026, 'Thursday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(50, 10, 'Winter', 2026, 'Friday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(51, 11, 'Winter', 2026, 'Monday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(52, 11, 'Winter', 2026, 'Tuesday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(53, 11, 'Winter', 2026, 'Wednesday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(54, 11, 'Winter', 2026, 'Thursday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(55, 11, 'Winter', 2026, 'Friday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(56, 12, 'Winter', 2026, 'Monday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(57, 12, 'Winter', 2026, 'Tuesday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(58, 12, 'Winter', 2026, 'Wednesday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(59, 12, 'Winter', 2026, 'Thursday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(60, 12, 'Winter', 2026, 'Friday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(61, 13, 'Winter', 2026, 'Monday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(62, 13, 'Winter', 2026, 'Tuesday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(63, 13, 'Winter', 2026, 'Wednesday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(64, 13, 'Winter', 2026, 'Thursday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(65, 13, 'Winter', 2026, 'Friday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(66, 14, 'Winter', 2026, 'Monday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(67, 14, 'Winter', 2026, 'Tuesday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(68, 14, 'Winter', 2026, 'Wednesday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(69, 14, 'Winter', 2026, 'Thursday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(70, 14, 'Winter', 2026, 'Friday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(71, 15, 'Winter', 2026, 'Monday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(72, 15, 'Winter', 2026, 'Tuesday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(73, 15, 'Winter', 2026, 'Wednesday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(74, 15, 'Winter', 2026, 'Thursday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00'),
(75, 15, 'Winter', 2026, 'Friday', '08:00:00', '17:00:00', 20, '2025-12-01 13:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `timetables`
--

CREATE TABLE `timetables` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `department_id` bigint(20) UNSIGNED NOT NULL,
  `term` varchar(255) NOT NULL,
  `year` int(11) NOT NULL,
  `semester` int(11) DEFAULT NULL,
  `status` enum('draft','active','archived') NOT NULL DEFAULT 'draft',
  `generated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `generated_at` timestamp NULL DEFAULT NULL,
  `conflicts_count` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `timetables`
--

INSERT INTO `timetables` (`id`, `department_id`, `term`, `year`, `semester`, `status`, `generated_by`, `generated_at`, `conflicts_count`) VALUES
(1, 1, 'Fall', 2026, 1, 'active', 2, '2026-04-06 02:35:42', 0),
(2, 1, 'Fall', 2026, 2, 'active', 2, '2026-04-06 02:36:01', 0),
(3, 1, 'Fall', 2026, 3, 'active', 2, '2026-04-06 02:36:28', 0),
(4, 1, 'Fall', 2026, 4, 'active', 2, '2026-04-06 02:37:06', 0),
(5, 1, 'Fall', 2026, 5, 'active', 2, '2026-04-06 02:37:22', 0),
(6, 1, 'Fall', 2026, 6, 'archived', 2, '2026-04-06 02:37:37', 0),
(8, 1, 'Fall', 2026, 7, 'active', 2, '2026-04-06 02:39:39', 0),
(9, 1, 'Fall', 2026, 8, 'active', 2, '2026-04-06 02:39:58', 0);

-- --------------------------------------------------------

--
-- Table structure for table `timetable_slots`
--

CREATE TABLE `timetable_slots` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `timetable_id` bigint(20) UNSIGNED NOT NULL,
  `course_section_id` bigint(20) UNSIGNED NOT NULL,
  `teacher_id` bigint(20) UNSIGNED NOT NULL,
  `room_id` bigint(20) UNSIGNED NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `component` enum('theory','lab') NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `timetable_slots`
--

INSERT INTO `timetable_slots` (`id`, `timetable_id`, `course_section_id`, `teacher_id`, `room_id`, `day_of_week`, `start_time`, `end_time`, `component`, `created_at`) VALUES
(1, 1, 181, 1, 1, 'Monday', '08:00:00', '09:30:00', 'theory', '2026-04-06 02:35:42'),
(2, 1, 182, 2, 1, 'Tuesday', '08:00:00', '09:30:00', 'theory', '2026-04-06 02:35:42'),
(3, 1, 183, 3, 1, 'Wednesday', '08:00:00', '09:30:00', 'theory', '2026-04-06 02:35:42'),
(4, 2, 184, 1, 8, 'Monday', '09:40:00', '11:10:00', 'lab', '2026-04-06 02:36:01'),
(5, 2, 185, 2, 1, 'Tuesday', '09:40:00', '11:10:00', 'theory', '2026-04-06 02:36:01'),
(6, 2, 186, 3, 1, 'Wednesday', '09:40:00', '11:10:00', 'theory', '2026-04-06 02:36:01'),
(7, 3, 188, 2, 8, 'Monday', '08:00:00', '09:30:00', 'lab', '2026-04-06 02:36:28'),
(8, 3, 187, 1, 2, 'Tuesday', '08:00:00', '09:30:00', 'theory', '2026-04-06 02:36:28'),
(9, 3, 189, 3, 1, 'Wednesday', '11:20:00', '12:50:00', 'theory', '2026-04-06 02:36:28'),
(10, 4, 192, 3, 9, 'Monday', '08:00:00', '09:30:00', 'lab', '2026-04-06 02:37:06'),
(11, 4, 190, 1, 2, 'Tuesday', '09:40:00', '11:10:00', 'theory', '2026-04-06 02:37:06'),
(12, 4, 191, 2, 2, 'Wednesday', '08:00:00', '09:30:00', 'theory', '2026-04-06 02:37:06'),
(13, 5, 195, 3, 9, 'Monday', '09:40:00', '11:10:00', 'lab', '2026-04-06 02:37:22'),
(14, 5, 193, 1, 1, 'Tuesday', '11:20:00', '12:50:00', 'theory', '2026-04-06 02:37:22'),
(15, 5, 194, 2, 2, 'Wednesday', '09:40:00', '11:10:00', 'theory', '2026-04-06 02:37:22'),
(16, 6, 196, 1, 1, 'Thursday', '11:20:00', '12:50:00', 'theory', '2026-04-06 02:37:37'),
(17, 6, 197, 2, 2, 'Tuesday', '11:20:00', '12:50:00', 'theory', '2026-04-06 02:37:37'),
(18, 6, 198, 3, 1, 'Wednesday', '13:50:00', '15:20:00', 'theory', '2026-04-06 02:37:37'),
(22, 8, 200, 2, 10, 'Monday', '09:40:00', '11:10:00', 'lab', '2026-04-06 02:39:39'),
(23, 8, 199, 1, 1, 'Tuesday', '13:50:00', '15:20:00', 'theory', '2026-04-06 02:39:39'),
(24, 8, 200, 2, 2, 'Wednesday', '11:20:00', '12:50:00', 'theory', '2026-04-06 02:39:39'),
(25, 8, 201, 3, 1, 'Thursday', '08:00:00', '09:30:00', 'theory', '2026-04-06 02:39:39'),
(26, 9, 202, 1, 8, 'Monday', '11:20:00', '12:50:00', 'lab', '2026-04-06 02:39:58'),
(27, 9, 202, 1, 1, 'Tuesday', '15:30:00', '17:00:00', 'theory', '2026-04-06 02:39:58'),
(28, 9, 203, 2, 1, 'Wednesday', '13:50:00', '15:20:00', 'theory', '2026-04-06 02:39:58'),
(29, 9, 204, 3, 1, 'Thursday', '09:40:00', '11:10:00', 'theory', '2026-04-06 02:39:58');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','hod','professor','student') NOT NULL DEFAULT 'student',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `email_verified_at`, `password`, `role`, `status`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Krina Patel', 'krina@admin.com', '2026-04-05 22:34:58', '$2y$10$VjOceQ920S6dimQxAamL5e.u0QxvcxCJAHQj2Wjbn/UJweSVREAWu', 'admin', 'active', NULL, '2026-04-05 22:34:58', '2026-04-05 22:34:58'),
(2, 'hod_cs', 'sarah.chen@university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'hod', 'active', NULL, '2024-01-15 13:00:00', '2024-01-15 13:00:00'),
(3, 'hod_it', 'michael.torres@university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'hod', 'active', NULL, '2024-01-15 13:00:00', '2024-01-15 13:00:00'),
(4, 'hod_ee', 'priya.sharma@university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'hod', 'active', NULL, '2024-01-15 13:00:00', '2024-01-15 13:00:00'),
(5, 'hod_me', 'robert.williams@university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'hod', 'active', NULL, '2024-01-15 13:00:00', '2024-01-15 13:00:00'),
(6, 'hod_bba', 'emily.johnson@university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'hod', 'active', NULL, '2024-01-15 13:00:00', '2024-01-15 13:00:00'),
(7, 'prof_david_miller', 'david.miller@university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'professor', 'active', NULL, '2024-01-20 14:00:00', '2024-01-20 14:00:00'),
(8, 'prof_jennifer_lee', 'jennifer.lee@university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'professor', 'active', NULL, '2024-01-20 14:00:00', '2024-01-20 14:00:00'),
(9, 'prof_kevin_zhang', 'kevin.zhang@university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'professor', 'active', NULL, '2024-01-20 14:00:00', '2024-01-20 14:00:00'),
(10, 'prof_lisa_patel', 'lisa.patel@university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'professor', 'active', NULL, '2024-01-20 14:00:00', '2024-01-20 14:00:00'),
(11, 'prof_james_brown', 'james.brown@university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'professor', 'active', NULL, '2024-01-20 14:00:00', '2024-01-20 14:00:00'),
(12, 'prof_anna_garcia', 'anna.garcia@university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'professor', 'active', NULL, '2024-01-20 14:00:00', '2024-01-20 14:00:00'),
(13, 'prof_mark_wilson', 'mark.wilson@university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'professor', 'active', NULL, '2024-01-20 14:00:00', '2024-01-20 14:00:00'),
(14, 'prof_nina_kumar', 'nina.kumar@university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'professor', 'active', NULL, '2024-01-20 14:00:00', '2024-01-20 14:00:00'),
(15, 'prof_steven_park', 'steven.park@university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'professor', 'active', NULL, '2024-01-20 14:00:00', '2024-01-20 14:00:00'),
(16, 'prof_catherine_white', 'catherine.white@university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'professor', 'active', NULL, '2024-01-20 14:00:00', '2024-01-20 14:00:00'),
(17, 'prof_daniel_reed', 'daniel.reed@university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'professor', 'active', NULL, '2024-01-20 14:00:00', '2024-01-20 14:00:00'),
(18, 'prof_michelle_adams', 'michelle.adams@university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'professor', 'active', NULL, '2024-01-20 14:00:00', '2024-01-20 14:00:00'),
(19, 'prof_thomas_scott', 'thomas.scott@university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'professor', 'active', NULL, '2024-01-20 14:00:00', '2024-01-20 14:00:00'),
(20, 'prof_rachel_morris', 'rachel.morris@university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'professor', 'active', NULL, '2024-01-20 14:00:00', '2024-01-20 14:00:00'),
(21, 'prof_brian_taylor', 'brian.taylor@university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'professor', 'active', NULL, '2024-01-20 14:00:00', '2024-01-20 14:00:00'),
(22, 'student_cs_01', 'alice.smith@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(23, 'student_cs_02', 'bob.johnson@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(24, 'student_cs_03', 'charlie.williams@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(25, 'student_cs_04', 'diana.brown@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(26, 'student_cs_05', 'edward.jones@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(27, 'student_cs_06', 'fatima.garcia@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(28, 'student_cs_07', 'george.miller@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(29, 'student_cs_08', 'hannah.davis@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(30, 'student_cs_09', 'ivan.wilson@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(31, 'student_cs_10', 'julia.moore@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(32, 'student_cs_11', 'kevin.taylor@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(33, 'student_cs_12', 'laura.anderson@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(34, 'student_it_01', 'mohammed.thomas@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(35, 'student_it_02', 'natalie.jackson@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(36, 'student_it_03', 'oscar.white@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(37, 'student_it_04', 'patricia.harris@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(38, 'student_it_05', 'quinn.martin@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(39, 'student_it_06', 'rachel.thompson@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(40, 'student_it_07', 'samuel.robinson@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(41, 'student_it_08', 'teresa.clark@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(42, 'student_it_09', 'uma.lewis@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(43, 'student_it_10', 'victor.lee@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(44, 'student_it_11', 'wendy.walker@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(45, 'student_it_12', 'xavier.hall@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(46, 'student_ee_01', 'yolanda.allen@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(47, 'student_ee_02', 'zachary.young@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(48, 'student_ee_03', 'amy.king@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(49, 'student_ee_04', 'brian.wright@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(50, 'student_ee_05', 'clara.hill@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(51, 'student_ee_06', 'derek.scott@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(52, 'student_ee_07', 'elena.green@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(53, 'student_ee_08', 'frank.adams@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(54, 'student_ee_09', 'grace.baker@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(55, 'student_ee_10', 'henry.nelson@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(56, 'student_ee_11', 'iris.carter@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(57, 'student_ee_12', 'jack.mitchell@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(58, 'student_me_01', 'karen.perez@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(59, 'student_me_02', 'louis.roberts@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(60, 'student_me_03', 'maria.turner@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(61, 'student_me_04', 'nathan.phillips@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(62, 'student_me_05', 'olivia.campbell@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(63, 'student_me_06', 'peter.parker@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(64, 'student_me_07', 'queenie.evans@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(65, 'student_me_08', 'ryan.edwards@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(66, 'student_me_09', 'sara.collins@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(67, 'student_me_10', 'tom.stewart@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(68, 'student_me_11', 'ursula.morris@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(69, 'student_me_12', 'vincent.reed@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(70, 'student_bba_01', 'wanda.cook@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(71, 'student_bba_02', 'xander.rogers@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(72, 'student_bba_03', 'yvonne.morgan@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(73, 'student_bba_04', 'zara.bell@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(74, 'student_bba_05', 'aaron.murphy@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(75, 'student_bba_06', 'betty.bailey@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(76, 'student_bba_07', 'carlos.rivera@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(77, 'student_bba_08', 'dorothy.cooper@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(78, 'student_bba_09', 'ethan.richardson@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(79, 'student_bba_10', 'florence.cox@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(80, 'student_bba_11', 'gabe.howard@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00'),
(81, 'student_bba_12', 'hina.ward@student.university.edu', NULL, '$2y$12$HsfpfiLbngARzMlC7rw69OTggOzilTezQyIcWZfPiKk0ve83VsshK', 'student', 'active', NULL, '2024-08-20 12:00:00', '2024-08-20 12:00:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `activity_logs_user_id_foreign` (`user_id`);

--
-- Indexes for table `conflicts`
--
ALTER TABLE `conflicts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `conflicts_timetable_id_foreign` (`timetable_id`),
  ADD KEY `conflicts_slot_id_1_foreign` (`slot_id_1`),
  ADD KEY `conflicts_slot_id_2_foreign` (`slot_id_2`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `courses_code_unique` (`code`),
  ADD KEY `courses_department_id_foreign` (`department_id`);

--
-- Indexes for table `course_assignments`
--
ALTER TABLE `course_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_assignments_course_section_id_foreign` (`course_section_id`),
  ADD KEY `course_assignments_teacher_id_foreign` (`teacher_id`);

--
-- Indexes for table `course_sections`
--
ALTER TABLE `course_sections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_sections_course_id_foreign` (`course_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `departments_code_unique` (`code`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `fee_payments`
--
ALTER TABLE `fee_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fee_payments_student_id_index` (`student_id`),
  ADD KEY `fee_payments_course_id_foreign` (`course_id`);

--
-- Indexes for table `hods`
--
ALTER TABLE `hods`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `hods_department_id_unique` (`department_id`),
  ADD KEY `hods_user_id_foreign` (`user_id`),
  ADD KEY `hods_teacher_id_foreign` (`teacher_id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `rooms_room_number_unique` (`room_number`);

--
-- Indexes for table `room_availability`
--
ALTER TABLE `room_availability`
  ADD PRIMARY KEY (`id`),
  ADD KEY `room_availability_room_id_foreign` (`room_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `students_roll_no_unique` (`roll_no`),
  ADD KEY `students_user_id_foreign` (`user_id`),
  ADD KEY `students_department_id_foreign` (`department_id`);

--
-- Indexes for table `student_course_registrations`
--
ALTER TABLE `student_course_registrations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_course_registrations_student_id_foreign` (`student_id`),
  ADD KEY `student_course_registrations_course_section_id_foreign` (`course_section_id`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `teachers_employee_id_unique` (`employee_id`),
  ADD KEY `teachers_user_id_foreign` (`user_id`),
  ADD KEY `teachers_department_id_foreign` (`department_id`);

--
-- Indexes for table `teacher_availability`
--
ALTER TABLE `teacher_availability`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_availability_teacher_id_foreign` (`teacher_id`);

--
-- Indexes for table `timetables`
--
ALTER TABLE `timetables`
  ADD PRIMARY KEY (`id`),
  ADD KEY `timetables_department_id_foreign` (`department_id`),
  ADD KEY `timetables_generated_by_foreign` (`generated_by`);

--
-- Indexes for table `timetable_slots`
--
ALTER TABLE `timetable_slots`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ts_teacher_day_time` (`teacher_id`,`day_of_week`,`start_time`,`end_time`),
  ADD KEY `ts_room_day_time` (`room_id`,`day_of_week`,`start_time`,`end_time`),
  ADD KEY `ts_section_day_time` (`course_section_id`,`day_of_week`,`start_time`,`end_time`),
  ADD KEY `ts_timetable_id` (`timetable_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `conflicts`
--
ALTER TABLE `conflicts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=97;

--
-- AUTO_INCREMENT for table `course_assignments`
--
ALTER TABLE `course_assignments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=232;

--
-- AUTO_INCREMENT for table `course_sections`
--
ALTER TABLE `course_sections`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=205;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fee_payments`
--
ALTER TABLE `fee_payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=228;

--
-- AUTO_INCREMENT for table `hods`
--
ALTER TABLE `hods`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `room_availability`
--
ALTER TABLE `room_availability`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=126;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `student_course_registrations`
--
ALTER TABLE `student_course_registrations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=542;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `teacher_availability`
--
ALTER TABLE `teacher_availability`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT for table `timetables`
--
ALTER TABLE `timetables`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `timetable_slots`
--
ALTER TABLE `timetable_slots`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `conflicts`
--
ALTER TABLE `conflicts`
  ADD CONSTRAINT `conflicts_slot_id_1_foreign` FOREIGN KEY (`slot_id_1`) REFERENCES `timetable_slots` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `conflicts_slot_id_2_foreign` FOREIGN KEY (`slot_id_2`) REFERENCES `timetable_slots` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `conflicts_timetable_id_foreign` FOREIGN KEY (`timetable_id`) REFERENCES `timetables` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `course_assignments`
--
ALTER TABLE `course_assignments`
  ADD CONSTRAINT `course_assignments_course_section_id_foreign` FOREIGN KEY (`course_section_id`) REFERENCES `course_sections` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_assignments_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `course_sections`
--
ALTER TABLE `course_sections`
  ADD CONSTRAINT `course_sections_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `fee_payments`
--
ALTER TABLE `fee_payments`
  ADD CONSTRAINT `fee_payments_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `hods`
--
ALTER TABLE `hods`
  ADD CONSTRAINT `hods_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `hods_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `hods_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `room_availability`
--
ALTER TABLE `room_availability`
  ADD CONSTRAINT `room_availability_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `students_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_course_registrations`
--
ALTER TABLE `student_course_registrations`
  ADD CONSTRAINT `student_course_registrations_course_section_id_foreign` FOREIGN KEY (`course_section_id`) REFERENCES `course_sections` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_course_registrations_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `teachers_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teachers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_availability`
--
ALTER TABLE `teacher_availability`
  ADD CONSTRAINT `teacher_availability_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `timetables`
--
ALTER TABLE `timetables`
  ADD CONSTRAINT `timetables_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timetables_generated_by_foreign` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `timetable_slots`
--
ALTER TABLE `timetable_slots`
  ADD CONSTRAINT `timetable_slots_course_section_id_foreign` FOREIGN KEY (`course_section_id`) REFERENCES `course_sections` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timetable_slots_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timetable_slots_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timetable_slots_timetable_id_foreign` FOREIGN KEY (`timetable_id`) REFERENCES `timetables` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
