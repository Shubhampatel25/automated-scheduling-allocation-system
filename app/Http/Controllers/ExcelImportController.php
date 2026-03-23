<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\SchedulingImport;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Imports\UsersImport;
use App\Imports\DepartmentsImport;
use App\Imports\RoomsImport;
use App\Imports\TeachersImport;
use App\Imports\HodsImport;
use App\Imports\StudentsImport;
use App\Imports\TeacherAvailabilityImport;
use App\Imports\RoomAvailabilityImport;
use App\Imports\CoursesImport;
use App\Imports\CourseSectionsImport;
use App\Imports\CourseAssignmentsImport;

class ExcelImportController extends Controller
{
    public function index()
    {
        return view('admin.excel-import');
    }

    /**
     * Show current row counts for all import-related tables.
     */
    public function counts()
    {
        $tables = [
            'users', 'departments', 'rooms', 'teachers', 'hods', 'students',
            'teacher_availability', 'room_availability',
            'courses', 'course_sections', 'course_assignments',
            'timetables', 'timetable_slots', 'conflicts',
        ];
        $counts = [];
        foreach ($tables as $table) {
            try {
                $counts[$table] = \Illuminate\Support\Facades\DB::table($table)->count();
            } catch (\Exception $e) {
                $counts[$table] = 'error';
            }
        }
        return response()->json($counts, 200, [], JSON_PRETTY_PRINT);
    }

    /**
     * Truncate all import-related tables (except timetables/conflicts — system-generated).
     */
    public function truncate(Request $request)
    {
        if ($request->input('confirm') !== 'yes') {
            return response()->json(['error' => 'Send confirm=yes to proceed.'], 400);
        }

        $tables = [
            // Delete in reverse FK order
            'course_assignments',
            'course_sections',
            'student_course_registrations',
            'timetable_slots',
            'conflicts',
            'timetables',
            'teacher_availability',
            'room_availability',
            'hods',
            'students',
            'teachers',
            'courses',
            'rooms',
            'departments',
            'users',
        ];

        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0');
        foreach ($tables as $table) {
            \Illuminate\Support\Facades\DB::table($table)->truncate();
        }
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1');

        return response()->json(['success' => 'All tables cleared. You can now import fresh data.']);
    }

    /**
     * Debug: upload a file and show raw row data from each sheet via PhpSpreadsheet.
     * Shows both the raw header values and the first 3 data rows with actual cell values.
     */
    public function debugRows(Request $request)
    {
        $request->validate(['excel_file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120']]);

        $file        = $request->file('excel_file');
        $spreadsheet = IOFactory::load($file->getPathname());
        $report      = [];

        foreach ($spreadsheet->getSheetNames() as $sheetName) {
            $sheet      = $spreadsheet->getSheetByName($sheetName);
            $highestRow = $sheet->getHighestRow();
            $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString(
                $sheet->getHighestColumn()
            );

            // Read header row (row 1) — raw values exactly as PhpSpreadsheet gives them
            $headers = [];
            for ($c = 1; $c <= $highestColIndex; $c++) {
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
                $val = $sheet->getCell($col . '1')->getValue();
                $headers[$col] = $val; // raw, no transformation
            }

            // Read first 3 data rows
            $sampleRows = [];
            for ($r = 2; $r <= min(4, $highestRow); $r++) {
                $rowData = [];
                foreach ($headers as $col => $header) {
                    $val = $sheet->getCell($col . $r)->getValue();
                    $key = (string)$header; // raw header as key
                    $rowData[$key] = $val;
                }
                $sampleRows[] = $rowData;
            }

            // Also show what normalize() would produce from the headers
            $normalizedHeaders = array_map(
                fn($h) => strtolower(trim((string)$h)),
                array_values($headers)
            );

            $report[$sheetName] = [
                'total_rows_incl_header' => $highestRow,
                'data_rows'              => $highestRow - 1,
                'raw_headers'            => array_values($headers),
                'normalized_headers'     => $normalizedHeaders,
                'sample_rows_raw'        => $sampleRows,
            ];
        }
        $spreadsheet->disconnectWorksheets();

        return response()->json($report, 200, [], JSON_PRETTY_PRINT);
    }

    /**
     * Diagnostic: upload a file and see the exact headers in each sheet.
     */
    public function diagnose(Request $request)
    {
        $request->validate(['excel_file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120']]);

        $file = $request->file('excel_file');
        $spreadsheet = IOFactory::load($file->getPathname());

        $report = [];
        foreach ($spreadsheet->getSheetNames() as $sheetName) {
            $sheet = $spreadsheet->getSheetByName($sheetName);
            $headers = [];
            $highestCol = $sheet->getHighestColumn();
            $colIndex = 1;
            while (true) {
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                $val = $sheet->getCell($col . '1')->getValue();
                if ($col === $highestCol) {
                    if ($val !== null && $val !== '') $headers[] = "'{$val}'";
                    break;
                }
                if ($val !== null && $val !== '') $headers[] = "'{$val}'";
                $colIndex++;
            }
            // Also grab row 2 to confirm it has data
            $row2 = [];
            $colIndex = 1;
            while (true) {
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                $val = $sheet->getCell($col . '2')->getValue();
                if ($col === $highestCol) {
                    $row2[] = $val;
                    break;
                }
                $row2[] = $val;
                $colIndex++;
            }
            $report[$sheetName] = [
                'headers' => $headers,
                'row2_sample' => array_filter($row2, fn($v) => $v !== null && $v !== ''),
            ];
        }
        $spreadsheet->disconnectWorksheets();

        return response()->json($report, 200, [], JSON_PRETTY_PRINT);
    }

    public function import(Request $request)
    {
        $request->validate([
            'import_mode' => ['required', 'in:multi,single'],
            'excel_file'  => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ], [
            'excel_file.required' => 'Please upload a file.',
            'excel_file.mimes'    => 'Only .xlsx, .xls, and .csv files are accepted.',
            'excel_file.max'      => 'File size must not exceed 5 MB.',
        ]);

        $file = $request->file('excel_file');

        if ($request->input('import_mode') === 'multi') {
            return $this->handleMultiSheet($file);
        }

        $request->validate([
            'import_type' => ['required', 'in:users,departments,rooms,teachers,hods,students,teacher_availability,room_availability,courses,course_sections,course_assignments'],
        ], [
            'import_type.required' => 'Please select what to import.',
        ]);

        return $this->handleSingleType($file, $request->input('import_type'));
    }

    // ──────────────────────────────────────────────
    // Multi-sheet: one Excel file, all sheets
    // ──────────────────────────────────────────────
    private function handleMultiSheet($file)
    {
        // Read which sheets are actually in the file before importing
        try {
            $spreadsheet     = IOFactory::load($file->getPathname());
            $availableSheets = $spreadsheet->getSheetNames();
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('import_mode', 'multi')
                ->with('error', 'Could not read the file: ' . $e->getMessage());
        }

        // All recognised sheet names (must match tab names exactly)
        $knownSheets = [
            'users', 'departments', 'rooms', 'teachers', 'hods', 'students',
            'teacher_availability', 'room_availability',
            'courses', 'course_sections', 'course_assignments',
        ];

        $matched = array_intersect($knownSheets, $availableSheets);

        if (empty($matched)) {
            $found = implode(', ', $availableSheets);
            $known = implode(', ', $knownSheets);
            return redirect()->back()
                ->with('import_mode', 'multi')
                ->with('warning', "No recognised sheets found in your file. Found: [{$found}]. Expected any of: [{$known}]. Sheet names are case-sensitive and must be lowercase/snake_case.");
        }

        $import = new SchedulingImport($availableSheets);

        try {
            Excel::import($import, $file);
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $msgs = [];
            foreach ($e->failures() as $f) {
                $msgs[] = "Row {$f->row()}: " . implode(', ', $f->errors());
            }
            return redirect()->back()
                ->with('import_mode', 'multi')
                ->withErrors(['excel' => $msgs])
                ->with('error', 'Import stopped due to validation errors.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('import_mode', 'multi')
                ->with('error', 'Import failed: ' . $e->getMessage());
        }

        // Collect per-sheet results
        $sheetsMap = [
            'users'                => ['label' => 'Users',                'importer' => $import->users],
            'departments'          => ['label' => 'Departments',          'importer' => $import->departments],
            'rooms'                => ['label' => 'Rooms',                'importer' => $import->rooms],
            'teachers'             => ['label' => 'Teachers',             'importer' => $import->teachers],
            'hods'                 => ['label' => 'HODs',                 'importer' => $import->hods],
            'students'             => ['label' => 'Students',             'importer' => $import->students],
            'teacher_availability' => ['label' => 'Teacher Availability', 'importer' => $import->teacherAvailability],
            'room_availability'    => ['label' => 'Room Availability',    'importer' => $import->roomAvailability],
            'courses'              => ['label' => 'Courses',              'importer' => $import->courses],
            'course_sections'      => ['label' => 'Course Sections',      'importer' => $import->courseSections],
            'course_assignments'   => ['label' => 'Course Assignments',   'importer' => $import->courseAssignments],
        ];

        $summary       = [];
        $allIssues     = [];
        $totalImported = 0;

        foreach ($matched as $sheetKey) {
            if (! isset($sheetsMap[$sheetKey])) continue;
            $label    = $sheetsMap[$sheetKey]['label'];
            $importer = $sheetsMap[$sheetKey]['importer'];
            $imp      = $importer->imported;
            $skip     = $importer->skipped;
            $totalImported += $imp;

            $line = "{$label}: {$imp} imported";
            if ($skip > 0) $line .= ", {$skip} skipped";
            $summary[] = $line;

            foreach (array_merge($importer->failures, $importer->errors) as $issue) {
                $allIssues[] = "[{$label}] {$issue}";
            }
        }

        $totalSkipped = array_sum(array_map(fn($s) => $sheetsMap[$s]['importer']->skipped ?? 0, array_keys(array_filter($sheetsMap, fn($s) => isset($s['importer'])))));

        $summaryMsg = implode(' | ', $summary);
        $flashKey   = $totalImported > 0 ? 'success' : 'warning';

        if ($totalImported === 0 && count($allIssues) === 0 && $totalSkipped === 0) {
            $summaryMsg = "No data rows found in any sheet. Check that your Excel file has data below the header row.";
        }

        return redirect()->back()
            ->with('import_mode', 'multi')
            ->with($flashKey, $summaryMsg)
            ->with('row_failures', $allIssues);
    }

    // ──────────────────────────────────────────────
    // Single-type: one sheet / one table
    // ──────────────────────────────────────────────
    private function handleSingleType($file, string $type)
    {
        $map = [
            'users'                => UsersImport::class,
            'departments'          => DepartmentsImport::class,
            'rooms'                => RoomsImport::class,
            'teachers'             => TeachersImport::class,
            'hods'                 => HodsImport::class,
            'students'             => StudentsImport::class,
            'teacher_availability' => TeacherAvailabilityImport::class,
            'room_availability'    => RoomAvailabilityImport::class,
            'courses'              => CoursesImport::class,
            'course_sections'      => CourseSectionsImport::class,
            'course_assignments'   => CourseAssignmentsImport::class,
        ];

        $importer = new $map[$type]();

        try {
            Excel::import($importer, $file);
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $msgs = [];
            foreach ($e->failures() as $f) {
                $msgs[] = "Row {$f->row()}: " . implode(', ', $f->errors());
            }
            return redirect()->back()
                ->with('import_mode', 'single')
                ->with('import_type', $type)
                ->withErrors(['excel' => $msgs])
                ->with('error', 'Import failed due to validation errors.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('import_mode', 'single')
                ->with('import_type', $type)
                ->with('error', 'Import failed: ' . $e->getMessage());
        }

        $allIssues = array_merge($importer->failures, $importer->errors);
        $imported  = $importer->imported;
        $skipped   = $importer->skipped;
        $label     = $this->typeLabel($type);

        if ($imported === 0 && count($allIssues) === 0 && $skipped === 0) {
            return redirect()->back()
                ->with('import_mode', 'single')
                ->with('import_type', $type)
                ->with('warning', 'The file appears to be empty or has no data rows.');
        }

        if ($imported === 0) {
            return redirect()->back()
                ->with('import_mode', 'single')
                ->with('import_type', $type)
                ->with('warning', "No {$label} imported. {$skipped} row(s) skipped.")
                ->with('row_failures', $allIssues);
        }

        $msg = "{$imported} {$label} imported successfully.";
        if ($skipped > 0) $msg .= " {$skipped} row(s) skipped.";

        return redirect()->back()
            ->with('import_mode', 'single')
            ->with('import_type', $type)
            ->with('success', $msg)
            ->with('row_failures', $allIssues);
    }

    private function typeLabel(string $type): string
    {
        return match ($type) {
            'departments'          => 'department(s)',
            'rooms'                => 'room(s)',
            'teachers'             => 'teacher(s)',
            'hods'                 => 'HOD(s)',
            'students'             => 'student(s)',
            'teacher_availability' => 'teacher availability record(s)',
            'room_availability'    => 'room availability record(s)',
            'courses'              => 'course(s)',
            'course_sections'      => 'course section(s)',
            'course_assignments'   => 'course assignment(s)',
            default                => 'record(s)',
        };
    }
}
