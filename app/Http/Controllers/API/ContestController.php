<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ContestService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContestController extends Controller
{
    use ApiResponse;

    protected $contestService;

    public function __construct(ContestService $contestService)
    {
        $this->contestService = $contestService;
    }
    public function index()
    {
        try {
            $contests = $this->contestService->getContestsForIndex();

            $data = $contests->map(function ($contest) {
                return [
                    'id'                 => $contest->id,
                    'title'              => $contest->title,
                    'creator'            => $contest->creator->name ?? 'Unknown',
                    'description'        => $contest->description,
                    'prize'              => $contest->prize,
                    'total_slots'        => $contest->total_slots,
                    'total_participants' => $contest->participants_count ?? 0,
                    'end_date'           => $contest->end_date,
                ];
            });
            return $this->success($data, 'Contests retrieved successfully!');
        } catch (\Exception $e) {
            return $this->error(null, $e->getMessage(), 500);
        }
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'       => 'required|string|max:255',
            'prize'       => 'required|numeric',
            'end_date'    => 'required|date',
            'total_slots' => 'required|integer',
            'prize_photo' => 'nullable|image|max:2048',
            'document'    => 'nullable|mimes:pdf,doc,docx|max:5120',
            'sponsors'               => 'nullable|array',
            'sponsors.*.user_id'     => 'required_with:sponsors|exists:users,id',
            'sponsors.*.amount'      => 'required_with:sponsors|numeric',
            'collaborators'          => 'nullable|array',
            'collaborators.*'        => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            $errorMessage = implode(' ', $errors);
            return $this->error($validator->errors(), $errorMessage, 422);
        }

        try {
            $contest = $this->contestService->createContest($request->all());
            return $this->success($contest, 'Contest created successfully!', 200);

        } catch (\Exception $e) {
            return $this->error(null, $e->getMessage(), 500);
        }
    }
    public function myContests()
    {
        try {
            $contests = $this->contestService->getMyContests();

            $data = $contests->map(function ($contest) {
                return [
                    'id'                 => $contest->id,
                    'title'              => $contest->title,
                    'prize'              => $contest->prize,
                    'total_participants' => $contest->participants_count ?? 0,
                    'is_published'       => $contest->is_published,
                    'end_date'           => $contest->end_date,
                ];
            });
            return $this->success($data, 'My contests retrieved successfully!');
        } catch (\Exception $e) {
            return $this->error(null, $e->getMessage(), 500);
        }
    }
    public function show(Request $request)
    {
        $id = $request->id;
        try {
            $contest = $this->contestService->getContestDetails($id);
            return $this->success($contest, 'Contest details retrieved successfully!');
        } catch (\Exception $e) {
            return $this->error(null, 'Contest not found!', 404);
        }
    }
    public function contestDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contest_id' => 'required|exists:contests,id',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        $id = $request->contest_id;

        try {
            $data = $this->contestService->getContestDetailsData($id);
            return $this->success($data, 'Contest details retrieved successfully!');
        } catch (\Exception $e) {
            return $this->error(null, $e->getMessage(), 500);
        }
    }
    public function update(Request $request)
    {
        $id = $request->id;
        $validator = Validator::make($request->all(), [
            'title'       => 'sometimes|string|max:255',
            'prize'       => 'sometimes|numeric',
            'end_date'    => 'sometimes|date',
            'total_slots' => 'sometimes|integer',
            'prize_photo' => 'nullable|image|max:2048',
            'document'    => 'nullable|mimes:pdf,doc,docx|max:5120',

            'deleted_sponsor_ids'      => 'nullable|array',
            'deleted_sponsor_ids.*'    => 'exists:sponsorships,id',
            'deleted_collaborator_ids' => 'nullable|array',
            'deleted_collaborator_ids.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        try {
            $contest = $this->contestService->updateContest($id, $request->all());
            return $this->success($contest, 'Contest updated successfully!');
        } catch (\Exception $e) {
            return $this->error(null, $e->getMessage(), 500);
        }
    }
    public function participatedContests()
    {
        try {
            $contests = $this->contestService->getUserParticipatedContests();

            $data = $contests->map(function ($contest) {
                return [
                    'id'                 => $contest->id,
                    'creator'            => $contest->creator->name ?? 'Unknown',
                    'description'        => $contest->description,
                    'prize'              => $contest->prize,
                    'total_slots'        => $contest->total_slots,
                    'total_participants' => $contest->participants_count ?? 0,
                    'end_date'           => $contest->end_date,
                ];
            });

            return $this->success($data, 'Participated contests retrieved successfully!');

        } catch (\Exception $e) {
            return $this->error(null, $e->getMessage(), 500);
        }
    }
    public function join(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contest_id' => 'required|exists:contests,id',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        $id = $request->contest_id;

        try {
            $this->contestService->participateInContest($id);

            return $this->success(null, 'Successfully participated in the contest!');
        } catch (\Exception $e) {
            return $this->error(null, $e->getMessage(), 400);
        }
    }

}
