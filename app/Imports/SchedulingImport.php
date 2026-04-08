<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SchedulingImport implements WithMultipleSheets
{
    public UsersImport                       $users;
    public DepartmentsImport                 $departments;
    public RoomsImport                       $rooms;
    public TeachersImport                    $teachers;
    public HodsImport                        $hods;
    public StudentsImport                    $students;
    public TeacherAvailabilityImport         $teacherAvailability;
    public RoomAvailabilityImport            $roomAvailability;
    public CoursesImport                     $courses;
    public CourseSectionsImport              $courseSections;
    public CourseAssignmentsImport           $courseAssignments;
    // Hybrid-logic support: import historical registrations and fee records
    public StudentCourseRegistrationsImport  $studentCourseRegistrations;
    public FeePaymentsImport                 $feePayments;

    private array $availableSheets;

    public function __construct(array $availableSheets = [])
    {
        $this->availableSheets            = $availableSheets;
        $this->users                      = new UsersImport();
        $this->departments                = new DepartmentsImport();
        $this->rooms                      = new RoomsImport();
        $this->teachers                   = new TeachersImport();
        $this->hods                       = new HodsImport();
        $this->students                   = new StudentsImport();
        $this->teacherAvailability        = new TeacherAvailabilityImport();
        $this->roomAvailability           = new RoomAvailabilityImport();
        $this->courses                    = new CoursesImport();
        $this->courseSections             = new CourseSectionsImport();
        $this->courseAssignments          = new CourseAssignmentsImport();
        $this->studentCourseRegistrations = new StudentCourseRegistrationsImport();
        $this->feePayments                = new FeePaymentsImport();
    }

    /**
     * Order matters: users first so user_id FK is valid when teachers/students import.
     * departments before teachers/students/courses so department_id FK is valid.
     * student_course_registrations and fee_payments come last — they depend on
     * students, course_sections, and courses all being present first.
     */
    public function sheets(): array
    {
        $all = [
            'users'                        => $this->users,
            'departments'                  => $this->departments,
            'rooms'                        => $this->rooms,
            'teachers'                     => $this->teachers,
            'hods'                         => $this->hods,
            'students'                     => $this->students,
            'teacher_availability'         => $this->teacherAvailability,
            'room_availability'            => $this->roomAvailability,
            'courses'                      => $this->courses,
            'course_sections'              => $this->courseSections,
            'course_assignments'           => $this->courseAssignments,
            // These two sheets power the HYBRID registration logic:
            // registrations provide passed/failed history; fees validate eligibility.
            'student_course_registrations' => $this->studentCourseRegistrations,
            'fee_payments'                 => $this->feePayments,
        ];

        if (empty($this->availableSheets)) {
            return $all;
        }

        return array_intersect_key($all, array_flip($this->availableSheets));
    }
}
