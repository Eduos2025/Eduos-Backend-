<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Qs;
use App\Helpers\Mk;
use App\Repositories\AssessmentRepo;
use App\Repositories\ExamRepo;
use App\Repositories\MarkRepo;
use App\Repositories\MyClassRepo;
use App\Repositories\StudentRepo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApiMarkAssessmentController extends ApiBaseController
{
    protected $my_class, $exam, $student, $year, $mark, $assessment;

    public function __construct(MyClassRepo $my_class, ExamRepo $exam, StudentRepo $student, MarkRepo $mark, AssessmentRepo $assessment)
    {
        $this->exam = $exam;
        $this->mark = $mark;
        $this->student = $student;
        $this->my_class = $my_class;
        $this->assessment = $assessment;
        $this->year = Qs::getCurrentSession();
    }

    /**
     * Get list of all exams.
     *
     * @return JsonResponse
     */
    public function exams(): JsonResponse
    {
        $exams = $this->exam->all();
        $examsArray = [];

        foreach ($exams as $ex) {
            $data = $ex->toArray();
            $data['id'] = Qs::hash($ex->id);
            $data['class_type_id'] = Qs::hash($ex->class_type_id);
            $examsArray[] = $data;
        }

        return $this->sendResponse($examsArray, 'Exams list retrieved successfully.');
    }

    /**
     * Get gradebook marks for editing.
     *
     * @param string $exam_id
     * @param string $class_id
     * @param string $subject_id
     * @param string|null $section_id
     * @return JsonResponse
     */
    public function getMarks(string $exam_id, string $class_id, string $subject_id, string $section_id = null): JsonResponse
    {
        if (!Qs::userIsTeamSAT()) {
            return $this->sendError('Forbidden.', ['error' => 'You do not have access to view gradebook.'], 403);
        }

        $realExamId = Qs::decodeHash($exam_id);
        $realClassId = Qs::decodeHash($class_id);
        $realSubjectId = Qs::decodeHash($subject_id);
        $realSectionId = $section_id ? Qs::decodeHash($section_id) : null;

        $d = $realSectionId == null 
            ? ['exam_id' => $realExamId, 'my_class_id' => $realClassId, 'subject_id' => $realSubjectId, 'year' => $this->year] 
            : ['exam_id' => $realExamId, 'my_class_id' => $realClassId, 'section_id' => $realSectionId, 'subject_id' => $realSubjectId, 'year' => $this->year];

        $marks = $this->exam->getMark($d)->whereNotNull('user');

        if ($marks->isEmpty()) {
            return $this->sendError('No student marks record found.', [], 404);
        }

        $marksArray = [];
        foreach ($marks as $mk) {
            $m = $mk->toArray();
            $m['id'] = Qs::hash($mk->id);
            $m['student_id'] = Qs::hash($mk->student_id);
            $m['my_class_id'] = Qs::hash($mk->my_class_id);
            $m['section_id'] = Qs::hash($mk->section_id);
            $m['subject_id'] = Qs::hash($mk->subject_id);
            $m['exam_id'] = Qs::hash($mk->exam_id);
            if ($mk->user) {
                $m['user'] = $mk->user->toArray();
                $m['user']['id'] = Qs::hash($mk->user->id);
            }
            $marksArray[] = $m;
        }

        return $this->sendResponse($marksArray, 'Gradebook marks retrieved successfully.');
    }

    /**
     * Update gradebook marks.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateMarks(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'exam_id'    => 'required|string',
            'class_id'   => 'required|string',
            'subject_id' => 'required|string',
            'section_id' => 'nullable|string',
            'marks'      => 'required|array', // key is hashed mark ID, value is numeric mark [0-100]
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors()->toArray(), 422);
        }

        $exam_id = Qs::decodeHash($request->exam_id);
        $class_id = Qs::decodeHash($request->class_id);
        $subject_id = Qs::decodeHash($request->subject_id);
        $section_id = $request->section_id ? Qs::decodeHash($request->section_id) : null;

        $exam = $this->exam->find($exam_id);
        if (!$exam) {
            return $this->sendError('Exam not found.', [], 404);
        }

        if ($this->exam->isLocked($exam_id) || (Mk::isDisabled($exam->editable) && !Qs::userIsTeamSA())) {
            return $this->sendError('Forbidden.', ['error' => 'This exam is locked or not editable.'], 403);
        }

        $d = $section_id == null 
            ? ['exam_id' => $exam_id, 'my_class_id' => $class_id, 'subject_id' => $subject_id, 'year' => $this->year] 
            : ['exam_id' => $exam_id, 'my_class_id' => $class_id, 'section_id' => $section_id, 'subject_id' => $subject_id, 'year' => $this->year];

        $wh = $section_id == null 
            ? ['my_class_id' => $class_id, 'exam_id' => $exam_id, 'year' => $this->year]
            : ['my_class_id' => $class_id, 'section_id' => $section_id, 'exam_id' => $exam_id, 'year' => $this->year];

        $marks = $this->exam->getMark($d)->whereNotNull('user');
        if ($marks->isEmpty()) {
            return $this->sendError('No student records found.', [], 404);
        }

        $inputMarks = $request->marks;
        $d_collection = [];
        $all_st_ids = [];
        $section_ids = [];
        $class_type = $this->my_class->findTypeByClass($class_id);

        foreach ($marks as $mk) {
            $all_st_ids[] = $mk->student_id;
            $section_ids[$mk->student_id] = $mk->section_id;

            $hashedMarkId = Qs::hash($mk->id);
            $exm = isset($inputMarks[$hashedMarkId]) ? $inputMarks[$hashedMarkId] : $mk->exm;

            $markData = [];
            $markData['id'] = $mk->id;
            $markData['exm'] = $exm;
            $markData['exam_id'] = $exam_id;
            $markData["tex{$exam->term}"] = $total = $exm;

            if ($total > 100) {
                $markData["tex{$exam->term}"] = $markData['exm'] = null;
            }

            $grade = $this->mark->getGrade($total, $class_type->id);
            $markData['grade_id'] = $grade ? $grade->id : null;
            $markData['sub_pos'] = $this->mark->getSubPos($mk->student_id, $exam, $class_id, $subject_id, $this->year);

            $d_collection[] = $markData;
        }

        $this->exam->massUpdateMark($d_collection);

        // Term/Annual specific evaluations
        if (Mk::isTerminalExam($exam->category_id) || Mk::isAnnualExam($exam->category_id)) {
            $d2_collection = [];
            foreach ($marks as $mk) {
                $hashedMarkId = Qs::hash($mk->id);
                $exm = isset($inputMarks[$hashedMarkId]) ? $inputMarks[$hashedMarkId] : $mk->exm;

                $d2 = [];
                $d2['mark_id'] = $mk->id;
                $d2["tex{$exam->term}"] = null;
                $evaluated_val = $this->assessment->getValueOutOfQuantity($exm, $exam->tdt_denominator);
                $d2['exm'] = ($evaluated_val == 0) ? null : $evaluated_val;

                $d2_collection[] = $d2;
            }
            $this->assessment->massUpdateRecs($d2_collection, ['mark_id']);
        }

        $sub_ids = $this->mark->getSubjectIDs($wh);
        $subjects = $this->my_class->getSubjectsByIDs($sub_ids);
        $marks2 = $this->exam->getMark($wh);

        $subjects_considered = 0;
        if (isset($class_id)) {
            $class_type_id = $this->my_class->find($class_id)->class_type_id;
            $subjects_considered = $this->my_class->findType($class_type_id)->value('subjects_considered') ?: 0;
        }

        /* Exam Record Update */
        $d3_collection = [];
        foreach (array_unique($all_st_ids) as $st_id) {
            $d3 = [];
            $d3['student_id'] = $st_id;
            $d3['exam_id'] = $exam_id;
            $d3['total'] = $this->mark->getExamTotalTerm($exam, $st_id, $class_id, $this->year);
            $d3['ave'] = $ave = $this->mark->getLimitedExamAvgTerm($exam, $st_id, $class_id, $section_id ?? $section_ids[$st_id], $this->year, $subjects_considered);
            $d3['grade_id'] = $this->mark->getGrade($ave, $class_type->id)->id ?? null;
            $d3['class_ave'] = $this->mark->getClassAvg($exam, $class_id, $this->year) ?? null;
            $d3['pos'] = $this->exam->getStudentPos($st_id, $exam->id, $class_id, $this->year, $exam->exam_student_position_by_value, $section_id ?? $section_ids[$st_id]);

            $points = [];
            $grade_points = [];
            foreach ($subjects as $sub) {
                $subMark = $marks2->where('student_id', $st_id)->where('subject_id', $sub->id)->first();
                $points[] = $subMark && $subMark->grade ? $subMark->grade->point : null;
                $grade_points[] = $subMark && $subMark->subject && $subMark->subject->core == 1 && $subMark->grade ? $subMark->grade->credit : null;
            }

            $d3['points'] = $total_points = $this->mark->getExtractedSumOf($points, 0, $subjects_considered);
            $d3['gpa'] = $subjects_considered > 0 ? ($this->mark->getExtractedSumOf($grade_points, 0, $subjects_considered) / $subjects_considered) : 0;
            $d3['division_id'] = Mk::getDivision($total_points, $class_type_id)->id ?? null;

            $d3_collection[] = $d3;
        }

        $this->exam->massUpdateExamRec($d3_collection, ['student_id', 'exam_id']);

        /* Class Position updates */
        $d4_collection = [];
        foreach (array_unique($all_st_ids) as $st_id) {
            $d4 = [];
            $d4['student_id'] = $st_id;
            $d4['exam_id'] = $exam->id;
            $d4['my_class_id'] = $class_id;
            $d4['class_pos'] = $this->exam->getStudentPos($st_id, $exam->id, $class_id, $this->year, $exam->exam_student_position_by_value);

            $d4_collection[] = $d4;
        }

        $this->exam->massUpdateExamRec($d4_collection, ['student_id', 'exam_id', 'my_class_id']);

        return $this->sendResponse([], 'Marks updated and processed successfully.');
    }

    /**
     * Get Term Tabulation Sheet.
     *
     * @param string $exam_id
     * @param string $class_id
     * @param string $section_id (or 'all')
     * @param string $year
     * @return JsonResponse
     */
    public function tabulationSheet(string $exam_id, string $class_id, string $section_id, string $year): JsonResponse
    {
        $realExamId = Qs::decodeHash($exam_id);
        $realClassId = Qs::decodeHash($class_id);
        $realSectionId = $section_id === 'all' ? 'all' : Qs::decodeHash($section_id);

        $wh = $realSectionId === 'all' 
            ? ['my_class_id' => $realClassId, 'exam_id' => $realExamId, 'year' => $year]
            : ['my_class_id' => $realClassId, 'section_id' => $realSectionId, 'exam_id' => $realExamId, 'year' => $year];

        $sub_ids = $this->mark->getSubjectIDs($wh);
        $st_ids = $this->mark->getStudentIDs($wh);

        if (count($sub_ids) < 1 || count($st_ids) < 1) {
            return $this->sendError('No records found for tabulation sheet.', [], 404);
        }

        $exam_records = $this->exam->getRecord($wh);
        $subjects = $this->my_class->getSubjectsByIDs($sub_ids);
        $students = $realSectionId === 'all' 
            ? $this->student->getRecordByUserIDs2($st_ids)->get()->whereNotNull('user')->sortBy('user.name')
            : $this->student->getRecordByUserIDs($st_ids)->get()->whereNotNull('user')->sortBy('user.name');
        
        $marks = $this->exam->getMark($wh);

        $studentsArray = [];
        foreach ($students as $st) {
            $record = $exam_records->where('student_id', $st->user_id)->first();
            
            $stdMarks = [];
            foreach ($subjects as $sub) {
                $mk = $marks->where('student_id', $st->user_id)->where('subject_id', $sub->id)->first();
                $stdMarks[$sub->slug] = $mk ? [
                    'exm'   => $mk->exm,
                    'grade' => $mk->grade ? $mk->grade->name : null,
                ] : null;
            }

            $studentsArray[] = [
                'name'         => $st->user ? $st->user->name : '',
                'student_id'   => Qs::hash($st->id),
                'user_id'      => Qs::hash($st->user_id),
                'marks'        => $stdMarks,
                'total'        => $record ? $record->total : 0,
                'ave'          => $record ? $record->ave : 0,
                'class_pos'    => $record ? $record->class_pos : null,
                'pos'          => $record ? $record->pos : null,
            ];
        }

        $subjectsArray = [];
        foreach ($subjects as $sub) {
            $subjectsArray[] = [
                'id'   => Qs::hash($sub->id),
                'name' => $sub->name,
                'slug' => $sub->slug,
            ];
        }

        return $this->sendResponse([
            'subjects' => $subjectsArray,
            'students' => $studentsArray,
        ], 'Tabulation sheet retrieved successfully.');
    }
}
