<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Qs;
use App\Repositories\MyClassRepo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiClassSectionSubjectController extends ApiBaseController
{
    protected $my_class;

    public function __construct(MyClassRepo $my_class)
    {
        $this->my_class = $my_class;
    }

    /**
     * Get all classes.
     *
     * @return JsonResponse
     */
    public function classes(): JsonResponse
    {
        $classes = $this->my_class->all();
        $classesArray = [];

        foreach ($classes as $mc) {
            $data = $mc->toArray();
            $data['id'] = Qs::hash($mc->id);
            $classesArray[] = $data;
        }

        return $this->sendResponse($classesArray, 'Classes retrieved successfully.');
    }

    /**
     * Get sections for a specific class.
     *
     * @param string $class_id
     * @return JsonResponse
     */
    public function sections(string $class_id): JsonResponse
    {
        $realClassId = Qs::decodeHash($class_id);
        if (!$realClassId) {
            return $this->sendError('Invalid Class ID format.', [], 400);
        }

        $sections = $this->my_class->getClassSections($realClassId);
        $sectionsArray = [];

        foreach ($sections as $sec) {
            $data = $sec->toArray();
            $data['id'] = Qs::hash($sec->id);
            $data['my_class_id'] = Qs::hash($sec->my_class_id);
            $sectionsArray[] = $data;
        }

        return $this->sendResponse($sectionsArray, 'Class sections retrieved successfully.');
    }

    /**
     * Get subjects for a specific class.
     *
     * @param string $class_id
     * @return JsonResponse
     */
    public function subjects(string $class_id): JsonResponse
    {
        $realClassId = Qs::decodeHash($class_id);
        if (!$realClassId) {
            return $this->sendError('Invalid Class ID format.', [], 400);
        }

        $subjects = $this->my_class->getClassSubjects($realClassId);
        $subjectsArray = [];

        foreach ($subjects as $sub) {
            $data = $sub->toArray();
            $data['id'] = Qs::hash($sub->id);
            $data['my_class_id'] = Qs::hash($sub->my_class_id);
            $subjectsArray[] = $data;
        }

        return $this->sendResponse($subjectsArray, 'Class subjects retrieved successfully.');
    }
}
