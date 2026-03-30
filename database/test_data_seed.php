<?php
/**
 * Comprehensive Test Data Seed — 6 Academic Scenarios + Semester 3 Advancement
 * =============================================================================
 * Run via:
 *   php artisan tinker --execute="require base_path('database/test_data_seed.php');"
 *
 * SCENARIO 1 — Full Pass (eligible for ALL courses of next semester)
 *   Student 9  (Faizan, ICT): passed all ICT Sem1 → Sem2
 *
 * SCENARIO 2 — Failed Prerequisite (some courses blocked)
 *   Student 1  (Aisha, CS  Sem2): failed CS112 → CS122 BLOCKED
 *   Student 7  (Karan, ICT Sem1): failed ICT111 → will block ICT121
 *   Student 13 (Aarav, EE  Sem1): failed EE111  → will block EE121
 *
 * SCENARIO 3 — Retaking failed subjects
 *   Student 5  (Nikhil, CS  Sem2): failed CS121, PAID supplemental → can re-enroll
 *   Student 11 (Dev,    ICT Sem2): failed ICT121, NOT paid          → button locked
 *   Student 17 (Yash,   EE  Sem2): failed EE121,  NOT paid          → button locked
 *
 * SCENARIO 4 — Passed retake → eligible for dependent course
 *   Student 2  (Rohit, CS Sem2): failed CS111 → retook → PASSED CS111 → CS121 now UNLOCKED
 *
 * SCENARIO 5 — Multiple failed prerequisites (two blocks at once)
 *   Student 6  (Tanvi, CS Sem2): failed CS111 + CS112 → CS121 BLOCKED + CS122 BLOCKED
 *
 * SCENARIO 6 — Duplicate enrollment attempt (handled by controller at runtime)
 *   Student 4  (Zoya, CS Sem3): passed ALL Sem1+Sem2 courses → auto-advanced to Sem3
 *     Trying to re-register any passed course → "Already passed" error
 *
 * SCENARIO 7 — Semester 3 Advancement (Sem2 fully completed → auto-advance to Sem3)
 *   Student 4  (Zoya,   CS  Sem3): all CS  Sem2 completed+passed → auto-advanced to Sem3
 *   Student 12 (Priyal, ICT Sem3): all ICT Sem2 completed+passed → auto-advanced to Sem3
 *   Student 16 (Naina,  EE  Sem3): all EE  Sem2 completed+passed → auto-advanced to Sem3
 *   These students are seeded on semester=2 with completed Sem2 records.
 *   When they visit Register Courses the secondary check advances them to Sem3
 *   and shows available Sem3 courses.
 */

use Illuminate\Support\Facades\DB;

Artisan::call('migrate', ['--force' => true]);
echo "✓ Migrations applied\n";

$now = now()->toDateTimeString();

// ─────────────────────────────────────────────────────────────────────────────
// 1. Upsert ALL courses (Sem1, Sem2, Sem3) with correct semesters
// ─────────────────────────────────────────────────────────────────────────────
$courseSemesters = [
    'CS111' => 1,  'CS112' => 1,  'CS113' => 1,
    'CS121' => 2,  'CS122' => 2,  'CS123' => 2,
    'CS131' => 3,  'CS132' => 3,  'CS133' => 3,
    'ICT111' => 1, 'ICT112' => 1, 'ICT113' => 1,
    'ICT121' => 2, 'ICT122' => 2, 'ICT123' => 2,
    'ICT131' => 3, 'ICT132' => 3, 'ICT133' => 3,
    'EE111'  => 1, 'EE112'  => 1, 'EE113'  => 1,
    'EE121'  => 2, 'EE122'  => 2, 'EE123'  => 2,
    'EE131'  => 3, 'EE132'  => 3, 'EE133'  => 3,
];
foreach ($courseSemesters as $code => $sem) {
    DB::table('courses')->where('code', $code)->update(['semester' => $sem]);
}
echo "✓ Course semesters set (Sem1–Sem3)\n";

// ─────────────────────────────────────────────────────────────────────────────
// 2. Set prerequisites
//    Sem2 prereqs: each Sem2 course requires corresponding Sem1 course
//    Sem3 prereqs: each Sem3 course requires corresponding Sem2 course
// ─────────────────────────────────────────────────────────────────────────────
$prerequisites = [
    // Sem2 — CS
    'CS121'  => ['code' => 'CS111',  'mandatory' => true],
    'CS122'  => ['code' => 'CS112',  'mandatory' => true],
    // Sem2 — ICT
    'ICT121' => ['code' => 'ICT111', 'mandatory' => true],
    'ICT122' => ['code' => 'ICT112', 'mandatory' => false],
    // Sem2 — EE
    'EE121'  => ['code' => 'EE111',  'mandatory' => true],
    'EE122'  => ['code' => 'EE112',  'mandatory' => false],
    // Sem3 — CS
    'CS131'  => ['code' => 'CS121',  'mandatory' => true],
    'CS132'  => ['code' => 'CS122',  'mandatory' => false],
    // Sem3 — ICT
    'ICT131' => ['code' => 'ICT121', 'mandatory' => true],
    'ICT132' => ['code' => 'ICT122', 'mandatory' => false],
    // Sem3 — EE
    'EE131'  => ['code' => 'EE121',  'mandatory' => true],
    'EE132'  => ['code' => 'EE122',  'mandatory' => false],
];
foreach ($prerequisites as $code => $prereq) {
    DB::table('courses')->where('code', $code)->update([
        'prerequisite_course_code' => $prereq['code'],
        'prerequisite_mandatory'   => $prereq['mandatory'] ? 1 : 0,
    ]);
}
echo "✓ Prerequisites set (Sem2 and Sem3)\n";

// ─────────────────────────────────────────────────────────────────────────────
// 3. Add Sem3 courses to the DB if they don't exist yet
// ─────────────────────────────────────────────────────────────────────────────
$sem3Definitions = [
    // CS Sem3
    ['CS131',  'Advanced Algorithms',          1, 3, 3],
    ['CS132',  'Database Management Systems',  1, 3, 3],
    ['CS133',  'Operating Systems',            1, 3, 3],
    // ICT Sem3
    ['ICT131', 'Advanced Databases',           2, 3, 3],
    ['ICT132', 'Computer Networks',            2, 3, 3],
    ['ICT133', 'Systems Analysis & Design',    2, 3, 3],
    // EE Sem3
    ['EE131',  'Power Electronics',            3, 3, 3],
    ['EE132',  'Control Systems',              3, 3, 3],
    ['EE133',  'Digital Signal Processing',    3, 3, 3],
];

foreach ($sem3Definitions as [$code, $name, $deptId, $sem, $credits]) {
    if (!DB::table('courses')->where('code', $code)->exists()) {
        DB::table('courses')->insert([
            'code'                     => $code,
            'name'                     => $name,
            'department_id'            => $deptId,
            'semester'                 => $sem,
            'prerequisite_course_code' => null,
            'prerequisite_mandatory'   => 0,
            'fee'                      => 0,
            'credits'                  => $credits,
            'type'                     => 'theory',
            'description'              => $name . ' — Semester 3',
            'status'                   => 'active',
            'created_at'               => $now,
        ]);
    }
}
echo "✓ Sem3 courses created\n";

// ─────────────────────────────────────────────────────────────────────────────
// 4. Add sections for Sem3 courses (Fall 2026 to match existing sections)
//    Each Sem3 course gets 1 section with 30 seats
// ─────────────────────────────────────────────────────────────────────────────
foreach (array_column($sem3Definitions, 0) as $code) {
    $courseId = DB::table('courses')->where('code', $code)->value('id');
    if ($courseId && !DB::table('course_sections')
            ->where('course_id', $courseId)->where('term', 'Fall')->where('year', 2026)->exists()) {
        DB::table('course_sections')->insert([
            'course_id'         => $courseId,
            'section_number'    => 1,
            'max_students'      => 30,
            'enrolled_students' => 0,
            'term'              => 'Fall',
            'year'              => 2026,
            'created_at'        => $now,
        ]);
    }
}
echo "✓ Sem3 sections created (Fall 2026)\n";

// ─────────────────────────────────────────────────────────────────────────────
// 5. Clear registrations & reset section enrolled counts
//    (Sem3 sections just created start at 0 — reset ensures consistency)
// ─────────────────────────────────────────────────────────────────────────────
DB::table('student_course_registrations')->truncate();
DB::table('course_sections')->update(['enrolled_students' => 0]);
DB::table('fee_payments')->truncate();
echo "✓ Cleared registrations, fees; reset enrolled counts\n";

// ─────────────────────────────────────────────────────────────────────────────
// 6. Helpers
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Find the Nth section (0-indexed) for a course code.
 */
function sectionFor(string $courseCode, int $offset = 0): ?int {
    $row = DB::table('course_sections')
        ->join('courses', 'courses.id', '=', 'course_sections.course_id')
        ->where('courses.code', $courseCode)
        ->select('course_sections.id')
        ->orderBy('course_sections.id')
        ->skip($offset)->first();
    return $row ? (int) $row->id : null;
}

function addReg(int $studentId, ?int $sectionId, string $status, ?string $result = null): void {
    global $now;
    if (!$sectionId) {
        echo "  ⚠ skipped: no section found (student $studentId)\n";
        return;
    }
    DB::table('student_course_registrations')->insert([
        'student_id'        => $studentId,
        'course_section_id' => $sectionId,
        'status'            => $status,
        'result'            => $result,
        'registered_at'     => $now,
    ]);
    if ($status === 'enrolled') {
        DB::table('course_sections')->where('id', $sectionId)->increment('enrolled_students');
    }
}

function addFee(int $studentId, int $semester, int $year = 2026,
                float $amount = 1200.00, string $status = 'paid'): void {
    global $now;
    DB::table('fee_payments')->insert([
        'student_id'  => $studentId,
        'type'        => 'regular',
        'course_id'   => null,
        'semester'    => $semester,
        'year'        => $year,
        'amount'      => $amount,
        'paid_amount' => $status === 'paid' ? $amount : 0.00,
        'status'      => $status,
        'paid_at'     => $status === 'paid' ? $now : null,
        'created_at'  => $now,
    ]);
}

function addSupplementalFee(int $studentId, int $courseId, string $status = 'paid'): void {
    global $now;
    DB::table('fee_payments')->insert([
        'student_id'  => $studentId,
        'type'        => 'supplemental',
        'course_id'   => $courseId,
        'semester'    => DB::table('courses')->where('id', $courseId)->value('semester') ?? 1,
        'year'        => 2026,
        'amount'      => 300.00,
        'paid_amount' => $status === 'paid' ? 300.00 : 0.00,
        'status'      => $status,
        'paid_at'     => $status === 'paid' ? $now : null,
        'created_at'  => $now,
    ]);
}

// Pre-resolve section IDs for all courses
$sec = [];
foreach (array_keys($courseSemesters) as $code) {
    $sec[$code]      = sectionFor($code, 0); // primary section
    $sec[$code.'_B'] = sectionFor($code, 1); // secondary section (retake scenario)
}

// ─────────────────────────────────────────────────────────────────────────────
// 7. Fees — regular fee at current semester for all students
// ─────────────────────────────────────────────────────────────────────────────
foreach (DB::table('students')->get() as $s) {
    addFee($s->id, $s->semester);
}
echo "✓ Regular fees paid for all students\n";

// ─────────────────────────────────────────────────────────────────────────────
// 8. REGISTRATIONS
// ─────────────────────────────────────────────────────────────────────────────

// ════════════════════════════════════════════════════════════════════════════
// CS DEPARTMENT
// ════════════════════════════════════════════════════════════════════════════

// ── Student 1: Aisha Khan — SCENARIO 2 (partial: CS122 blocked) ──
addReg(1, $sec['CS111'], 'completed', 'pass');
addReg(1, $sec['CS112'], 'completed', 'fail');  // → CS122 BLOCKED (mandatory)
addReg(1, $sec['CS113'], 'completed', 'pass');
addReg(1, $sec['CS121'], 'enrolled');            // CS111 ✓ — available
addReg(1, $sec['CS123'], 'enrolled');            // no mandatory prereq — available
// CS122 is NOT registered because it's blocked (CS112 not passed)
echo "✓ Aisha (1) [S2]: CS121 enrolled, CS122 blocked (CS112 fail), CS123 enrolled\n";

// ── Student 2: Rohit Verma — SCENARIO 4 (passed retake → eligible) ──
addReg(2, $sec['CS111'],           'completed', 'fail');              // original fail
addReg(2, $sec['CS111_B'] ?? $sec['CS111'], 'completed', 'pass');    // retake PASS ← S4
addReg(2, $sec['CS112'],    'completed', 'pass');
addReg(2, $sec['CS113'],    'completed', 'pass');
addReg(2, $sec['CS121'],    'enrolled');   // CS121 UNLOCKED (CS111 retake passed)
addReg(2, $sec['CS122'],    'enrolled');
addReg(2, $sec['CS123'],    'enrolled');
echo "✓ Rohit (2) [S4]: retake-passed CS111 → all Sem2 enrolled\n";

// ── Student 3: Meera Joshi — Sem1, all in progress ──
addReg(3, $sec['CS111'], 'enrolled');
addReg(3, $sec['CS112'], 'enrolled');
addReg(3, $sec['CS113'], 'enrolled');
echo "✓ Meera (3): Sem1 all enrolled\n";

// ── Student 4: Zoya Ahmed — SCENARIO 6 + 7 (all Sem2 done → auto-advance to Sem3) ──
// ALL Sem1 + Sem2 completed with pass.  She is seeded on semester=2.
// When she visits Register Courses the secondary check fires and advances her to Sem3.
// Sem3 fee is pre-paid so she can immediately see Sem3 courses.
addReg(4, $sec['CS111'], 'completed', 'pass');  // Sem1 history
addReg(4, $sec['CS112'], 'completed', 'pass');
addReg(4, $sec['CS113'], 'completed', 'pass');
addReg(4, $sec['CS121'], 'completed', 'pass');  // Sem2 — all passed
addReg(4, $sec['CS122'], 'completed', 'pass');  // attempting to re-register → "Already passed"
addReg(4, $sec['CS123'], 'completed', 'pass');  // all Sem2 done → triggers Sem3 advancement
echo "✓ Zoya (4) [S6+S7]: all Sem1+Sem2 passed → will auto-advance to Sem3\n";

// ── Student 5: Nikhil Saini — SCENARIO 3a (retake with paid supplemental) ──
addReg(5, $sec['CS111'], 'completed', 'pass');  // Sem1 history
addReg(5, $sec['CS112'], 'completed', 'pass');
addReg(5, $sec['CS113'], 'completed', 'pass');
addReg(5, $sec['CS121'], 'completed', 'fail');  // ← FAILED → retake needed
addReg(5, $sec['CS122'], 'enrolled');
addReg(5, $sec['CS123'], 'enrolled');
echo "✓ Nikhil (5) [S3a]: failed CS121, supplemental fee to follow\n";

// ── Student 6: Tanvi Gupta — SCENARIO 5 (multiple blocks: CS111+CS112 failed) ──
addReg(6, $sec['CS111'], 'completed', 'fail');  // → CS121 BLOCKED (mandatory)
addReg(6, $sec['CS112'], 'completed', 'fail');  // → CS122 BLOCKED (mandatory)
addReg(6, $sec['CS113'], 'completed', 'pass');
// On Sem2: CS121 blocked, CS122 blocked, only CS123 available
echo "✓ Tanvi (6) [S5]: 2 mandatory blocks (CS111+CS112 failed)\n";

// ════════════════════════════════════════════════════════════════════════════
// ICT DEPARTMENT
// ════════════════════════════════════════════════════════════════════════════

// ── Student 7: Karan Shah — SCENARIO 2 (failed ICT111, still Sem1) ──
addReg(7, $sec['ICT111'], 'completed', 'fail');  // → ICT121 BLOCKED when on Sem2
addReg(7, $sec['ICT112'], 'completed', 'pass');
addReg(7, $sec['ICT113'], 'enrolled');
echo "✓ Karan (7) [S2]: failed ICT111 → will block ICT121\n";

// ── Student 8: Riya Das — Sem1, all enrolled ──
addReg(8, $sec['ICT111'], 'enrolled');
addReg(8, $sec['ICT112'], 'enrolled');
addReg(8, $sec['ICT113'], 'enrolled');
echo "✓ Riya (8): Sem1 all enrolled\n";

// ── Student 9: Faizan Noor — SCENARIO 1 (full pass → Sem2 all available) ──
addReg(9, $sec['ICT111'], 'completed', 'pass');
addReg(9, $sec['ICT112'], 'completed', 'pass');
addReg(9, $sec['ICT113'], 'completed', 'pass');
addReg(9, $sec['ICT121'], 'enrolled');
addReg(9, $sec['ICT122'], 'enrolled');
addReg(9, $sec['ICT123'], 'enrolled');
echo "✓ Faizan (9) [S1]: all Sem1 passed → all Sem2 enrolled\n";

// ── Student 10: Sana Qureshi — Sem2, passed ICT121 ──
addReg(10, $sec['ICT111'], 'completed', 'pass');
addReg(10, $sec['ICT112'], 'completed', 'pass');
addReg(10, $sec['ICT113'], 'completed', 'pass');
addReg(10, $sec['ICT121'], 'completed', 'pass');
addReg(10, $sec['ICT122'], 'enrolled');
addReg(10, $sec['ICT123'], 'enrolled');
echo "✓ Sana (10): passed ICT121, enrolled ICT122+ICT123\n";

// ── Student 11: Dev Malhotra — SCENARIO 3b (retake, fee NOT paid) ──
addReg(11, $sec['ICT111'], 'completed', 'pass');
addReg(11, $sec['ICT112'], 'completed', 'pass');
addReg(11, $sec['ICT113'], 'completed', 'pass');
addReg(11, $sec['ICT121'], 'completed', 'fail');  // ← FAILED → retake, fee NOT paid
addReg(11, $sec['ICT122'], 'enrolled');
addReg(11, $sec['ICT123'], 'enrolled');
echo "✓ Dev (11) [S3b]: failed ICT121, no supplemental fee → button locked\n";

// ── Student 12: Priyal Jain — SCENARIO 7 (all Sem2 done → auto-advance to Sem3) ──
addReg(12, $sec['ICT111'], 'completed', 'pass');
addReg(12, $sec['ICT112'], 'completed', 'pass');
addReg(12, $sec['ICT113'], 'completed', 'pass');
addReg(12, $sec['ICT121'], 'completed', 'pass');  // ALL Sem2 completed+passed
addReg(12, $sec['ICT122'], 'completed', 'pass');
addReg(12, $sec['ICT123'], 'completed', 'pass');
echo "✓ Priyal (12) [S7]: all Sem1+Sem2 passed → will auto-advance to Sem3\n";

// ════════════════════════════════════════════════════════════════════════════
// EE DEPARTMENT
// ════════════════════════════════════════════════════════════════════════════

// ── Student 13: Aarav Kulkarni — SCENARIO 2 (failed EE111, still Sem1) ──
addReg(13, $sec['EE111'], 'completed', 'fail');  // → EE121 BLOCKED
addReg(13, $sec['EE112'], 'completed', 'pass');
addReg(13, $sec['EE113'], 'enrolled');
echo "✓ Aarav (13) [S2]: failed EE111 → will block EE121\n";

// ── Student 14: Ishita Paul — Sem2, all enrolled ──
addReg(14, $sec['EE111'], 'completed', 'pass');
addReg(14, $sec['EE112'], 'completed', 'pass');
addReg(14, $sec['EE113'], 'completed', 'pass');
addReg(14, $sec['EE121'], 'enrolled');
addReg(14, $sec['EE122'], 'enrolled');
addReg(14, $sec['EE123'], 'enrolled');
echo "✓ Ishita (14): all Sem2 enrolled\n";

// ── Student 15: Manav Batra — Sem1, all enrolled ──
addReg(15, $sec['EE111'], 'enrolled');
addReg(15, $sec['EE112'], 'enrolled');
addReg(15, $sec['EE113'], 'enrolled');
echo "✓ Manav (15): Sem1 all enrolled\n";

// ── Student 16: Naina Thomas — SCENARIO 7 (all Sem2 done → auto-advance to Sem3) ──
addReg(16, $sec['EE111'], 'completed', 'pass');
addReg(16, $sec['EE112'], 'completed', 'pass');
addReg(16, $sec['EE113'], 'completed', 'pass');
addReg(16, $sec['EE121'], 'completed', 'pass');  // ALL Sem2 completed+passed
addReg(16, $sec['EE122'], 'completed', 'pass');
addReg(16, $sec['EE123'], 'completed', 'pass');
echo "✓ Naina (16) [S7]: all Sem1+Sem2 passed → will auto-advance to Sem3\n";

// ── Student 17: Yash Patil — SCENARIO 3b (retake, fee NOT paid) ──
addReg(17, $sec['EE111'], 'completed', 'pass');
addReg(17, $sec['EE112'], 'completed', 'pass');
addReg(17, $sec['EE113'], 'completed', 'pass');
addReg(17, $sec['EE121'], 'completed', 'fail');  // ← FAILED EE121, no supplemental fee
addReg(17, $sec['EE122'], 'enrolled');
addReg(17, $sec['EE123'], 'enrolled');
echo "✓ Yash (17) [S3b]: failed EE121, no supplemental fee → button locked\n";

// ── Student 18: Simran Kaur — Sem2, all enrolled ──
addReg(18, $sec['EE111'], 'completed', 'pass');
addReg(18, $sec['EE112'], 'completed', 'pass');
addReg(18, $sec['EE113'], 'completed', 'pass');
addReg(18, $sec['EE121'], 'enrolled');
addReg(18, $sec['EE122'], 'enrolled');
addReg(18, $sec['EE123'], 'enrolled');
echo "✓ Simran (18): all Sem2 enrolled\n";

// ─────────────────────────────────────────────────────────────────────────────
// 9. semester_1_result
// ─────────────────────────────────────────────────────────────────────────────
$resultMap = [
    1  => 'Pass',   // Aisha
    2  => 'Pass',   // Rohit (retake pass counted)
    3  => 'N/A',    // Meera
    4  => 'Pass',   // Zoya
    5  => 'Pass',   // Nikhil
    6  => 'Fail',   // Tanvi (admin-advanced despite fails)
    7  => 'Fail',   // Karan
    8  => 'N/A',    // Riya
    9  => 'Pass',   // Faizan
    10 => 'Pass',   // Sana
    11 => 'Pass',   // Dev
    12 => 'Pass',   // Priyal
    13 => 'Fail',   // Aarav
    14 => 'Pass',   // Ishita
    15 => 'N/A',    // Manav
    16 => 'Pass',   // Naina
    17 => 'Pass',   // Yash
    18 => 'Pass',   // Simran
];
foreach ($resultMap as $sid => $result) {
    DB::table('students')->where('id', $sid)->update(['semester_1_result' => $result]);
}
echo "✓ semester_1_result set\n";

// ─────────────────────────────────────────────────────────────────────────────
// 10. Semester assignments
//     Sem2 students: semester = 2
//     Sem1 students who are mid-progress: keep at semester = 1
//     Sem3-advancing students: keep at semester = 2 (auto-advance fires on page visit)
// ─────────────────────────────────────────────────────────────────────────────
$semesterOverrides = [
    1 => 2,   // Aisha  → Sem2
    2 => 2,   // Rohit  → Sem2 (passed retake)
    4 => 2,   // Zoya   → Sem2 (will auto-advance to Sem3 on page visit)
    5 => 2,   // Nikhil → Sem2
    6 => 2,   // Tanvi  → Sem2 (admin decision)
    9 => 2,   // Faizan → Sem2
    10 => 2,  // Sana   → Sem2
    11 => 2,  // Dev    → Sem2
    12 => 2,  // Priyal → Sem2 (will auto-advance to Sem3)
    14 => 2,  // Ishita → Sem2
    16 => 2,  // Naina  → Sem2 (will auto-advance to Sem3)
    17 => 2,  // Yash   → Sem2
    18 => 2,  // Simran → Sem2
];
foreach ($semesterOverrides as $sid => $sem) {
    DB::table('students')->where('id', $sid)->update(['semester' => $sem]);
}
echo "✓ Semester assignments applied\n";

// ─────────────────────────────────────────────────────────────────────────────
// 11. Regular fees for Sem2
// ─────────────────────────────────────────────────────────────────────────────
$sem2Students = [1, 2, 4, 5, 6, 9, 10, 11, 12, 14, 16, 17, 18];
foreach ($sem2Students as $sid) {
    $has = DB::table('fee_payments')->where('student_id', $sid)
        ->where('type', 'regular')->where('semester', 2)->where('year', 2026)->exists();
    if (!$has) addFee($sid, 2, 2026, 1200.00, 'paid');
}
echo "✓ Sem2 fees paid\n";

// ─────────────────────────────────────────────────────────────────────────────
// 12. Sem3 fees pre-paid for auto-advancing students (Zoya=4, Priyal=12, Naina=16)
//     They are still on semester=2 in the DB; the fee is ready so when the
//     auto-advance fires on page visit they can immediately see Sem3 courses.
// ─────────────────────────────────────────────────────────────────────────────
foreach ([4, 12, 16] as $sid) {
    $has = DB::table('fee_payments')->where('student_id', $sid)
        ->where('type', 'regular')->where('semester', 3)->where('year', 2026)->exists();
    if (!$has) addFee($sid, 3, 2026, 1200.00, 'paid');
}
echo "✓ Sem3 fees pre-paid for Zoya (4), Priyal (12), Naina (16)\n";

// ─────────────────────────────────────────────────────────────────────────────
// 13. Supplemental fees
// ─────────────────────────────────────────────────────────────────────────────
$courseIds = [];
foreach (['CS121', 'CS112', 'ICT121', 'EE121'] as $code) {
    $courseIds[$code] = DB::table('courses')->where('code', $code)->value('id');
}

if ($courseIds['CS121'])  addSupplementalFee(5, $courseIds['CS121'],  'paid');    // Nikhil: S3a paid
if ($courseIds['CS112'])  addSupplementalFee(1, $courseIds['CS112'],  'pending'); // Aisha: CS112 backlog
if ($courseIds['ICT121']) addSupplementalFee(11, $courseIds['ICT121'], 'pending');// Dev: S3b locked
// Yash (17) / EE121 — no supplemental record at all (shows "Fee Required" in UI)

echo "✓ Supplemental fees set\n";

// ─────────────────────────────────────────────────────────────────────────────
// Summary
// ─────────────────────────────────────────────────────────────────────────────
$maxSem = DB::table('courses')->max('semester');
echo "\n✅ Seed complete!\n";
echo "  Total courses              : " . DB::table('courses')->count() . " (up to Sem$maxSem)\n";
echo "  Course sections            : " . DB::table('course_sections')->count() . "\n";
echo "  Registrations              : " . DB::table('student_course_registrations')->count() . "\n";
echo "    ↳ completed pass         : " . DB::table('student_course_registrations')->where('result','pass')->count() . "\n";
echo "    ↳ completed fail         : " . DB::table('student_course_registrations')->where('result','fail')->count() . "\n";
echo "    ↳ currently enrolled     : " . DB::table('student_course_registrations')->where('status','enrolled')->count() . "\n";
echo "  Fee payments               : " . DB::table('fee_payments')->count() . "\n";
echo "  Students on Sem1           : " . DB::table('students')->where('semester',1)->count() . "\n";
echo "  Students on Sem2           : " . DB::table('students')->where('semester',2)->count() . "\n";
echo "\n  SCENARIO TEST GUIDE:\n";
echo "  S1 Full Pass           → Login as Faizan (9, ICT)       — all Sem2 courses available, no blocks\n";
echo "  S2 Partial Eligibility → Login as Aisha (1, CS)         — CS122 blocked, CS121+CS123 available\n";
echo "  S2 Multi-Block         → Login as Tanvi (6, CS)         — CS121+CS122 both blocked, only CS123\n";
echo "  S3a Retake (paid fee)  → Login as Nikhil (5, CS)        — CS121 retake, green 'Paid' button\n";
echo "  S3b Retake (no fee)    → Login as Dev (11, ICT)         — ICT121 retake, red 'Fee Required' locked\n";
echo "  S4 Retake → Eligible   → Login as Rohit (2, CS)         — profile shows CS111 fail+retakepass\n";
echo "  S5 Multiple Blocks     → Login as Tanvi (6, CS)         — two mandatory blocks at once\n";
echo "  S6 Duplicate Attempt   → Login as Zoya (4, CS)          — try registering CS121/CS122 → 'Already passed'\n";
echo "  S7 Sem3 Advancement    → Login as Zoya/Priyal/Naina      — visit Register Courses → advances to Sem3\n";
