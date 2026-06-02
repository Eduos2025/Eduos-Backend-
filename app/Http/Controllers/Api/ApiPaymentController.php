<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Qs;
use App\Helpers\Pay;
use App\Notifications\StudentPaymentPaid;
use App\Repositories\MyClassRepo;
use App\Repositories\PaymentRepo;
use App\Repositories\StudentRepo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApiPaymentController extends ApiBaseController
{
    protected $my_class, $pay, $student, $year;

    public function __construct(MyClassRepo $my_class, PaymentRepo $pay, StudentRepo $student)
    {
        $this->my_class = $my_class;
        $this->pay = $pay;
        $this->year = Qs::getCurrentSession();
        $this->student = $student;
    }

    /**
     * Get payment history for a student.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if ($user->user_type === 'student') {
            $studentId = $user->id;
        } elseif ($user->user_type === 'parent') {
            // Get first child or validate child_id parameter
            $childIdHashed = $request->query('student_id');
            if ($childIdHashed) {
                $studentId = Qs::decodeHash($childIdHashed);
                if (!Qs::userIsMyChild($studentId, $user->id)) {
                    return $this->sendError('Forbidden.', ['error' => 'This student is not registered under your profile.'], 403);
                }
            } else {
                $children = Qs::findMyChildren($user->id);
                if ($children->isEmpty()) {
                    return $this->sendResponse([], 'No registered children found.');
                }
                $studentId = $children->first()->user_id;
            }
        } else {
            // accountant or SA can request by student_id
            $studentIdHashed = $request->query('student_id');
            if (!$studentIdHashed) {
                return $this->sendError('Validation Error.', ['student_id' => ['The student_id parameter is required.']], 422);
            }
            $studentId = Qs::decodeHash($studentIdHashed);
            if (!Qs::userIsTeamAccount()) {
                return $this->sendError('Forbidden.', ['error' => 'Access Denied.'], 403);
            }
        }

        $records = $this->pay->getAllMyPR($studentId)->get()->whereNotNull('payment');
        
        $recordsArray = [];
        foreach ($records as $rec) {
            $r = $rec->toArray();
            $r['id'] = Qs::hash($rec->id);
            $r['student_id'] = Qs::hash($rec->student_id);
            $r['payment_id'] = Qs::hash($rec->payment_id);
            if ($rec->payment) {
                $r['payment'] = $rec->payment->toArray();
                $r['payment']['id'] = Qs::hash($rec->payment->id);
            }
            $recordsArray[] = $r;
        }

        return $this->sendResponse($recordsArray, 'Payment records retrieved successfully.');
    }

    /**
     * Get details of a student invoice.
     *
     * @param string $student_id
     * @param string|null $year
     * @return JsonResponse
     */
    public function invoice(string $student_id, string $year = null): JsonResponse
    {
        $realStudentId = Qs::decodeHash($student_id);
        if (!$realStudentId) {
            return $this->sendError('Invalid Student ID format.', [], 400);
        }

        $user = auth()->user();
        if ($user->user_type === 'student' && $user->id != $realStudentId) {
            return $this->sendError('Forbidden.', ['error' => 'You can only view your own invoices.'], 403);
        }
        if ($user->user_type === 'parent' && !Qs::userIsMyChild($realStudentId, $user->id)) {
            return $this->sendError('Forbidden.', ['error' => 'This student is not registered under your profile.'], 403);
        }

        $inv = $year ? $this->pay->getAllMyPR($realStudentId, $year) : $this->pay->getAllMyPR($realStudentId);
        $pr = $inv->get()->whereNotNull('payment');

        $uncleared = [];
        $cleared = [];

        foreach ($pr->where('paid', 0) as $rec) {
            $item = $rec->toArray();
            $item['id'] = Qs::hash($rec->id);
            $item['student_id'] = Qs::hash($rec->student_id);
            $item['payment_id'] = Qs::hash($rec->payment_id);
            if ($rec->payment) {
                $item['payment'] = $rec->payment->toArray();
                $item['payment']['id'] = Qs::hash($rec->payment->id);
            }
            $uncleared[] = $item;
        }

        foreach ($pr->where('paid', 1) as $rec) {
            $item = $rec->toArray();
            $item['id'] = Qs::hash($rec->id);
            $item['student_id'] = Qs::hash($rec->student_id);
            $item['payment_id'] = Qs::hash($rec->payment_id);
            if ($rec->payment) {
                $item['payment'] = $rec->payment->toArray();
                $item['payment']['id'] = Qs::hash($rec->payment->id);
            }
            $cleared[] = $item;
        }

        $studentRec = $this->student->getRecord(['user_id' => $realStudentId])->first();

        return $this->sendResponse([
            'student' => $studentRec ? [
                'name'   => $studentRec->user ? $studentRec->user->name : '',
                'adm_no' => $studentRec->adm_no,
                'class'  => $studentRec->my_class ? $studentRec->my_class->name : '',
            ] : null,
            'uncleared' => $uncleared,
            'cleared'   => $cleared,
        ], 'Invoice data retrieved successfully.');
    }

    /**
     * Process fee payment.
     *
     * @param Request $request
     * @param string $pr_id
     * @return JsonResponse
     */
    public function payNow(Request $request, string $pr_id): JsonResponse
    {
        $realPrId = Qs::decodeHash($pr_id);
        if (!$realPrId) {
            return $this->sendError('Invalid Payment Record ID format.', [], 400);
        }

        $validator = Validator::make($request->all(), [
            'amt_paid' => 'required|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors()->toArray(), 422);
        }

        $pr = $this->pay->findRecord($realPrId);
        if (!$pr) {
            return $this->sendError('Payment record not found.', [], 404);
        }

        $payment = $this->pay->find($pr->payment_id);
        
        $d['amt_paid'] = $amt_p = $pr->amt_paid + $request->amt_paid;
        $d['balance'] = $bal = $payment->amount - $amt_p;
        $d['paid'] = $bal < 1 ? 1 : 0;

        $this->pay->updateRecord($realPrId, $d);

        $d2['amt_paid'] = $request->amt_paid;
        $d2['balance'] = $bal;
        $d2['pr_id'] = $realPrId;
        $d2['year'] = $this->year;

        $receipt = $this->pay->createReceipt($d2);
        
        // Notify Parent
        $st_rec = $this->student->getRecord(['user_id' => $pr->student_id])->with(['my_parent', 'user'])->get();
        $parent = $st_rec->pluck('my_parent')->first();

        if ($parent) {
            $parent->notify(new StudentPaymentPaid($receipt, $st_rec->first()));
        }

        $receiptData = $receipt->toArray();
        $receiptData['id'] = Qs::hash($receipt->id);
        $receiptData['pr_id'] = Qs::hash($receipt->pr_id);

        return $this->sendResponse([
            'receipt' => $receiptData,
            'balance' => $bal,
            'paid'    => $bal < 1,
        ], 'Payment processed and receipt created successfully.');
    }

    /**
     * Get details of a payment receipt.
     *
     * @param string $pr_id
     * @return JsonResponse
     */
    public function receipt(string $pr_id): JsonResponse
    {
        $realPrId = Qs::decodeHash($pr_id);
        if (!$realPrId) {
            return $this->sendError('Invalid Payment Record ID format.', [], 400);
        }

        $pr = $this->pay->getRecord(['id' => $realPrId])->with('receipt')->first();
        if (!$pr) {
            return $this->sendError('Payment record not found.', [], 404);
        }

        $receipts = $pr->receipt;
        $receiptsArray = [];
        foreach ($receipts as $rc) {
            $item = $rc->toArray();
            $item['id'] = Qs::hash($rc->id);
            $item['pr_id'] = Qs::hash($rc->pr_id);
            $receiptsArray[] = $item;
        }

        $studentRec = $this->student->getRecord(['user_id' => $pr->student_id])->first();

        return $this->sendResponse([
            'student'  => $studentRec ? [
                'name'   => $studentRec->user ? $studentRec->user->name : '',
                'adm_no' => $studentRec->adm_no,
            ] : null,
            'payment'  => $pr->payment ? [
                'title'  => $pr->payment->title,
                'amount' => $pr->payment->amount,
            ] : null,
            'ref_no'   => $pr->ref_no,
            'receipts' => $receiptsArray,
        ], 'Receipt details retrieved successfully.');
    }
}
