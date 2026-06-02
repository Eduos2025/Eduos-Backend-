<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Qs;
use App\Helpers\Usr;
use App\Repositories\ExamRepo;
use App\Repositories\LocationRepo;
use App\Repositories\MyClassRepo;
use App\Repositories\StudentRepo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiUtilityController extends ApiBaseController
{
    protected $loc, $my_class, $student, $exam;

    public function __construct(LocationRepo $loc, MyClassRepo $my_class, StudentRepo $student, ExamRepo $exam)
    {
        $this->loc = $loc;
        $this->student = $student;
        $this->my_class = $my_class;
        $this->exam = $exam;
    }

    /**
     * Get nationalities.
     *
     * @return JsonResponse
     */
    public function nationalities(): JsonResponse
    {
        $nationals = $this->loc->getAllNationals();
        $nationalsArray = $nationals->map(fn($q) => ['id' => $q->id, 'name' => $q->name])->all();

        return $this->sendResponse($nationalsArray, 'Nationalities retrieved successfully.');
    }

    /**
     * Get states of a nationality.
     *
     * @param int $nal_id
     * @return JsonResponse
     */
    public function states(int $nal_id): JsonResponse
    {
        $states = $this->loc->getStateByNationalityID($nal_id);
        $statesArray = $states->map(fn($q) => ['id' => $q->id, 'name' => $q->name])->all();

        return $this->sendResponse($statesArray, 'States retrieved successfully.');
    }

    /**
     * Get LGAs of a state.
     *
     * @param int $state_id
     * @return JsonResponse
     */
    public function lgas(int $state_id): JsonResponse
    {
        $lgas = $this->loc->getLGAs($state_id);
        $lgasArray = $lgas->map(fn($q) => ['id' => $q->id, 'name' => $q->name])->all();

        return $this->sendResponse($lgasArray, 'LGAs retrieved successfully.');
    }

    /**
     * Get students in a class.
     *
     * @param string $class_id
     * @return JsonResponse
     */
    public function classStudents(string $class_id): JsonResponse
    {
        $realClassId = Qs::decodeHash($class_id);
        if (!$realClassId) {
            return $this->sendError('Invalid Class ID format.', [], 400);
        }

        $students = $this->student->getRecord(['my_class_id' => $realClassId])->get()->whereNotNull('user');
        $studentsArray = [];

        foreach ($students as $st) {
            if ($st->user) {
                $studentsArray[] = [
                    'id'   => Qs::hash($st->user->id),
                    'name' => $st->user->name,
                ];
            }
        }

        return $this->sendResponse($studentsArray, 'Class students retrieved successfully.');
    }

    /**
     * Get subjects in a class (filtered for teachers if applicable).
     *
     * @param string $class_id
     * @return JsonResponse
     */
    public function classSubjects(string $class_id): JsonResponse
    {
        $realClassId = Qs::decodeHash($class_id);
        if (!$realClassId) {
            return $this->sendError('Invalid Class ID format.', [], 400);
        }

        $subjects = Qs::userIsTeacher()
            ? $this->my_class->findSubjectByRecord(auth()->id(), $realClassId)
            : $this->my_class->findSubjectByClass($realClassId);

        $subjectsArray = [];
        foreach ($subjects as $sub) {
            $id = $sub->id ?? ($sub->subject ? $sub->subject->id : null);
            $name = $sub->name ?? ($sub->subject ? $sub->subject->name : null);
            
            if ($id && $name) {
                $subjectsArray[] = [
                    'id'   => Qs::hash($id),
                    'name' => $name,
                ];
            }
        }

        return $this->sendResponse($subjectsArray, 'Class subjects retrieved successfully.');
    }

    /**
     * Get exams of a specific session/year.
     *
     * @param string $year
     * @return JsonResponse
     */
    public function yearExams(string $year): JsonResponse
    {
        $exams = $this->exam->getExam(['year' => $year], false);
        $examsArray = [];

        foreach ($exams as $ex) {
            $examsArray[] = [
                'id'   => Qs::hash($ex->id),
                'name' => $ex->name,
            ];
        }

        return $this->sendResponse($examsArray, 'Session exams retrieved successfully.');
    }
}
