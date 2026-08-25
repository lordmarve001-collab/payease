<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AjoGroupResource;
use App\Models\AjoGroup;
use App\Models\AjoMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AjoController extends Controller
{
    public function groups(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            ]);

            $groups = AjoGroup::where('status', 'active')
                ->orderBy('created_at', 'desc')
                ->paginate($validated['per_page'] ?? 20);

            return response()->json([
                'success' => true,
                'data' => [
                    'groups' => AjoGroupResource::collection($groups->items()),
                    'pagination' => [
                        'current_page' => $groups->currentPage(),
                        'last_page' => $groups->lastPage(),
                        'per_page' => $groups->perPage(),
                        'total' => $groups->total(),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch groups: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function showGroup(Request $request, string $id): JsonResponse
    {
        try {
            $group = AjoGroup::with(['members.user'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'group' => new AjoGroupResource($group),
                    'members' => $group->members->map(function ($member) {
                        return [
                            'id' => $member->id,
                            'user_id' => $member->user_id,
                            'user_name' => $member->user->full_name ?? null,
                            'position' => $member->position,
                            'status' => $member->status,
                            'joined_at' => $member->created_at,
                        ];
                    }),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Group not found',
            ], 404);
        }
    }

    public function memberships(Request $request): JsonResponse
    {
        try {
            $memberships = AjoMember::where('user_id', $request->user()->id)
                ->with('group')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'memberships' => $memberships->map(function ($membership) {
                        return [
                            'id' => $membership->id,
                            'group' => new AjoGroupResource($membership->group),
                            'position' => $membership->position,
                            'status' => $membership->status,
                            'joined_at' => $membership->created_at,
                        ];
                    }),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch memberships: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function contributions(Request $request, string $groupId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            ]);

            $membership = AjoMember::where('user_id', $request->user()->id)
                ->where('ajo_group_id', $groupId)
                ->first();

            if (! $membership) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not a member of this group',
                ], 403);
            }

            $contributions = $membership->contributions()
                ->orderBy('created_at', 'desc')
                ->paginate($validated['per_page'] ?? 20);

            return response()->json([
                'success' => true,
                'data' => [
                    'contributions' => $contributions->items(),
                    'pagination' => [
                        'current_page' => $contributions->currentPage(),
                        'last_page' => $contributions->lastPage(),
                        'per_page' => $contributions->perPage(),
                        'total' => $contributions->total(),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch contributions: ' . $e->getMessage(),
            ], 500);
        }
    }
}
