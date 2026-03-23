<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SchedulingImport implements WithMultipleSheets
{
    public UsersImport               $users;
    public DepartmentsImport         $departments;
    public RoomsImport               $rooms;
    public TeachersImport            $teachers;
    public HodsImport                $hods;
    public StudentsImport            $students;
    public TeacherAvailabilityImport $teacherAvailability;
    public RoomAvailabilityImport    $roomAvailability;
    public CoursesImport             $courses;
    public CourseSectionsImport      $courseSections;
    public CourseAssignmentsImport   $courseAssignments;

    private array $availableSheets;

    public function __construct(array $availableSheets = [])
    {
        $this->availableSheets     = $availableSheets;
        $this->users               = new UsersImport();
        $this->departments         = new DepartmentsImport();
        $this->rooms               = new RoomsImport();
        $this->teachers            = new TeachersImport();
        $this->hods                = new HodsImport();
        $this->students            = new StudentsImport();
        $this->teacherAvailability = new TeacherAvailabilityImport();
        $this->roomAvailability    = new RoomAvailabilityImport();
        $this->courses             = new CoursesImport();
        $this->courseSections      = new CourseSectionsImport();
        $this->courseAssignments   = new CourseAssignmentsImport();
    }

    /**
     * Order matters: users first so user_id FK is valid when teachers/students import.
     * departments before teachers/students/courses so department_id FK is valid.
     */
    public function sheets(): array
    {
        $all = [
            'users'                => $this->users,
            'departments'          => $this->departments,
            'rooms'                => $this->rooms,
            'teachers'             => $this->teachers,
            'hods'                 => $this->hods,
            'students'             => $this->students,
            'teacher_availability' => $this->teacherAvailability,
            'room_availability'    => $this->roomAvailability,
            'courses'              => $this->courses,
            'course_sections'      => $this->courseSections,
            'course_assignments'   => $this->courseAssignments,
        ];

        if (empty($this->availableSheets)) {
            return $all;
        }

        return array_intersect_key($all, array_flip($this->availableSheets));
    }
}
