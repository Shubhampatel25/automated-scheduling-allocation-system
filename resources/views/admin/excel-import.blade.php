@extends('layouts.dashboard')

@section('title', 'Excel Import')
@section('role-label', 'Admin Panel')
@section('page-title', 'Excel Import')

@section('sidebar-nav')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<style>
    .import-wrap { max-width: 740px; margin: 0 auto; }

    /* Mode toggle */
    .mode-tabs { display: flex; gap: 0; margin-bottom: 24px; border-radius: 8px; overflow: hidden; border: 1.5px solid #d1d5db; }
    .mode-tab {
        flex: 1; padding: 11px 0; text-align: center; font-weight: 600;
        font-size: .92rem; cursor: pointer; background: #f9fafb; color: #6b7280;
        border: none; transition: all .15s;
    }
    .mode-tab.active { background: #4f46e5; color: #fff; }
    .mode-tab:first-child { border-right: 1.5px solid #d1d5db; }

    /* Cards */
    .import-card {
        background: #fff; border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,.08); padding: 28px 32px;
    }
    .import-card h2 { margin: 0 0 5px 0; font-size: 1.2rem; color: #1a1a2e; }
    .import-card .subtitle { color: #666; font-size: .88rem; margin-bottom: 22px; line-height: 1.5; }

    .form-group { margin-bottom: 18px; }
    .form-group label { display: block; font-weight: 600; margin-bottom: 6px; color: #333; font-size: .9rem; }
    .form-control {
        width: 100%; padding: 10px 13px; border: 1.5px solid #ddd; border-radius: 7px;
        font-size: .93rem; transition: border-color .2s; box-sizing: border-box;
    }
    .form-control:focus { border-color: #4f46e5; outline: none; }
    .btn-import {
        background: #4f46e5; color: #fff; border: none; padding: 11px 28px;
        border-radius: 7px; font-size: .95rem; font-weight: 600; cursor: pointer; transition: background .2s;
    }
    .btn-import:hover { background: #4338ca; }

    /* Alerts */
    .alert { border-radius: 8px; padding: 13px 17px; margin-bottom: 18px; font-size: .9rem; line-height: 1.6; }
    .alert-success { background: #d1fae5; border-left: 4px solid #10b981; color: #065f46; }
    .alert-warning { background: #fef3c7; border-left: 4px solid #f59e0b; color: #92400e; }
    .alert-danger  { background: #fee2e2; border-left: 4px solid #ef4444; color: #991b1b; }
    .alert ul { margin: 7px 0 0 0; padding-left: 18px; }
    .alert ul li { margin-bottom: 2px; }

    /* Sheet name badges */
    .sheet-list { display: flex; flex-wrap: wrap; gap: 7px; margin: 14px 0 4px 0; }
    .sheet-badge {
        background: #ede9fe; color: #5b21b6; padding: 4px 11px;
        border-radius: 20px; font-size: .8rem; font-weight: 700; font-family: monospace;
    }

    /* Format reference */
    .format-section { margin-top: 28px; }
    .format-section h3 { font-size: 1rem; color: #1a1a2e; margin-bottom: 12px; border-bottom: 2px solid #e5e7eb; padding-bottom: 7px; }
    .format-tabs { display: flex; flex-wrap: wrap; gap: 7px; margin-bottom: 14px; }
    .format-tab-btn {
        padding: 5px 13px; border-radius: 20px; font-size: .8rem; font-weight: 600;
        border: 1.5px solid #d1d5db; background: #f9fafb; color: #374151; cursor: pointer; transition: all .15s;
    }
    .format-tab-btn.active, .format-tab-btn:hover { background: #4f46e5; color: #fff; border-color: #4f46e5; }
    .format-panel { display: none; }
    .format-panel.active { display: block; }

    .col-table { width: 100%; border-collapse: collapse; font-size: .86rem; }
    .col-table th { background: #f3f4f6; padding: 8px 11px; text-align: left; border: 1px solid #e5e7eb; color: #374151; font-weight: 700; }
    .col-table td { padding: 7px 11px; border: 1px solid #e5e7eb; color: #4b5563; vertical-align: top; }
    .col-table tr:nth-child(even) td { background: #f9fafb; }
    .badge-req { background: #fee2e2; color: #991b1b; padding: 2px 7px; border-radius: 10px; font-size: .76rem; font-weight: 700; }
    .badge-opt { background: #d1fae5; color: #065f46; padding: 2px 7px; border-radius: 10px; font-size: .76rem; font-weight: 700; }

    .note-box { background: #eff6ff; border-left: 4px solid #3b82f6; border-radius: 0 6px 6px 0; padding: 11px 15px; margin-top: 12px; font-size: .85rem; color: #1e40af; }
    .note-box strong { display: block; margin-bottom: 3px; }
    .note-box ol { margin: 5px 0 0 0; padding-left: 16px; }
    .note-box li { margin-bottom: 2px; }

    .hidden { display: none; }
</style>

{{-- ── Alerts ── --}}
@php $mode = session('import_mode', 'multi'); @endphp

@if(session('success'))
<div class="alert alert-success">&#10003; {{ session('success') }}
    @if(session('row_failures') && count(session('row_failures')))
    <ul>@foreach(session('row_failures') as $f)<li>{{ $f }}</li>@endforeach</ul>
    @endif
</div>
@endif

@if(session('warning'))
<div class="alert alert-warning">&#9888; {{ session('warning') }}
    @if(session('row_failures') && count(session('row_failures')))
    <ul>@foreach(session('row_failures') as $f)<li>{{ $f }}</li>@endforeach</ul>
    @endif
</div>
@endif

@if(session('error'))
<div class="alert alert-danger">&#10007; {{ session('error') }}</div>
@endif

@if($errors->has('excel'))
<div class="alert alert-danger"><strong>Validation errors:</strong>
    <ul>@foreach($errors->get('excel') as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

@if($errors->any() && !$errors->has('excel'))
<div class="alert alert-danger">
    <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<div class="import-wrap">

    {{-- ── Mode tabs ── --}}
    <div class="mode-tabs">
        <button type="button" class="mode-tab {{ $mode === 'multi' ? 'active' : '' }}" onclick="switchMode('multi')">
            &#128196; All-in-One (multiple sheets)
        </button>
        <button type="button" class="mode-tab {{ $mode === 'single' ? 'active' : '' }}" onclick="switchMode('single')">
            &#128203; Single type
        </button>
    </div>

    {{-- ── Multi-sheet form ── --}}
    <div id="form-multi" class="{{ $mode !== 'multi' ? 'hidden' : '' }}">
        <div class="import-card">
            <h2>All-in-One Import</h2>
            <p class="subtitle">
                Upload <strong>one Excel file</strong> with up to 12 sheets — each named exactly as shown below.
                Sheets you leave out are simply skipped. Import runs top-to-bottom automatically.
            </p>

            <div class="sheet-list">
                <span class="sheet-badge">departments</span>
                <span class="sheet-badge">rooms</span>
                <span class="sheet-badge">teachers</span>
                <span class="sheet-badge">hods</span>
                <span class="sheet-badge">students</span>
                <span class="sheet-badge">teacher_availability</span>
                <span class="sheet-badge">room_availability</span>
                <span class="sheet-badge">courses</span>
                <span class="sheet-badge">course_sections</span>
                <span class="sheet-badge">course_assignments</span>
                <span class="sheet-badge">student_course_registrations</span>
                <span class="sheet-badge">fee_payments</span>
            </div>
            <p style="font-size:.8rem;color:#6b7280;margin:0 0 4px 0;">Sheet names are <strong>case-sensitive</strong> and must be <strong>lowercase / snake_case</strong> exactly as shown.</p>
            <p style="font-size:.8rem;color:#6b7280;margin:0 0 20px 0;">Any other sheets (e.g. <code>README</code>, <code>users</code>) are silently skipped.</p>

            <form action="{{ route('admin.excel-import.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="import_mode" value="multi">
                <div class="form-group">
                    <label for="excel_file_multi">Excel file (.xlsx / .xls, max 5 MB) <span style="color:#ef4444">*</span></label>
                    <input type="file" name="excel_file" id="excel_file_multi" class="form-control" accept=".xlsx,.xls">
                </div>
                <button type="submit" class="btn-import">&#8593; Import All Sheets</button>
            </form>
        </div>
    </div>

    {{-- ── Single-type form ── --}}
    <div id="form-single" class="{{ $mode !== 'single' ? 'hidden' : '' }}">
        <div class="import-card">
            <h2>Single-Type Import</h2>
            <p class="subtitle">Upload a file with one sheet/one type of data. Also accepts .csv files.</p>

            <form action="{{ route('admin.excel-import.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="import_mode" value="single">

                <div class="form-group">
                    <label for="import_type">Import Type <span style="color:#ef4444">*</span></label>
                    <select name="import_type" id="import_type" class="form-control" onchange="syncFormatTab(this.value)">
                        <option value="" disabled {{ old('import_type', session('import_type', '')) === '' ? 'selected' : '' }}>-- Select --</option>
                        <option value="departments"          {{ old('import_type', session('import_type')) == 'departments'          ? 'selected' : '' }}>departments</option>
                        <option value="rooms"                {{ old('import_type', session('import_type')) == 'rooms'                ? 'selected' : '' }}>rooms</option>
                        <option value="teachers"             {{ old('import_type', session('import_type')) == 'teachers'             ? 'selected' : '' }}>teachers (creates user accounts)</option>
                        <option value="hods"                 {{ old('import_type', session('import_type')) == 'hods'                 ? 'selected' : '' }}>hods</option>
                        <option value="students"             {{ old('import_type', session('import_type')) == 'students'             ? 'selected' : '' }}>students (creates user accounts)</option>
                        <option value="teacher_availability" {{ old('import_type', session('import_type')) == 'teacher_availability' ? 'selected' : '' }}>teacher_availability</option>
                        <option value="room_availability"    {{ old('import_type', session('import_type')) == 'room_availability'    ? 'selected' : '' }}>room_availability</option>
                        <option value="courses"              {{ old('import_type', session('import_type')) == 'courses'              ? 'selected' : '' }}>courses</option>
                        <option value="course_sections"      {{ old('import_type', session('import_type')) == 'course_sections'      ? 'selected' : '' }}>course_sections</option>
                        <option value="course_assignments"         {{ old('import_type', session('import_type')) == 'course_assignments'         ? 'selected' : '' }}>course_assignments</option>
                        <option value="student_course_registrations" {{ old('import_type', session('import_type')) == 'student_course_registrations' ? 'selected' : '' }}>student_course_registrations</option>
                        <option value="fee_payments"              {{ old('import_type', session('import_type')) == 'fee_payments'              ? 'selected' : '' }}>fee_payments</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="excel_file_single">File (.xlsx / .xls / .csv, max 5 MB) <span style="color:#ef4444">*</span></label>
                    <input type="file" name="excel_file" id="excel_file_single" class="form-control" accept=".xlsx,.xls,.csv">
                </div>

                <button type="submit" class="btn-import">&#8593; Import</button>
            </form>
        </div>
    </div>

    {{-- ── Column Format Reference ── --}}
    <div class="format-section">
        <h3>&#128196; Column Format Reference</h3>
        <div class="format-tabs">
            <button class="format-tab-btn active" onclick="showPanel('departments',this)">Departments</button>
            <button class="format-tab-btn" onclick="showPanel('rooms',this)">Rooms</button>
            <button class="format-tab-btn" onclick="showPanel('teachers',this)">Teachers</button>
            <button class="format-tab-btn" onclick="showPanel('hods',this)">HODs</button>
            <button class="format-tab-btn" onclick="showPanel('students',this)">Students</button>
            <button class="format-tab-btn" onclick="showPanel('courses',this)">Courses</button>
            <button class="format-tab-btn" onclick="showPanel('course_sections',this)">Course Sections</button>
            <button class="format-tab-btn" onclick="showPanel('course_assignments',this)">Course Assignments</button>
            <button class="format-tab-btn" onclick="showPanel('teacher_availability',this)">Teacher Availability</button>
            <button class="format-tab-btn" onclick="showPanel('room_availability',this)">Room Availability</button>
            <button class="format-tab-btn" onclick="showPanel('student_course_registrations',this)">Student Registrations</button>
            <button class="format-tab-btn" onclick="showPanel('fee_payments',this)">Fee Payments</button>
        </div>

        {{-- Departments --}}
        <div id="panel-departments" class="format-panel active">
            <table class="col-table">
                <thead><tr><th>Column Header (Row 1)</th><th>Required</th><th>Notes</th></tr></thead>
                <tbody>
                    <tr><td>code</td><td><span class="badge-req">Required</span></td><td>Unique. e.g. <em>CS</em>. Auto-uppercased.</td></tr>
                    <tr><td>name</td><td><span class="badge-req">Required</span></td><td>Full department name.</td></tr>
                    <tr><td>description</td><td><span class="badge-opt">Optional</span></td><td>Short description.</td></tr>
                </tbody>
            </table>
        </div>

        {{-- Rooms --}}
        <div id="panel-rooms" class="format-panel">
            <table class="col-table">
                <thead><tr><th>Column Header</th><th>Required</th><th>Notes</th></tr></thead>
                <tbody>
                    <tr><td>room_number</td><td><span class="badge-req">Required</span></td><td>Unique. e.g. <em>A101</em>.</td></tr>
                    <tr><td>building</td><td><span class="badge-req">Required</span></td><td>Building name.</td></tr>
                    <tr><td>type</td><td><span class="badge-req">Required</span></td><td><code>classroom</code> | <code>lab</code> | <code>seminar_hall</code></td></tr>
                    <tr><td>capacity</td><td><span class="badge-req">Required</span></td><td>Integer. e.g. <em>40</em>.</td></tr>
                    <tr><td>equipment</td><td><span class="badge-opt">Optional</span></td><td>e.g. <em>Projector, Whiteboard</em>.</td></tr>
                    <tr><td>status</td><td><span class="badge-opt">Optional</span></td><td><code>available</code> | <code>unavailable</code> | <code>maintenance</code>. Default: <em>available</em>.</td></tr>
                </tbody>
            </table>
        </div>

        {{-- Teachers --}}
        <div id="panel-teachers" class="format-panel">
            <table class="col-table">
                <thead><tr><th>Column Header</th><th>Required</th><th>Notes</th></tr></thead>
                <tbody>
                    <tr><td>employee_id</td><td><span class="badge-req">Required</span></td><td>Unique. e.g. <em>EMP001</em>.</td></tr>
                    <tr><td>name</td><td><span class="badge-req">Required</span></td><td>Full name.</td></tr>
                    <tr><td>email</td><td><span class="badge-req">Required</span></td><td>Unique. Used for login (role = professor).</td></tr>
                    <tr><td>department_code</td><td><span class="badge-req">Required</span></td><td>Must match an existing department code.</td></tr>
                    <tr><td>password</td><td><span class="badge-opt">Optional</span></td><td>Plain text. Default: <em>Teacher@123</em>.</td></tr>
                </tbody>
            </table>
        </div>

        {{-- HODs --}}
        <div id="panel-hods" class="format-panel">
            <table class="col-table">
                <thead><tr><th>Column Header</th><th>Required</th><th>Notes</th></tr></thead>
                <tbody>
                    <tr><td>department_id</td><td><span class="badge-req">Required</span></td><td>Numeric ID of the department. Must exist in the <em>departments</em> table.</td></tr>
                    <tr><td>user_id</td><td><span class="badge-opt">Optional</span></td><td>Numeric ID of an existing user to link as HOD.</td></tr>
                    <tr><td>teacher_id</td><td><span class="badge-opt">Optional</span></td><td>Numeric ID of an existing teacher to link as HOD.</td></tr>
                    <tr><td>appointed_date</td><td><span class="badge-opt">Optional</span></td><td>Date of appointment. e.g. <em>2025-01-15</em>.</td></tr>
                    <tr><td>status</td><td><span class="badge-opt">Optional</span></td><td><code>active</code> | <code>inactive</code>. Default: <em>active</em>.</td></tr>
                </tbody>
            </table>
            <div class="note-box"><strong>Note:</strong> This sheet uses raw database IDs, not codes. Import <em>departments</em> and <em>teachers</em> first so the IDs exist.</div>
        </div>

        {{-- Students --}}
        <div id="panel-students" class="format-panel">
            <table class="col-table">
                <thead><tr><th>Column Header</th><th>Required</th><th>Notes</th></tr></thead>
                <tbody>
                    <tr><td>roll_no</td><td><span class="badge-req">Required</span></td><td>Unique. e.g. <em>S2024001</em>.</td></tr>
                    <tr><td>name</td><td><span class="badge-req">Required</span></td><td>Full name.</td></tr>
                    <tr><td>email</td><td><span class="badge-req">Required</span></td><td>Unique. Used for login (role = student).</td></tr>
                    <tr><td>department_code</td><td><span class="badge-req">Required</span></td><td>Must match an existing department code.</td></tr>
                    <tr><td>semester</td><td><span class="badge-req">Required</span></td><td>Integer 1–8.</td></tr>
                    <tr><td>password</td><td><span class="badge-opt">Optional</span></td><td>Plain text. Default: <em>Student@123</em>.</td></tr>
                </tbody>
            </table>
        </div>

        {{-- Courses --}}
        <div id="panel-courses" class="format-panel">
            <table class="col-table">
                <thead><tr><th>Column Header</th><th>Required</th><th>Notes</th></tr></thead>
                <tbody>
                    <tr><td>code</td><td><span class="badge-req">Required</span></td><td>Unique. e.g. <em>CS101</em>. Auto-uppercased.</td></tr>
                    <tr><td>name</td><td><span class="badge-req">Required</span></td><td>Course name.</td></tr>
                    <tr><td>department_code</td><td><span class="badge-req">Required</span></td><td>Must match an existing department code.</td></tr>
                    <tr><td>credits</td><td><span class="badge-req">Required</span></td><td>Integer 1–6.</td></tr>
                    <tr><td>type</td><td><span class="badge-req">Required</span></td><td><code>theory</code> | <code>lab</code> | <code>lecture_lab</code></td></tr>
                    <tr><td>description</td><td><span class="badge-opt">Optional</span></td><td>Short description.</td></tr>
                    <tr><td>semester</td><td><span class="badge-opt">Optional</span></td><td>Integer 1–8.</td></tr>
                    <tr><td>fee</td><td><span class="badge-opt">Optional</span></td><td>Decimal. e.g. <em>500.00</em>.</td></tr>
                    <tr><td>status</td><td><span class="badge-opt">Optional</span></td><td><code>active</code> | <code>inactive</code>. Default: <em>active</em>.</td></tr>
                </tbody>
            </table>
        </div>

        {{-- Course Sections --}}
        <div id="panel-course_sections" class="format-panel">
            <table class="col-table">
                <thead><tr><th>Column Header</th><th>Required</th><th>Notes</th></tr></thead>
                <tbody>
                    <tr><td>course_code</td><td><span class="badge-req">Required</span></td><td>Must match an existing course code.</td></tr>
                    <tr><td>section_number</td><td><span class="badge-req">Required</span></td><td>Integer. e.g. <em>1</em>.</td></tr>
                    <tr><td>term</td><td><span class="badge-req">Required</span></td><td><code>Winter</code> | <code>Summer</code> | <code>Fall</code></td></tr>
                    <tr><td>year</td><td><span class="badge-req">Required</span></td><td>4-digit year. e.g. <em>2025</em>.</td></tr>
                    <tr><td>max_students</td><td><span class="badge-opt">Optional</span></td><td>Integer. Default: <em>30</em>.</td></tr>
                </tbody>
            </table>
            <div class="note-box"><strong>Import Courses sheet before Course Sections.</strong></div>
        </div>

        {{-- Course Assignments --}}
        <div id="panel-course_assignments" class="format-panel">
            <table class="col-table">
                <thead><tr><th>Column Header</th><th>Required</th><th>Notes</th></tr></thead>
                <tbody>
                    <tr><td>course_code</td><td><span class="badge-req">Required</span></td><td>Must match an existing course code.</td></tr>
                    <tr><td>section_number</td><td><span class="badge-req">Required</span></td><td>The section must already exist.</td></tr>
                    <tr><td>term</td><td><span class="badge-req">Required</span></td><td>Must match the section's term.</td></tr>
                    <tr><td>year</td><td><span class="badge-req">Required</span></td><td>Must match the section's year.</td></tr>
                    <tr><td>teacher_employee_id</td><td><span class="badge-req">Required</span></td><td>Must match an existing teacher's employee ID.</td></tr>
                    <tr><td>component</td><td><span class="badge-req">Required</span></td><td><code>theory</code> | <code>lab</code></td></tr>
                </tbody>
            </table>
            <div class="note-box">
                <strong>Correct sheet order in your Excel file (all-in-one import):</strong>
                <ol>
                    <li>departments</li><li>rooms</li><li>teachers</li><li>hods</li>
                    <li>students</li><li>teacher_availability</li><li>room_availability</li>
                    <li>courses</li><li>course_sections</li><li>course_assignments</li>
                    <li>student_course_registrations</li><li>fee_payments</li>
                </ol>
                The all-in-one importer processes sheets in this order automatically. You can include any subset — missing sheets are skipped.
            </div>
        </div>

        {{-- Room Availability --}}
        <div id="panel-room_availability" class="format-panel">
            <table class="col-table">
                <thead><tr><th>Column Header</th><th>Required</th><th>Notes</th></tr></thead>
                <tbody>
                    <tr><td>room_id</td><td><span class="badge-req">Required</span></td><td>Numeric ID of an existing room. Must exist in the <em>rooms</em> table.</td></tr>
                    <tr><td>day_of_week</td><td><span class="badge-req">Required</span></td><td><code>Monday</code> | <code>Tuesday</code> | <code>Wednesday</code> | <code>Thursday</code> | <code>Friday</code></td></tr>
                    <tr><td>start_time</td><td><span class="badge-req">Required</span></td><td>24-hr format. e.g. <em>08:00</em>.</td></tr>
                    <tr><td>end_time</td><td><span class="badge-req">Required</span></td><td>24-hr format. e.g. <em>17:00</em>.</td></tr>
                    <tr><td>status</td><td><span class="badge-opt">Optional</span></td><td><code>available</code> | <code>unavailable</code>. Default: <em>available</em>.</td></tr>
                </tbody>
            </table>
            <div class="note-box"><strong>Note:</strong> Import <em>rooms</em> first so the room_id values exist.</div>
        </div>

        {{-- Student Course Registrations --}}
        <div id="panel-student_course_registrations" class="format-panel">
            <table class="col-table">
                <thead><tr><th>Column Header</th><th>Required</th><th>Notes</th></tr></thead>
                <tbody>
                    <tr><td>student_id <em>or</em> roll_no</td><td><span class="badge-req">Required</span></td><td>Provide one of these. <code>roll_no</code> is easier — e.g. <em>S2024001</em>.</td></tr>
                    <tr><td>course_section_id</td><td><span class="badge-req">Required</span></td><td>Numeric ID of the course section. Must exist in <em>course_sections</em>.</td></tr>
                    <tr><td>status</td><td><span class="badge-opt">Optional</span></td><td><code>enrolled</code> | <code>completed</code> | <code>dropped</code>. Default: <em>enrolled</em>.</td></tr>
                    <tr><td>result</td><td><span class="badge-opt">Optional</span></td><td><code>pass</code> | <code>fail</code>. Required when status is <code>completed</code>.</td></tr>
                    <tr><td>registered_at</td><td><span class="badge-opt">Optional</span></td><td>Date/datetime. e.g. <em>2025-01-15</em>. Defaults to now.</td></tr>
                </tbody>
            </table>
            <div class="note-box">
                <strong>Notes:</strong>
                <ul style="margin:5px 0 0 0;padding-left:16px;">
                    <li>Import <em>students</em> and <em>course_sections</em> before this sheet.</li>
                    <li>The student's department must match the course's department.</li>
                    <li>Duplicate registrations for the same student + section are skipped.</li>
                    <li>Enrolled student counts on sections are automatically updated after import.</li>
                </ul>
            </div>
        </div>

        {{-- Fee Payments --}}
        <div id="panel-fee_payments" class="format-panel">
            <table class="col-table">
                <thead><tr><th>Column Header</th><th>Required</th><th>Notes</th></tr></thead>
                <tbody>
                    <tr><td>student_id <em>or</em> roll_no</td><td><span class="badge-req">Required</span></td><td>Provide one of these. <code>roll_no</code> is easier — e.g. <em>S2024001</em>.</td></tr>
                    <tr><td>type</td><td><span class="badge-opt">Optional</span></td><td><code>regular</code> | <code>supplemental</code>. Default: <em>regular</em>.</td></tr>
                    <tr><td>course_id <em>or</em> course_code</td><td><span class="badge-opt">Optional</span></td><td>Required when type is <code>supplemental</code>. e.g. <em>CS101</em>.</td></tr>
                    <tr><td>semester</td><td><span class="badge-opt">Optional</span></td><td>Integer 1–8.</td></tr>
                    <tr><td>year</td><td><span class="badge-opt">Optional</span></td><td>4-digit year. e.g. <em>2025</em>.</td></tr>
                    <tr><td>amount</td><td><span class="badge-opt">Optional</span></td><td>Total fee amount. Decimal. e.g. <em>1500.00</em>.</td></tr>
                    <tr><td>paid_amount</td><td><span class="badge-opt">Optional</span></td><td>Amount paid so far. Decimal. Default: <em>0</em>.</td></tr>
                    <tr><td>status</td><td><span class="badge-opt">Optional</span></td><td><code>pending</code> | <code>paid</code> | <code>partial</code> | <code>overdue</code>. Auto-corrected based on amounts if omitted.</td></tr>
                    <tr><td>paid_at</td><td><span class="badge-opt">Optional</span></td><td>Date/datetime of payment. e.g. <em>2025-03-01</em>.</td></tr>
                </tbody>
            </table>
            <div class="note-box"><strong>Note:</strong> Import <em>students</em> first. For supplemental fees, import <em>courses</em> first too.</div>
        </div>

        {{-- Teacher Availability --}}
        <div id="panel-teacher_availability" class="format-panel">
            <table class="col-table">
                <thead><tr><th>Column Header</th><th>Required</th><th>Notes</th></tr></thead>
                <tbody>
                    <tr><td>teacher_employee_id</td><td><span class="badge-req">Required</span></td><td>Must match an existing teacher's employee ID.</td></tr>
                    <tr><td>term</td><td><span class="badge-req">Required</span></td><td><code>Winter</code> | <code>Summer</code> | <code>Fall</code></td></tr>
                    <tr><td>year</td><td><span class="badge-req">Required</span></td><td>4-digit year.</td></tr>
                    <tr><td>day_of_week</td><td><span class="badge-req">Required</span></td><td><code>Monday</code> | <code>Tuesday</code> | <code>Wednesday</code> | <code>Thursday</code> | <code>Friday</code></td></tr>
                    <tr><td>start_time</td><td><span class="badge-req">Required</span></td><td>24-hr format. e.g. <em>08:00</em>.</td></tr>
                    <tr><td>end_time</td><td><span class="badge-req">Required</span></td><td>24-hr format. e.g. <em>17:00</em>.</td></tr>
                    <tr><td>max_hours_per_week</td><td><span class="badge-opt">Optional</span></td><td>Integer 1–40.</td></tr>
                </tbody>
            </table>
            <div class="note-box">
                <strong>System time slots:</strong>
                08:00–09:30 &nbsp;|&nbsp; 09:40–11:10 &nbsp;|&nbsp; 11:20–12:50 &nbsp;|&nbsp; 13:50–15:20 &nbsp;|&nbsp; 15:30–17:00
            </div>
        </div>
    </div>
</div>

{{-- ── DB Tools ── --}}
<div style="max-width:740px;margin:28px auto 0 auto;background:#fff;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,.08);padding:22px 28px;">
    <h3 style="margin:0 0 6px 0;font-size:.98rem;color:#374151;">&#128203; Database Tools</h3>
    <p style="font-size:.83rem;color:#6b7280;margin:0 0 16px 0;">Use these if import says "all rows already exist" — check counts first, then clear if needed.</p>

    <div style="display:flex;gap:10px;flex-wrap:wrap;">
        {{-- Check counts --}}
        <a href="{{ route('admin.excel-import.counts') }}" target="_blank"
           style="background:#374151;color:#fff;padding:9px 16px;border-radius:7px;font-size:.85rem;font-weight:600;text-decoration:none;">
            &#128269; Check Table Counts
        </a>

        {{-- Clear all --}}
        <form action="{{ route('admin.excel-import.truncate') }}" method="POST" target="_blank"
              onsubmit="return confirm('This will DELETE ALL data from users, teachers, students, courses, rooms and all related tables. Are you sure?')">
            @csrf
            <input type="hidden" name="confirm" value="yes">
            <button type="submit"
                    style="background:#dc2626;color:#fff;border:none;padding:9px 16px;border-radius:7px;font-size:.85rem;font-weight:600;cursor:pointer;">
                &#128465; Clear All Tables &amp; Re-import
            </button>
        </form>
    </div>
</div>

{{-- ── Diagnostic tools ── --}}
<div style="max-width:740px;margin:28px auto 0 auto;background:#fff;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,.08);padding:22px 28px;">
    <h3 style="margin:0 0 6px 0;font-size:.98rem;color:#374151;">&#128269; Diagnose: Show Column Headers</h3>
    <p style="font-size:.83rem;color:#6b7280;margin:0 0 14px 0;">Upload your file to see the exact header names read from each sheet.</p>
    <form action="{{ route('admin.excel-import.diagnose') }}" method="POST" enctype="multipart/form-data" target="_blank">
        @csrf
        <div style="display:flex;gap:10px;align-items:center;">
            <input type="file" name="excel_file" accept=".xlsx,.xls" class="form-control" style="flex:1;">
            <button type="submit" style="background:#374151;color:#fff;border:none;padding:10px 18px;border-radius:7px;font-weight:600;cursor:pointer;white-space:nowrap;font-size:.88rem;">Show Headers</button>
        </div>
    </form>
</div>

<div style="max-width:740px;margin:16px auto 0 auto;background:#fff;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,.08);padding:22px 28px;">
    <h3 style="margin:0 0 6px 0;font-size:.98rem;color:#374151;">&#128202; Diagnose: Show Raw Parsed Rows</h3>
    <p style="font-size:.83rem;color:#6b7280;margin:0 0 14px 0;">Use this if import shows "0 imported, N skipped". It shows the first 3 data rows exactly as the importer receives them — with normalized (lowercased) keys and actual values. You can see if any key field (email, code, etc.) is empty.</p>
    <form action="{{ route('admin.excel-import.debug-rows') }}" method="POST" enctype="multipart/form-data" target="_blank">
        @csrf
        <div style="display:flex;gap:10px;align-items:center;">
            <input type="file" name="excel_file" accept=".xlsx,.xls" class="form-control" style="flex:1;">
            <button type="submit" style="background:#1d4ed8;color:#fff;border:none;padding:10px 18px;border-radius:7px;font-weight:600;cursor:pointer;white-space:nowrap;font-size:.88rem;">Show Raw Rows</button>
        </div>
    </form>
</div>

<script>
function switchMode(mode) {
    document.getElementById('form-multi').classList.toggle('hidden', mode !== 'multi');
    document.getElementById('form-single').classList.toggle('hidden', mode !== 'single');
    document.querySelectorAll('.mode-tab').forEach((t, i) => {
        t.classList.toggle('active', (i === 0 && mode === 'multi') || (i === 1 && mode === 'single'));
    });
}

function showPanel(id, btn) {
    document.querySelectorAll('.format-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.format-tab-btn').forEach(b => b.classList.remove('active'));
    const panel = document.getElementById('panel-' + id);
    if (panel) panel.classList.add('active');
    if (btn) btn.classList.add('active');
}

function syncFormatTab(val) {
    if (!val) return;
    document.querySelectorAll('.format-tab-btn').forEach(btn => {
        const match = btn.getAttribute('onclick').match(/'([^']+)'/);
        if (match && match[1] === val) showPanel(val, btn);
    });
}

document.addEventListener('DOMContentLoaded', function () {
    const sel = document.getElementById('import_type');
    if (sel && sel.value) syncFormatTab(sel.value);
});
</script>
@endsection
