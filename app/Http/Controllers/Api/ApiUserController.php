<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Qs;
use App\Helpers\Usr;
use App\Http\Requests\UserBlockedState;
use App\Http\Requests\UserRequest;
use App\Http\Requests\UserStaffDataEditState;
use App\Repositories\LocationRepo;
use App\Repositories\MyClassRepo;
use App\Repositories\UserRepo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ApiUserController extends ApiBaseController
{
    protected $user, $loc, $my_class;

    public function __construct(UserRepo $user, LocationRepo $loc, MyClassRepo $my_class)
    {
        $this->user = $user;
        $this->loc = $loc;
        $this->my_class = $my_class;
    }

    /**
     * Display a list of non-student users.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        if (!Qs::userIsTeamSA()) {
            return $this->sendError('Forbidden.', ['error' => 'You do not have access to view users.'], 403);
        }

        $users = $this->user->getPTAUsers();
        $usersArray = [];

        foreach ($users as $u) {
            $data = $u->toArray();
            $data['id'] = Qs::hash($u->id);
            $usersArray[] = $data;
        }

        $userTypes = $this->user->getAllNotStudentType();
        $userTypesArray = [];
        foreach ($userTypes as $ut) {
            $t = $ut->toArray();
            $t['id'] = Qs::hash($ut->id);
            $userTypesArray[] = $t;
        }

        return $this->sendResponse([
            'users'      => $usersArray,
            'user_types' => $userTypesArray,
        ], 'Users list retrieved successfully.');
    }

    /**
     * Store a newly created user in storage.
     *
     * @param UserRequest $req
     * @return JsonResponse
     */
    public function store(UserRequest $req): JsonResponse
    {
        if (!Qs::userIsTeamSA()) {
            return $this->sendError('Forbidden.', ['error' => 'You do not have permissions to create users.'], 403);
        }

        $except = ['_token', '_method'];
        $user_type = $this->user->findType($req->user_type)->title;
        $data = $req->except(array_merge(Qs::getStaffRecord(), Qs::getParentRelativeRecord(), $except));

        $data['name'] = $name = ucwords(strtolower($req->name));
        $data['user_type'] = $user_type;
        $data['code'] = $code = strtoupper(Str::random(10));
        $data['photo'] = Usr::createAvatar($name, $code, $user_type);
        $data['dob'] = $req->dob;

        $user_is_staff = in_array($user_type, Qs::getStaff());
        $user_is_teamSA = in_array($user_type, Qs::getTeamSA());
        $user_is_parent = in_array($user_type, Qs::getParent());

        $emp_date = $req->emp_date ?: now();
        $staff_id = Qs::getAppCode() . '/STAFF/' . date('Y/m', strtotime($emp_date)) . '/' . mt_rand(1000, 9999);
        $data['username'] = $uname = $user_is_teamSA ? $req->username : $staff_id;

        $data['work'] = $user_is_parent ? $req->work : NULL;

        $pass = $req->password ?: $user_type;
        $data['password'] = Hash::make($pass);
        $data['message_media_heading_color'] = '#' . substr(md5($name), 0, 6);

        if ($req->hasFile('photo')) {
            $photo = $req->file('photo');
            $f = Qs::getFileMetaData($photo);
            $f['name'] = 'photo.' . $f['ext'];
            $f['path'] = $data['photo'] = Qs::getUploadPath($user_type) . $data['code'] . '/' . $f['name'];
            $photo->storeAs($f['path']);
        }

        if (!$uname && !$req->email) {
            return $this->sendError('Validation Error.', ['error' => 'Either Username or Email must be provided.'], 422);
        }

        $user = $this->user->create($data);

        /* CREATE STAFF RECORD */
        if ($user_is_staff) {
            $d2 = $req->only(Qs::getStaffRecord());
            $d2['user_id'] = $user->id;
            $d2['code'] = $staff_id;
            $d2['subjects_studied'] = json_encode(explode(",", $d2['subjects_studied'] ?? ''));

            $this->user->createStaffRecord($d2);
        }

        /* CREATE PARENT RELATIVE RECORD */
        if ($user_is_parent) {
            $d3 = $req->only(Qs::getParentRelativeRecord(['name2']));
            $d3['user_id'] = $user->id;
            $d3['name'] = ucwords(strtolower($req->name2 ?? ''));
            $d3['relation'] = $req->relation;

            $this->user->createParentRelativeRecord($d3);
        }

        $userData = $user->toArray();
        $userData['id'] = Qs::hash($user->id);

        return $this->sendResponse($userData, 'User created successfully.', 211);
    }

    /**
     * Display the specified user.
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

        $user = $this->user->find($realId);
        if (!$user) {
            return $this->sendError('User not found.', [], 404);
        }

        /* Prevent other Users from viewing Profile of others */
        if (auth()->id() != $realId && !Qs::userIsTeamSA() && !Qs::userIsMyChild(auth()->id(), $realId)) {
            return $this->sendError('Forbidden.', ['error' => 'You do not have access to view this profile.'], 403);
        }

        $userData = $user->toArray();
        $userData['id'] = Qs::hash($user->id);

        if (Qs::userIsTeamSATCL()) {
            $staff = $this->user->getStaffRecord(['user_id' => $realId])->first();
            $userData['staff_rec'] = $staff ? $staff->toArray() : null;
        }

        return $this->sendResponse($userData, 'User profile retrieved successfully.');
    }

    /**
     * Update the specified user in storage.
     *
     * @param UserRequest $req
     * @param string $id
     * @return JsonResponse
     */
    public function update(UserRequest $req, string $id): JsonResponse
    {
        $realId = (int) Qs::decodeHash($id);
        $user_type_id = (int) Qs::decodeHash($req->user_type_id);

        if (!Qs::userIsTeamSA()) {
            return $this->sendError('Forbidden.', ['error' => 'You do not have permissions to update users.'], 403);
        }

        if (Qs::headSA($realId) && !Qs::headSA(auth()->id())) {
            return $this->sendError('Forbidden.', ['error' => 'Action denied for Head SA.'], 403);
        } elseif (Qs::headSA(auth()->id()) && !Qs::userIsHead()) {
            return $this->sendError('Forbidden.', ['error' => 'Action denied.'], 403);
        }

        $user = $this->user->find($realId);
        if (!$user) {
            return $this->sendError('User not found.', [], 404);
        }

        $user_type = $this->user->findType($user_type_id)->title ?? $user->user_type;
        $user_was_staff = in_array($user->user_type, Qs::getStaff());
        $user_is_staff = in_array($user_type, Qs::getStaff());
        $user_is_parent = in_array($user_type, Qs::getParent());

        $except = array_merge(Qs::getStaffRecord(), Qs::getParentRelativeRecord(), ['_token', '_method', 'user_type_id']);
        $data = $req->except($except);

        $data['name'] = $name = ucwords(strtolower($req->name));
        $data['user_type'] = $user_type;
        $data['work'] = $user_is_parent ? $req->work : NULL;
        $data['message_media_heading_color'] = '#' . substr(md5($name), 0, 6);

        if ($req->hasFile('photo')) {
            $photo = $req->file('photo');
            $f = Qs::getFileMetaData($photo);
            $f['name'] = 'photo.' . $f['ext'];
            $f['path'] = $data['photo'] = Qs::getUploadPath($user_type) . $user->code . '/' . $f['name'];
            $photo->storeAs($f['path']);
        }

        $this->user->update($realId, $data);

        /* UPDATE OR CREATE NEW STAFF RECORD */
        if ($user_was_staff) {
            if ($user_is_staff) {
                $d2 = $req->only(Qs::getStaffRecord());
                $d2['code'] = $user->code;
                $d2['subjects_studied'] = json_encode(explode(",", $d2['subjects_studied'] ?? ''));
                $this->user->updateStaffRecord(['user_id' => $realId], $d2);
            } else {
                $this->user->deleteStaffRecord(['user_id' => $realId]);
            }
        } elseif ($user_is_staff) {
            $d2 = $req->only(Qs::getStaffRecord());
            $d2['code'] = $user->code;
            $d2['subjects_studied'] = json_encode(explode(",", $d2['subjects_studied'] ?? ''));
            $this->user->updateStaffRecord(['user_id' => $realId], $d2);
        }

        /* UPDATE PARENT RELATIVE RECORD */
        if ($user_is_parent) {
            $d3 = $req->only(Qs::getParentRelativeRecord(['user_id', 'name2']));
            $d3['name'] = ucwords(strtolower($req->name2 ?? ''));
            $d3['relation'] = $req->relation;
            $this->user->updateParentRelativeRec(['user_id' => $realId], $d3);
        }

        return $this->sendResponse([], 'User updated successfully.');
    }

    /**
     * Remove the specified user from storage.
     *
     * @param string $id
     * @return JsonResponse
     */
    public function destroy(string $id): JsonResponse
    {
        if (!Qs::userIsSuperAdmin()) {
            return $this->sendError('Forbidden.', ['error' => 'Only Super Admins can delete users.'], 403);
        }

        $realId = Qs::decodeHash($id);
        if (Qs::headSA($realId)) {
            return $this->sendError('Forbidden.', ['error' => 'Action denied for Head SA.'], 403);
        }

        $user = $this->user->find($realId);
        if (!$user) {
            return $this->sendError('User not found.', [], 404);
        }

        if ($user->user_type == 'teacher' && $this->userTeachesSubject($user)) {
            return $this->sendError('Conflict.', ['error' => 'This teacher is currently teaching a subject. Reassign their subjects before deleting.'], 409);
        }

        $path = Qs::getUploadPath($user->user_type) . $user->code;
        if (Storage::exists($path)) {
            Storage::deleteDirectory($path);
        }
        $this->user->delete($user->id);

        return $this->sendResponse([], 'User deleted successfully.');
    }

    /**
     * Check if a teacher teaches any subjects.
     */
    protected function userTeachesSubject($user): bool
    {
        $subjects = $this->my_class->findSubjectRecByTeacher($user->id);
        return $subjects->isNotEmpty();
    }

    /**
     * Update user blocked status.
     *
     * @param UserBlockedState $req
     * @return JsonResponse
     */
    public function updateBlockedState(UserBlockedState $req): JsonResponse
    {
        if (!Qs::userIsSuperAdmin()) {
            return $this->sendError('Forbidden.', ['error' => 'Only Super Admins can alter user status.'], 403);
        }

        $user_id = $req->id;
        $data = $req->only("blocked");
        $this->user->update($user_id, $data);

        return $this->sendResponse([], 'User blocked state updated successfully.');
    }

    /**
     * Reset user password.
     *
     * @param string $id
     * @return JsonResponse
     */
    public function resetPassword(string $id): JsonResponse
    {
        if (!Qs::userIsSuperAdmin()) {
            return $this->sendError('Forbidden.', ['error' => 'Only Super Admins can reset user passwords.'], 403);
        }

        $realId = Qs::decodeHash($id);
        if (Qs::headSA($realId) && !Qs::userIsHead()) {
            return $this->sendError('Forbidden.', ['error' => 'Action denied.'], 403);
        }

        $data['password'] = Hash::make('user');
        $data['password_updated_at'] = NULL;

        $this->user->update($realId, $data);

        return $this->sendResponse([], 'User password reset to default "user" successfully.');
    }
}
