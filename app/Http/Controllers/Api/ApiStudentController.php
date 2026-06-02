<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Qs;
use App\Helpers\Usr;
use App\Http\Requests\Student\StudentRecordCreate;
use App\Http\Requests\Student\StudentRecordUpdate;
use App\Repositories\LocationRepo;
use App\Repositories\MyClassRepo;
use App\Repositories\StudentRepo;
use App\Repositories\UserRepo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ApiStudentController extends ApiBaseController
{
    protected $loc, $my_class, $user, $student;

    public function __construct(LocationRepo $loc, MyClassRepo $my_class, UserRepo $user, StudentRepo $student)
    {
        $this->loc = $loc;
        $this->my_class = $my_class;
        $this->user = $user;
        $this->student = $student;
    }

    /**
     * Get students list, optionally filtered by class.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        if (!Qs::userIsTeamSATCL()) {
            return $this->sendError('Forbidden.', ['error' => 'You do not have access to view students.'], 403);
        }

        $classId = $request->query('class_id');
        if ($classId) {
            $realClassId = Qs::decodeHash($classId);
            $students = $this->student->findStudentsByClass($realClassId);
        } else {
            $students = $this->student->getRecord(['grad' => 0])->get();
        }

        $studentsArray = [];
        foreach ($students as $st) {
            $user = $st->user;
            if (!$user) continue;

            $data = $st->toArray();
            $data['id'] = Qs::hash($st->id);
            $data['user_id'] = Qs::hash($st->user_id);
            $data['my_class_id'] = Qs::hash($st->my_class_id);
            $data['section_id'] = Qs::hash($st->section_id);
            $data['user'] = $user->toArray();
            $data['user']['id'] = Qs::hash($user->id);

            $studentsArray[] = $data;
        }

        return $this->sendResponse($studentsArray, 'Students list retrieved successfully.');
    }

    /**
     * Show detailed profile of a student.
     *
     * @param string $id
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        $realId = Qs::decodeHash($id);
        if (!$realId) {
            return $this->sendError('Invalid ID format.', [], 400);
        }

        // Try getting active student record, fallback to graduated
        $sr = $this->student->getRecord(['id' => $realId])->first() ?? $this->student->getRecord2(['id' => $realId])->first();
        if (!$sr) {
            return $this->sendError('Student record not found.', [], 404);
        }

        /* Prevent Other Students/Parents from viewing Profile of others */
        if (auth()->id() != $sr->user_id && !Qs::userIsTeamSATCL() && !Qs::userIsMyChild($sr->user_id, auth()->id())) {
            if (Qs::userIsStudent2($sr->user->user_type) && !(Qs::userIsLibrarian() || Qs::userIsAccountant())) {
                return $this->sendError('Forbidden.', ['error' => 'You do not have access to view this student profile.'], 403);
            }
        }

        $data = $sr->toArray();
        $data['id'] = Qs::hash($sr->id);
        $data['user_id'] = Qs::hash($sr->user_id);
        $data['my_class_id'] = Qs::hash($sr->my_class_id);
        $data['section_id'] = Qs::hash($sr->section_id);

        if ($sr->user) {
            $data['user'] = $sr->user->toArray();
            $data['user']['id'] = Qs::hash($sr->user->id);
        }

        if ($sr->my_class) {
            $data['my_class'] = $sr->my_class->toArray();
            $data['my_class']['id'] = Qs::hash($sr->my_class->id);
        }

        if ($sr->section) {
            $data['section'] = $sr->section->toArray();
            $data['section']['id'] = Qs::hash($sr->section->id);
        }

        return $this->sendResponse($data, 'Student profile retrieved successfully.');
    }

    /**
     * Admit a new student.
     *
     * @param StudentRecordCreate $req
     * @return JsonResponse
     */
    public function store(StudentRecordCreate $req): JsonResponse
    {
        if (!Qs::userIsTeamSA()) {
            return $this->sendError('Forbidden.', ['error' => 'You do not have permissions to admit students.'], 403);
        }

        $data = $req->only(Qs::getUserRecord());
        $sr = $req->only(Qs::getStudentData());

        $ct = $this->my_class->findTypeByClass($req->my_class_id)->code;

        $data['user_type'] = $user_type = 'student';
        $data['name'] = $name = strtoupper($req->name);
        $data['code'] = $code = strtoupper(Str::random(10));
        $data['password'] = Hash::make('student');
        $data['photo'] = Usr::createAvatar($name, $code, $user_type);

        $adm_no = $req->adm_no;
        $data['username'] = strtoupper(Qs::getAppCode() . '/' . $ct . '/' . date('Y', strtotime($sr['date_admitted'])) . '/' . ($adm_no ?: mt_rand(1000, 99999)));

        if ($req->hasFile('photo')) {
            $photo = $req->file('photo');
            $f = Qs::getFileMetaData($photo);
            $f['name'] = 'photo.' . $f['ext'];
            $f['path'] = $data['photo'] = Qs::getUploadPath('student') . $data['code'] . '/' . $f['name'];
            $photo->storeAs($f['path']);
        }

        if ($req->hasFile('birth_certificate')) {
            $birth_certificate = $req->file('birth_certificate');
            $f = Qs::getFileMetaData($birth_certificate);
            $f['name'] = 'birth_certificate.' . $f['ext'];
            $f['path'] = $sr['birth_certificate'] = Qs::getUploadPath('student') . $data['code'] . '/' . $f['name'];
            $birth_certificate->storeAs($f['path']);
        }

        $user = $this->user->create($data);

        $sr['user_id'] = $user->id;
        $sr['adm_no'] = $user->username;
        $sr['session'] = Qs::getSetting('current_session');
        $sr['house_no'] = strtoupper($sr['house_no']);
        $sr['ps_name'] = Qs::userIsHead() ? $sr['ps_name'] : ucfirst($sr['ps_name']);
        $sr['ss_name'] = Qs::userIsHead() ? $sr['ss_name'] : ucfirst($sr['ss_name']);
        $sr['p_status'] = Qs::userIsHead() ? $sr['p_status'] : ucfirst($sr['p_status']);

        $studentRec = $this->student->createRecord($sr);

        $response = $studentRec->toArray();
        $response['id'] = Qs::hash($studentRec->id);
        $response['user_id'] = Qs::hash($studentRec->user_id);

        return $this->sendResponse($response, 'Student admitted successfully.', 211);
    }

    /**
     * Update student record.
     *
     * @param StudentRecordUpdate $req
     * @param string $id
     * @return JsonResponse
     */
    public function update(StudentRecordUpdate $req, string $id): JsonResponse
    {
        if (!Qs::userIsTeamSA()) {
            return $this->sendError('Forbidden.', ['error' => 'You do not have permissions to update student records.'], 403);
        }

        $realId = Qs::decodeHash($id);
        if (!$realId) {
            return $this->sendError('Invalid ID format.', [], 400);
        }

        $sr = $this->student->getRecord(['id' => $realId])->first() ?? $this->student->getRecord2(['id' => $realId])->first();
        if (!$sr) {
            return $this->sendError('Student record not found.', [], 404);
        }

        $d = $req->only(Qs::getUserRecord());
        $d['name'] = strtoupper($req->name);
        $d['username'] = $req->adm_no;

        if ($req->hasFile('photo')) {
            $photo = $req->file('photo');
            $f = Qs::getFileMetaData($photo);
            $f['name'] = 'photo.' . $f['ext'];
            $f['path'] = $d['photo'] = Qs::getUploadPath('student') . $sr->user->code . '/' . $f['name'];
            $photo->storeAs($f['path']);
        }

        $this->user->update($sr->user->id, $d);

        $srec = $req->only(Qs::getStudentData());

        if ($req->hasFile('birth_certificate')) {
            $birth_certificate = $req->file('birth_certificate');
            $f = Qs::getFileMetaData($birth_certificate);
            $f['name'] = 'birth_certificate.' . $f['ext'];
            $f['path'] = $srec['birth_certificate'] = Qs::getUploadPath('student') . $sr->user->code . '/' . $f['name'];
            $birth_certificate->storeAs($f['path']);
        }

        $srec['house_no'] = strtoupper($srec['house_no']);
        $srec['ps_name'] = ucfirst($srec['ps_name']);
        $srec['ss_name'] = ucfirst($srec['ss_name']);
        $srec['p_status'] = ucfirst($srec['p_status']);
        $srec['adm_no'] = $req->adm_no;

        $this->student->updateRecord($realId, $srec);

        return $this->sendResponse([], 'Student record updated successfully.');
    }

    /**
     * Soft-delete student.
     *
     * @param string $id
     * @return JsonResponse
     */
    public function destroy(string $id): JsonResponse
    {
        if (!Qs::userIsSuperAdmin()) {
            return $this->sendError('Forbidden.', ['error' => 'Only Super Admins can delete student records.'], 403);
        }

        $realId = Qs::decodeHash($id);
        if (!$realId) {
            return $this->sendError('Invalid ID format.', [], 400);
        }

        $sr = $this->student->getRecord(['user_id' => $realId])->first();
        if (!$sr) {
            return $this->sendError('Student not found.', [], 404);
        }

        $this->user->delete($sr->user->id);

        return $this->sendResponse([], 'Student deleted successfully.');
    }

    /**
     * Block all students in a class.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function blockClass(Request $request): JsonResponse
    {
        if (!Qs::userIsTeamSA()) {
            return $this->sendError('Forbidden.', ['error' => 'Unauthorized action.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'class_id' => 'required|string',
            'blocked'  => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors()->toArray(), 422);
        }

        $classId = Qs::decodeHash($request->class_id);
        $blocked = $request->blocked ? 1 : 0;

        $students_ids = $this->student->getRecord(['my_class_id' => $classId])->get()->pluck('user_id');
        $this->user->updateByIds($students_ids, ['blocked' => $blocked]);

        $statusStr = $blocked ? 'blocked' : 'unblocked';
        return $this->sendResponse([], "Selected class students have been {$statusStr} successfully.");
    }
}
