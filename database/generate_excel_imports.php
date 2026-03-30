<?php
/**
 * Generate clean Excel-importable CSV files for all import types.
 *
 * Run via:
 *   php artisan tinker --execute="require base_path('database/generate_excel_imports.php');"
 *
 * Output → storage/app/excel-imports/*.csv
 * Open each CSV in Excel, then use Admin → Excel Import to upload.
 *
 * Import ORDER matters (foreign keys):
 *   1. teachers.csv
 *   2. courses.csv
 *   3. course_sections.csv
 *   4. course_assignments.csv
 *   5. teacher_availability.csv
 *   6. students.csv
 */

$dir = storage_path('app/excel-imports');
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

function writeCsv(string $path, array $headers, array $rows): void {
    $fp = fopen($path, 'w');
    fputcsv($fp, $headers);
    foreach ($rows as $row) {
        fputcsv($fp, $row);
    }
    fclose($fp);
    echo "✓ Written: " . basename($path) . " (" . count($rows) . " rows)\n";
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. TEACHERS
// Columns: employee_id, name, email, department_code, status
// ─────────────────────────────────────────────────────────────────────────────
writeCsv("$dir/teachers.csv",
    ['employee_id', 'name', 'email', 'department_code', 'status'],
    [
        // CS Department — 3 teachers
        ['T001', 'Neha Kapoor',    'neha.kapoor@college.edu',    'CS',  'active'],
        ['T002', 'Rajesh Nair',    'rajesh.nair@college.edu',    'CS',  'active'],
        ['T003', 'Arjun Mehta',    'arjun.mehta@college.edu',    'CS',  'active'],
        // ICT Department — 3 teachers
        ['T004', 'Priya Sharma',   'priya.sharma@college.edu',   'ICT', 'active'],
        ['T005', 'Vikram Singh',   'vikram.singh@college.edu',   'ICT', 'active'],
        ['T006', 'Sunita Patel',   'sunita.patel@college.edu',   'ICT', 'active'],
        // EE Department — 3 teachers
        ['T007', 'Ravi Kumar',     'ravi.kumar@college.edu',     'EE',  'active'],
        ['T008', 'Meena Iyer',     'meena.iyer@college.edu',     'EE',  'active'],
        ['T009', 'Suresh Reddy',   'suresh.reddy@college.edu',   'EE',  'active'],
    ]
);

// ─────────────────────────────────────────────────────────────────────────────
// 2. COURSES
// Columns: code, name, department_code, credits, type, semester, status, description
// Note: prerequisite_course_code & prerequisite_mandatory are set via seed after import
// ─────────────────────────────────────────────────────────────────────────────
writeCsv("$dir/courses.csv",
    ['code', 'name', 'department_code', 'credits', 'type', 'semester', 'status', 'description'],
    [
        // ── CS Semester 1 ──
        ['CS111', 'Programming Fundamentals',   'CS',  3, 'theory', 1, 'active', 'Introduction to programming using Python'],
        ['CS112', 'Discrete Mathematics',        'CS',  3, 'theory', 1, 'active', 'Logic, sets, relations and graph theory'],
        ['CS113', 'Digital Logic',               'CS',  3, 'theory', 1, 'active', 'Boolean algebra, gates and combinational circuits'],
        // ── CS Semester 2 ──
        ['CS121', 'Object Oriented Programming', 'CS',  3, 'theory', 2, 'active', 'OOP concepts using Java — prereq: CS111'],
        ['CS122', 'Data Structures',             'CS',  3, 'theory', 2, 'active', 'Arrays, linked lists, trees, graphs — prereq: CS112'],
        ['CS123', 'Computer Organization',       'CS',  3, 'theory', 2, 'active', 'CPU architecture, memory and I/O systems'],
        // ── ICT Semester 1 ──
        ['ICT111', 'IT Foundations',             'ICT', 3, 'theory', 1, 'active', 'Fundamentals of information technology'],
        ['ICT112', 'Web Technologies',           'ICT', 3, 'theory', 1, 'active', 'HTML, CSS, JavaScript basics'],
        ['ICT113', 'Database Basics',            'ICT', 3, 'theory', 1, 'active', 'Introduction to relational databases'],
        // ── ICT Semester 2 ──
        ['ICT121', 'Database Systems',           'ICT', 3, 'theory', 2, 'active', 'Advanced SQL, normalization — prereq: ICT111'],
        ['ICT122', 'Network Fundamentals',       'ICT', 3, 'theory', 2, 'active', 'TCP/IP, routing, network layers — prereq: ICT112 (advisory)'],
        ['ICT123', 'Software Engineering',       'ICT', 3, 'theory', 2, 'active', 'SDLC, agile, design patterns'],
        // ── EE Semester 1 ──
        ['EE111', 'Engineering Mathematics I',   'EE',  3, 'theory', 1, 'active', 'Calculus and differential equations'],
        ['EE112', 'Basic Electrical Circuits',   'EE',  3, 'theory', 1, 'active', 'Ohm\'s law, Kirchhoff\'s laws, circuit analysis'],
        ['EE113', 'Electronic Devices',          'EE',  3, 'theory', 1, 'active', 'Semiconductors, diodes, transistors'],
        // ── EE Semester 2 ──
        ['EE121', 'Circuit Analysis',            'EE',  3, 'theory', 2, 'active', 'AC/DC circuits, Thevenin — prereq: EE111'],
        ['EE122', 'Electrical Machines',         'EE',  3, 'theory', 2, 'active', 'Transformers, motors, generators — prereq: EE112 (advisory)'],
        ['EE123', 'Signals and Systems',         'EE',  3, 'theory', 2, 'active', 'Fourier analysis, Laplace transforms'],
    ]
);

// ─────────────────────────────────────────────────────────────────────────────
// 3. COURSE SECTIONS
// Columns: course_code, section_number, term, year, max_students
// One section per course — Fall 2026
// ─────────────────────────────────────────────────────────────────────────────
$sectionRows = [];
$allCodes = [
    'CS111','CS112','CS113','CS121','CS122','CS123',
    'ICT111','ICT112','ICT113','ICT121','ICT122','ICT123',
    'EE111','EE112','EE113','EE121','EE122','EE123',
];
foreach ($allCodes as $code) {
    $sectionRows[] = [$code, 1, 'Fall', 2026, 40];
}
writeCsv("$dir/course_sections.csv",
    ['course_code', 'section_number', 'term', 'year', 'max_students'],
    $sectionRows
);

// ─────────────────────────────────────────────────────────────────────────────
// 4. COURSE ASSIGNMENTS (teacher → section)
// Columns: course_code, section_number, term, year, teacher_employee_id, component
// Logic: each CS course → T001–T003, ICT → T004–T006, EE → T007–T009 (rotating)
// ─────────────────────────────────────────────────────────────────────────────
$assignments = [
    // CS — 3 teachers rotate across 6 courses
    ['CS111', 1, 'Fall', 2026, 'T001', 'theory'],
    ['CS112', 1, 'Fall', 2026, 'T002', 'theory'],
    ['CS113', 1, 'Fall', 2026, 'T003', 'theory'],
    ['CS121', 1, 'Fall', 2026, 'T001', 'theory'],
    ['CS122', 1, 'Fall', 2026, 'T002', 'theory'],
    ['CS123', 1, 'Fall', 2026, 'T003', 'theory'],
    // ICT
    ['ICT111', 1, 'Fall', 2026, 'T004', 'theory'],
    ['ICT112', 1, 'Fall', 2026, 'T005', 'theory'],
    ['ICT113', 1, 'Fall', 2026, 'T006', 'theory'],
    ['ICT121', 1, 'Fall', 2026, 'T004', 'theory'],
    ['ICT122', 1, 'Fall', 2026, 'T005', 'theory'],
    ['ICT123', 1, 'Fall', 2026, 'T006', 'theory'],
    // EE
    ['EE111',  1, 'Fall', 2026, 'T007', 'theory'],
    ['EE112',  1, 'Fall', 2026, 'T008', 'theory'],
    ['EE113',  1, 'Fall', 2026, 'T009', 'theory'],
    ['EE121',  1, 'Fall', 2026, 'T007', 'theory'],
    ['EE122',  1, 'Fall', 2026, 'T008', 'theory'],
    ['EE123',  1, 'Fall', 2026, 'T009', 'theory'],
];
writeCsv("$dir/course_assignments.csv",
    ['course_code', 'section_number', 'term', 'year', 'teacher_employee_id', 'component'],
    $assignments
);

// ─────────────────────────────────────────────────────────────────────────────
// 5. TEACHER AVAILABILITY
// Columns: teacher_employee_id, term, year, day_of_week, start_time, end_time, max_hours_per_week
// Each teacher available Mon-Fri in two slots (morning + afternoon)
// Non-conflicting schedule: CS in morning, ICT midday, EE afternoon
// ─────────────────────────────────────────────────────────────────────────────
$availRows = [];
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

$teacherSlots = [
    // CS teachers — morning slots
    'T001' => [['08:00', '10:00'], ['10:00', '12:00']],
    'T002' => [['08:00', '10:00'], ['10:00', '12:00']],
    'T003' => [['08:00', '10:00'], ['10:00', '12:00']],
    // ICT teachers — midday slots
    'T004' => [['10:00', '12:00'], ['12:00', '14:00']],
    'T005' => [['10:00', '12:00'], ['12:00', '14:00']],
    'T006' => [['10:00', '12:00'], ['12:00', '14:00']],
    // EE teachers — afternoon slots
    'T007' => [['13:00', '15:00'], ['15:00', '17:00']],
    'T008' => [['13:00', '15:00'], ['15:00', '17:00']],
    'T009' => [['13:00', '15:00'], ['15:00', '17:00']],
];

foreach ($teacherSlots as $empId => $slots) {
    foreach ($days as $day) {
        foreach ($slots as [$start, $end]) {
            $availRows[] = [$empId, 'Fall', 2026, $day, $start, $end, 20];
        }
    }
}
writeCsv("$dir/teacher_availability.csv",
    ['teacher_employee_id', 'term', 'year', 'day_of_week', 'start_time', 'end_time', 'max_hours_per_week'],
    $availRows
);

// ─────────────────────────────────────────────────────────────────────────────
// 6. STUDENTS
// Columns: roll_no, name, email, department_code, semester, status
//
// Real-world scenario per department (6 students each):
//   Sem 1 — new students, haven't finished Sem 1 yet
//   Sem 2 — advanced students (passed Sem 1)
//
// NOTE: After importing, run test_data_seed.php to set registrations,
//       semester_1_result, and advance qualifying students to Sem 2.
// ─────────────────────────────────────────────────────────────────────────────
writeCsv("$dir/students.csv",
    ['roll_no', 'name', 'email', 'department_code', 'semester', 'status'],
    [
        // ── CS Students ──
        // Sem 1 (new / failed a subject / in-progress)
        ['CS2026001', 'Aisha Khan',      'aisha.khan@student.edu',      'CS',  1, 'active'],  // will advance to Sem2
        ['CS2026002', 'Rohit Verma',     'rohit.verma@student.edu',     'CS',  1, 'active'],  // failed CS111 → stays Sem1
        ['CS2026003', 'Meera Joshi',     'meera.joshi@student.edu',     'CS',  1, 'active'],  // all enrolled, in-progress
        // Sem 2 (already advanced from last intake)
        ['CS2026004', 'Zoya Ahmed',      'zoya.ahmed@student.edu',      'CS',  2, 'active'],
        ['CS2026005', 'Nikhil Saini',    'nikhil.saini@student.edu',    'CS',  2, 'active'],  // failed CS121 → retake
        ['CS2026006', 'Tanvi Gupta',     'tanvi.gupta@student.edu',     'CS',  2, 'active'],

        // ── ICT Students ──
        ['ICT2026001', 'Karan Shah',     'karan.shah@student.edu',      'ICT', 1, 'active'],  // failed ICT111
        ['ICT2026002', 'Riya Das',       'riya.das@student.edu',        'ICT', 1, 'active'],  // all enrolled
        ['ICT2026003', 'Faizan Noor',    'faizan.noor@student.edu',     'ICT', 1, 'active'],  // will advance to Sem2
        ['ICT2026004', 'Sana Qureshi',   'sana.qureshi@student.edu',    'ICT', 2, 'active'],
        ['ICT2026005', 'Dev Malhotra',   'dev.malhotra@student.edu',    'ICT', 2, 'active'],  // failed ICT121
        ['ICT2026006', 'Priyal Jain',    'priyal.jain@student.edu',     'ICT', 2, 'active'],

        // ── EE Students ──
        ['EE2026001',  'Aarav Kulkarni', 'aarav.kulkarni@student.edu',  'EE',  1, 'active'],  // failed EE111
        ['EE2026002',  'Ishita Paul',    'ishita.paul@student.edu',     'EE',  1, 'active'],  // will advance to Sem2
        ['EE2026003',  'Manav Batra',    'manav.batra@student.edu',     'EE',  1, 'active'],  // all enrolled
        ['EE2026004',  'Naina Thomas',   'naina.thomas@student.edu',    'EE',  2, 'active'],
        ['EE2026005',  'Yash Patil',     'yash.patil@student.edu',      'EE',  2, 'active'],  // failed EE121
        ['EE2026006',  'Simran Kaur',    'simran.kaur@student.edu',     'EE',  2, 'active'],
    ]
);

// ─────────────────────────────────────────────────────────────────────────────
// Done
// ─────────────────────────────────────────────────────────────────────────────
echo "\n✅ All CSV files written to: storage/app/excel-imports/\n";
echo "\nImport them in this exact order via Admin → Excel Import:\n";
echo "  1. teachers.csv\n";
echo "  2. courses.csv\n";
echo "  3. course_sections.csv\n";
echo "  4. course_assignments.csv\n";
echo "  5. teacher_availability.csv\n";
echo "  6. students.csv\n";
echo "\nAfter all imports are done, run the test data seed to set\n";
echo "registrations, results, and semester advancement:\n";
echo "  php artisan tinker --execute=\"require base_path('database/test_data_seed.php');\"\n";
