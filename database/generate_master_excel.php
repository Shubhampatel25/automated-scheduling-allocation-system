<?php
/**
 * Generates a single Excel file covering EVERY import type.
 *
 * Run:  php artisan tinker --execute="require base_path('database/generate_master_excel.php');"
 * File: storage/app/master_import.xlsx
 *
 * Sheet order matches the SchedulingImport dependency chain:
 *   1. users              — all logins (admin, hods, professors, students)
 *   2. departments        — CS, ICT, EE
 *   3. rooms              — classrooms + labs
 *   4. teachers           — 12 teachers including 3 HOD-teachers
 *   5. hods               — 1 per department (links user+teacher+dept)
 *   6. students           — 18 students across 3 depts
 *   7. teacher_availability
 *   8. room_availability
 *   9. courses            — 18 courses (Sem1+Sem2 per dept) with prereqs
 *  10. course_sections    — 1 section per course, Fall 2026
 *  11. course_assignments — teacher → section assignments
 *
 * Default password for ALL accounts: Password@123
 */

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

$spreadsheet = new Spreadsheet();
$spreadsheet->removeSheetByIndex(0);

// ── bcrypt hash for "Password@123" ───────────────────────────────────────────
$hash = bcrypt('Password@123');

// ── Shared style helper ───────────────────────────────────────────────────────
function makeSheet(
    Spreadsheet $wb,
    string $sheetName,
    array  $headers,
    array  $rows,
    string $headerHex = '4F46E5'
): void {
    $sheet = $wb->createSheet();
    $sheet->setTitle($sheetName);
    $colCount = count($headers);
    $lastCol  = Coordinate::stringFromColumnIndex($colCount);

    // ── Header row ────────────────────────────────────────────────────────────
    foreach ($headers as $i => $h) {
        $sheet->getCellByColumnAndRow($i + 1, 1)->setValue($h);
    }
    $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
        'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $headerHex]],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER],
        'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN,
                                         'color'       => ['rgb' => 'CCCCCC']]],
    ]);
    $sheet->getRowDimension(1)->setRowHeight(22);

    // ── Data rows ─────────────────────────────────────────────────────────────
    foreach ($rows as $ri => $row) {
        $excelRow = $ri + 2;
        foreach ($row as $ci => $val) {
            $sheet->getCellByColumnAndRow($ci + 1, $excelRow)->setValue($val);
        }
        $range = "A{$excelRow}:{$lastCol}{$excelRow}";
        $fillHex = $ri % 2 === 0 ? 'FFFFFF' : 'F5F3FF';
        $sheet->getStyle($range)->applyFromArray([
            'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $fillHex]],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN,
                                           'color'       => ['rgb' => 'E5E7EB']]],
        ]);
        $sheet->getRowDimension($excelRow)->setRowHeight(18);
    }

    // ── Auto-size + freeze ────────────────────────────────────────────────────
    for ($c = 1; $c <= $colCount; $c++) {
        $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($c))->setAutoSize(true);
    }
    $sheet->freezePane('A2');
}

// =============================================================================
// SHEET 1 — users
// ALL logins.  Password column must be a bcrypt hash (importer stores as-is).
// IDs are fixed so HODs sheet can reference them directly.
//   1        → admin
//   2–4      → hod (CS / ICT / EE)
//   5–13     → professor (T001–T009, with HOD teachers T000CS/ICT/EE at 2–4)
//   14–31    → student
// =============================================================================
makeSheet($spreadsheet, 'users',
    ['id','username','email','password','role','status'],
    [
        // ── Admin ──
        [1,  'admin',         'admin@college.edu',         $hash, 'admin',     'active'],
        // ── HOD teachers ──
        [2,  'hod_cs',        'hod.cs@college.edu',        $hash, 'hod',       'active'],
        [3,  'hod_ict',       'hod.ict@college.edu',       $hash, 'hod',       'active'],
        [4,  'hod_ee',        'hod.ee@college.edu',        $hash, 'hod',       'active'],
        // ── Professors ──
        [5,  'neha_kapoor',   'neha.kapoor@college.edu',   $hash, 'professor', 'active'],
        [6,  'rajesh_nair',   'rajesh.nair@college.edu',   $hash, 'professor', 'active'],
        [7,  'arjun_mehta',   'arjun.mehta@college.edu',   $hash, 'professor', 'active'],
        [8,  'priya_sharma',  'priya.sharma@college.edu',  $hash, 'professor', 'active'],
        [9,  'vikram_singh',  'vikram.singh@college.edu',  $hash, 'professor', 'active'],
        [10, 'sunita_patel',  'sunita.patel@college.edu',  $hash, 'professor', 'active'],
        [11, 'ravi_kumar',    'ravi.kumar@college.edu',    $hash, 'professor', 'active'],
        [12, 'meena_iyer',    'meena.iyer@college.edu',    $hash, 'professor', 'active'],
        [13, 'suresh_reddy',  'suresh.reddy@college.edu',  $hash, 'professor', 'active'],
        // ── CS Students ──
        [14, 'aisha_khan',    'aisha.khan@student.edu',    $hash, 'student',   'active'],
        [15, 'rohit_verma',   'rohit.verma@student.edu',   $hash, 'student',   'active'],
        [16, 'meera_joshi',   'meera.joshi@student.edu',   $hash, 'student',   'active'],
        [17, 'zoya_ahmed',    'zoya.ahmed@student.edu',    $hash, 'student',   'active'],
        [18, 'nikhil_saini',  'nikhil.saini@student.edu',  $hash, 'student',   'active'],
        [19, 'tanvi_gupta',   'tanvi.gupta@student.edu',   $hash, 'student',   'active'],
        // ── ICT Students ──
        [20, 'karan_shah',    'karan.shah@student.edu',    $hash, 'student',   'active'],
        [21, 'riya_das',      'riya.das@student.edu',      $hash, 'student',   'active'],
        [22, 'faizan_noor',   'faizan.noor@student.edu',   $hash, 'student',   'active'],
        [23, 'sana_qureshi',  'sana.qureshi@student.edu',  $hash, 'student',   'active'],
        [24, 'dev_malhotra',  'dev.malhotra@student.edu',  $hash, 'student',   'active'],
        [25, 'priyal_jain',   'priyal.jain@student.edu',   $hash, 'student',   'active'],
        // ── EE Students ──
        [26, 'aarav_kulkarni','aarav.kulkarni@student.edu',$hash, 'student',   'active'],
        [27, 'ishita_paul',   'ishita.paul@student.edu',   $hash, 'student',   'active'],
        [28, 'manav_batra',   'manav.batra@student.edu',   $hash, 'student',   'active'],
        [29, 'naina_thomas',  'naina.thomas@student.edu',  $hash, 'student',   'active'],
        [30, 'yash_patil',    'yash.patil@student.edu',    $hash, 'student',   'active'],
        [31, 'simran_kaur',   'simran.kaur@student.edu',   $hash, 'student',   'active'],
    ],
    '1E3A5F'
);

// =============================================================================
// SHEET 2 — departments
// id is fixed (1=CS, 2=ICT, 3=EE) so HODs sheet can reference dept ids.
// =============================================================================
makeSheet($spreadsheet, 'departments',
    ['id','code','name','description'],
    [
        [1, 'CS',  'Computer Science',                     'Bachelor of Computer Science'],
        [2, 'ICT', 'Information & Communication Technology','Bachelor of ICT'],
        [3, 'EE',  'Electrical Engineering',               'Bachelor of Electrical Engineering'],
    ],
    '374151'
);

// =============================================================================
// SHEET 3 — rooms
// id fixed (1–9) so room_availability can reference them.
// =============================================================================
makeSheet($spreadsheet, 'rooms',
    ['id','room_number','building','type','capacity','equipment','status'],
    [
        [1, 'CS-101',  'CS Block',  'classroom',   40, 'Projector, Whiteboard',    'available'],
        [2, 'CS-102',  'CS Block',  'classroom',   40, 'Projector, Whiteboard',    'available'],
        [3, 'CS-LAB',  'CS Block',  'lab',         30, 'Computers, Projector',     'available'],
        [4, 'ICT-101', 'ICT Block', 'classroom',   40, 'Projector, Whiteboard',    'available'],
        [5, 'ICT-102', 'ICT Block', 'classroom',   40, 'Projector, Whiteboard',    'available'],
        [6, 'ICT-LAB', 'ICT Block', 'lab',         30, 'Computers, Projector',     'available'],
        [7, 'EE-101',  'EE Block',  'classroom',   40, 'Projector, Whiteboard',    'available'],
        [8, 'EE-102',  'EE Block',  'classroom',   40, 'Projector, Whiteboard',    'available'],
        [9, 'EE-LAB',  'EE Block',  'lab',         25, 'Oscilloscopes, Equipment', 'available'],
    ],
    '92400E'
);

// =============================================================================
// SHEET 4 — teachers
// id fixed.  Includes 3 HOD-teachers (id 1-3) + 9 regular teachers (id 4-12).
// Uses department_code lookup so dept must be imported first.
// =============================================================================
makeSheet($spreadsheet, 'teachers',
    ['id','user_id','employee_id','name','email','department_code','status'],
    [
        // HOD teachers
        [1,  2,  'HOD-CS',  'Dr. James Wilson',  'hod.cs@college.edu',       'CS',  'active'],
        [2,  3,  'HOD-ICT', 'Dr. Sarah Ahmed',   'hod.ict@college.edu',      'ICT', 'active'],
        [3,  4,  'HOD-EE',  'Dr. Robert Chen',   'hod.ee@college.edu',       'EE',  'active'],
        // CS professors
        [4,  5,  'T001',    'Neha Kapoor',       'neha.kapoor@college.edu',  'CS',  'active'],
        [5,  6,  'T002',    'Rajesh Nair',       'rajesh.nair@college.edu',  'CS',  'active'],
        [6,  7,  'T003',    'Arjun Mehta',       'arjun.mehta@college.edu',  'CS',  'active'],
        // ICT professors
        [7,  8,  'T004',    'Priya Sharma',      'priya.sharma@college.edu', 'ICT', 'active'],
        [8,  9,  'T005',    'Vikram Singh',      'vikram.singh@college.edu', 'ICT', 'active'],
        [9,  10, 'T006',    'Sunita Patel',      'sunita.patel@college.edu', 'ICT', 'active'],
        // EE professors
        [10, 11, 'T007',    'Ravi Kumar',        'ravi.kumar@college.edu',   'EE',  'active'],
        [11, 12, 'T008',    'Meena Iyer',        'meena.iyer@college.edu',   'EE',  'active'],
        [12, 13, 'T009',    'Suresh Reddy',      'suresh.reddy@college.edu', 'EE',  'active'],
    ],
    '065F46'
);

// =============================================================================
// SHEET 5 — hods
// Requires integer department_id, user_id, teacher_id — all fixed above.
// =============================================================================
makeSheet($spreadsheet, 'hods',
    ['user_id','teacher_id','department_id','appointed_date','status'],
    [
        [2, 1, 1, '2026-01-01', 'active'],   // CS  → Dr. James Wilson
        [3, 2, 2, '2026-01-01', 'active'],   // ICT → Dr. Sarah Ahmed
        [4, 3, 3, '2026-01-01', 'active'],   // EE  → Dr. Robert Chen
    ],
    '7C3AED'
);

// =============================================================================
// SHEET 6 — students
// id fixed (1-18) so seed can reference them reliably.
// Uses department_code lookup.
// NOTE: semester is set to the STARTING semester — seed advances qualifying ones.
// =============================================================================
makeSheet($spreadsheet, 'students',
    ['id','user_id','roll_no','name','email','department_code','semester','status'],
    [
        // CS — Sem1 (new intake: Aisha advances, Rohit fails, Meera in-progress)
        [1,  14, 'CS2026001', 'Aisha Khan',      'aisha.khan@student.edu',      'CS',  1, 'active'],
        [2,  15, 'CS2026002', 'Rohit Verma',     'rohit.verma@student.edu',     'CS',  1, 'active'],
        [3,  16, 'CS2026003', 'Meera Joshi',     'meera.joshi@student.edu',     'CS',  1, 'active'],
        // CS — Sem2 (previous intake, already advanced)
        [4,  17, 'CS2026004', 'Zoya Ahmed',      'zoya.ahmed@student.edu',      'CS',  2, 'active'],
        [5,  18, 'CS2026005', 'Nikhil Saini',    'nikhil.saini@student.edu',    'CS',  2, 'active'],
        [6,  19, 'CS2026006', 'Tanvi Gupta',     'tanvi.gupta@student.edu',     'CS',  2, 'active'],
        // ICT — Sem1
        [7,  20, 'ICT2026001','Karan Shah',      'karan.shah@student.edu',      'ICT', 1, 'active'],
        [8,  21, 'ICT2026002','Riya Das',        'riya.das@student.edu',        'ICT', 1, 'active'],
        [9,  22, 'ICT2026003','Faizan Noor',     'faizan.noor@student.edu',     'ICT', 1, 'active'],
        // ICT — Sem2
        [10, 23, 'ICT2026004','Sana Qureshi',    'sana.qureshi@student.edu',    'ICT', 2, 'active'],
        [11, 24, 'ICT2026005','Dev Malhotra',    'dev.malhotra@student.edu',    'ICT', 2, 'active'],
        [12, 25, 'ICT2026006','Priyal Jain',     'priyal.jain@student.edu',     'ICT', 2, 'active'],
        // EE — Sem1
        [13, 26, 'EE2026001', 'Aarav Kulkarni',  'aarav.kulkarni@student.edu',  'EE',  1, 'active'],
        [14, 27, 'EE2026002', 'Ishita Paul',     'ishita.paul@student.edu',     'EE',  1, 'active'],
        [15, 28, 'EE2026003', 'Manav Batra',     'manav.batra@student.edu',     'EE',  1, 'active'],
        // EE — Sem2
        [16, 29, 'EE2026004', 'Naina Thomas',    'naina.thomas@student.edu',    'EE',  2, 'active'],
        [17, 30, 'EE2026005', 'Yash Patil',      'yash.patil@student.edu',      'EE',  2, 'active'],
        [18, 31, 'EE2026006', 'Simran Kaur',     'simran.kaur@student.edu',     'EE',  2, 'active'],
    ],
    'BE185D'
);

// =============================================================================
// SHEET 7 — teacher_availability
// Schedule design (no dept overlaps, enabling clean timetable generation):
//   CS  teachers → Morning    08:00–12:00
//   ICT teachers → Midday     10:00–14:00
//   EE  teachers → Afternoon  13:00–17:00
// Each teacher gets 2 slots × 5 days = 10 rows
// =============================================================================
$availRows = [];
$days = ['Monday','Tuesday','Wednesday','Thursday','Friday'];
$tSlots = [
    'HOD-CS'  => [['08:00','10:00'],['10:00','12:00']],
    'T001'    => [['08:00','10:00'],['10:00','12:00']],
    'T002'    => [['08:00','10:00'],['10:00','12:00']],
    'T003'    => [['08:00','10:00'],['10:00','12:00']],
    'HOD-ICT' => [['10:00','12:00'],['12:00','14:00']],
    'T004'    => [['10:00','12:00'],['12:00','14:00']],
    'T005'    => [['10:00','12:00'],['12:00','14:00']],
    'T006'    => [['10:00','12:00'],['12:00','14:00']],
    'HOD-EE'  => [['13:00','15:00'],['15:00','17:00']],
    'T007'    => [['13:00','15:00'],['15:00','17:00']],
    'T008'    => [['13:00','15:00'],['15:00','17:00']],
    'T009'    => [['13:00','15:00'],['15:00','17:00']],
];
foreach ($tSlots as $emp => $slots) {
    foreach ($days as $day) {
        foreach ($slots as [$s, $e]) {
            $availRows[] = [$emp, 'Fall', 2026, $day, $s, $e, 20];
        }
    }
}
makeSheet($spreadsheet, 'teacher_availability',
    ['teacher_employee_id','term','year','day_of_week','start_time','end_time','max_hours_per_week'],
    $availRows,
    '0F766E'
);

// =============================================================================
// SHEET 8 — room_availability
// room_id references fixed IDs from rooms sheet (1–9).
// CS rooms open mornings, ICT midday, EE afternoons — matches teacher schedule.
// =============================================================================
$roomAvailRows = [];
$roomSlots = [
    1 => [['08:00','10:00'],['10:00','12:00']],   // CS-101
    2 => [['08:00','10:00'],['10:00','12:00']],   // CS-102
    3 => [['08:00','10:00'],['10:00','12:00']],   // CS-LAB
    4 => [['10:00','12:00'],['12:00','14:00']],   // ICT-101
    5 => [['10:00','12:00'],['12:00','14:00']],   // ICT-102
    6 => [['10:00','12:00'],['12:00','14:00']],   // ICT-LAB
    7 => [['13:00','15:00'],['15:00','17:00']],   // EE-101
    8 => [['13:00','15:00'],['15:00','17:00']],   // EE-102
    9 => [['13:00','15:00'],['15:00','17:00']],   // EE-LAB
];
foreach ($roomSlots as $roomId => $slots) {
    foreach ($days as $day) {
        foreach ($slots as [$s, $e]) {
            $roomAvailRows[] = [$roomId, $day, $s, $e, 'available'];
        }
    }
}
makeSheet($spreadsheet, 'room_availability',
    ['room_id','day_of_week','start_time','end_time','status'],
    $roomAvailRows,
    '1D4ED8'
);

// =============================================================================
// SHEET 9 — courses
// Uses department_id (integer, fixed: 1=CS 2=ICT 3=EE).
// prerequisite_course_code & prerequisite_mandatory set here in the import
// (CoursesImport does not yet handle them, but the seed script sets them after).
// =============================================================================
makeSheet($spreadsheet, 'courses',
    ['id','code','name','department_id','credits','type','semester','status','description'],
    [
        // CS Semester 1 — no prerequisites
        [1,  'CS111', 'Programming Fundamentals',    1, 3, 'theory', 1, 'active', 'Intro to programming with Python'],
        [2,  'CS112', 'Discrete Mathematics',         1, 3, 'theory', 1, 'active', 'Logic, sets, relations, graph theory'],
        [3,  'CS113', 'Digital Logic',                1, 3, 'theory', 1, 'active', 'Boolean algebra, logic gates'],
        // CS Semester 2 — with mandatory prerequisites
        [4,  'CS121', 'Object Oriented Programming',  1, 3, 'theory', 2, 'active', 'OOP with Java — Prereq: CS111 (mandatory)'],
        [5,  'CS122', 'Data Structures',              1, 3, 'theory', 2, 'active', 'Arrays, trees, graphs — Prereq: CS112 (mandatory)'],
        [6,  'CS123', 'Computer Organization',        1, 3, 'theory', 2, 'active', 'CPU architecture, memory'],
        // ICT Semester 1
        [7,  'ICT111','IT Foundations',               2, 3, 'theory', 1, 'active', 'Fundamentals of IT'],
        [8,  'ICT112','Web Technologies',             2, 3, 'theory', 1, 'active', 'HTML, CSS, JavaScript'],
        [9,  'ICT113','Database Basics',              2, 3, 'theory', 1, 'active', 'Intro to relational databases'],
        // ICT Semester 2
        [10, 'ICT121','Database Systems',             2, 3, 'theory', 2, 'active', 'Advanced SQL — Prereq: ICT111 (mandatory)'],
        [11, 'ICT122','Network Fundamentals',         2, 3, 'theory', 2, 'active', 'TCP/IP, routing — Prereq: ICT112 (advisory)'],
        [12, 'ICT123','Software Engineering',         2, 3, 'theory', 2, 'active', 'SDLC, agile, design patterns'],
        // EE Semester 1
        [13, 'EE111', 'Engineering Mathematics I',   3, 3, 'theory', 1, 'active', 'Calculus and differential equations'],
        [14, 'EE112', 'Basic Electrical Circuits',   3, 3, 'theory', 1, 'active', "Ohm's law, Kirchhoff's laws"],
        [15, 'EE113', 'Electronic Devices',          3, 3, 'theory', 1, 'active', 'Semiconductors, diodes, transistors'],
        // EE Semester 2
        [16, 'EE121', 'Circuit Analysis',            3, 3, 'theory', 2, 'active', 'AC/DC circuits — Prereq: EE111 (mandatory)'],
        [17, 'EE122', 'Electrical Machines',         3, 3, 'theory', 2, 'active', 'Transformers, motors — Prereq: EE112 (advisory)'],
        [18, 'EE123', 'Signals and Systems',         3, 3, 'theory', 2, 'active', 'Fourier analysis, Laplace transforms'],
    ],
    'B45309'
);

// =============================================================================
// SHEET 10 — course_sections
// 1 section per course, Fall 2026, max 40 students.
// Uses course_code lookup — courses must be imported first.
// =============================================================================
$codes = ['CS111','CS112','CS113','CS121','CS122','CS123',
          'ICT111','ICT112','ICT113','ICT121','ICT122','ICT123',
          'EE111','EE112','EE113','EE121','EE122','EE123'];
$secRows = [];
foreach ($codes as $i => $code) {
    $secRows[] = [$i + 1, $code, 1, 'Fall', 2026, 40];
}
makeSheet($spreadsheet, 'course_sections',
    ['id','course_code','section_number','term','year','max_students'],
    $secRows,
    '6D28D9'
);

// =============================================================================
// SHEET 11 — course_assignments
// Teacher → section mapping. Uses course_code + section_number + term + year
// to look up section, and teacher_employee_id to look up teacher.
// HOD teachers also teach one course each.
// =============================================================================
makeSheet($spreadsheet, 'course_assignments',
    ['course_code','section_number','term','year','teacher_employee_id','component'],
    [
        // CS — HOD teaches CS123 (org), T001/T002/T003 teach rest
        ['CS111', 1, 'Fall', 2026, 'T001',   'theory'],
        ['CS112', 1, 'Fall', 2026, 'T002',   'theory'],
        ['CS113', 1, 'Fall', 2026, 'T003',   'theory'],
        ['CS121', 1, 'Fall', 2026, 'T001',   'theory'],
        ['CS122', 1, 'Fall', 2026, 'T002',   'theory'],
        ['CS123', 1, 'Fall', 2026, 'HOD-CS', 'theory'],
        // ICT
        ['ICT111', 1, 'Fall', 2026, 'T004',    'theory'],
        ['ICT112', 1, 'Fall', 2026, 'T005',    'theory'],
        ['ICT113', 1, 'Fall', 2026, 'T006',    'theory'],
        ['ICT121', 1, 'Fall', 2026, 'T004',    'theory'],
        ['ICT122', 1, 'Fall', 2026, 'T005',    'theory'],
        ['ICT123', 1, 'Fall', 2026, 'HOD-ICT', 'theory'],
        // EE
        ['EE111',  1, 'Fall', 2026, 'T007',   'theory'],
        ['EE112',  1, 'Fall', 2026, 'T008',   'theory'],
        ['EE113',  1, 'Fall', 2026, 'T009',   'theory'],
        ['EE121',  1, 'Fall', 2026, 'T007',   'theory'],
        ['EE122',  1, 'Fall', 2026, 'T008',   'theory'],
        ['EE123',  1, 'Fall', 2026, 'HOD-EE', 'theory'],
    ],
    '0369A1'
);

// =============================================================================
// Save file
// =============================================================================
$out = storage_path('app/master_import.xlsx');
(new Xlsx($spreadsheet))->save($out);

echo "\n✅  master_import.xlsx created at storage/app/\n\n";
echo "Sheets (import in this order):\n";
echo "  1.  users               (31 rows) — all logins, password: Password\@123\n";
echo "  2.  departments         (3 rows)\n";
echo "  3.  rooms               (9 rows)\n";
echo "  4.  teachers            (12 rows) — includes 3 HOD-teachers\n";
echo "  5.  hods                (3 rows)\n";
echo "  6.  students            (18 rows)\n";
echo "  7.  teacher_availability(120 rows)\n";
echo "  8.  room_availability   (90 rows)\n";
echo "  9.  courses             (18 rows)\n";
echo "  10. course_sections     (18 rows)\n";
echo "  11. course_assignments  (18 rows)\n";
echo "\nAfter import, run the seed to add registrations + results:\n";
echo "  php artisan tinker --execute=\"require base_path('database/test_data_seed.php');\"\n";
