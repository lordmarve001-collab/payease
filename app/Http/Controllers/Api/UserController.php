<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => [
                    'user' => new UserResource($request->user()),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch profile: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'full_name' => ['sometimes', 'string', 'max:255'],
                'email' => ['sometimes', 'nullable', 'email', 'unique:users,email,' . $request->user()->id],
            ]);

            $user = $request->user();
            $user->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => [
                    'user' => new UserResource($user->fresh()),
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function changePin(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'current_pin' => ['required', 'string', 'digits:4'],
                'new_pin' => ['required', 'string', 'digits:4', 'confirmed'],
                'new_pin_confirmation' => ['required', 'string', 'digits:4'],
            ]);

            $user = $request->user();

            if (! $user->verifyPin($validated['current_pin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Current PIN is incorrect',
                ], 422);
            }

            $user->update([
                'pin' => Hash::make($validated['new_pin']),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'PIN changed successfully',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to change PIN: ' . $e->getMessage(),
            ], 500);
        }
    }
}
