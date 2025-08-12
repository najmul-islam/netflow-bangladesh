<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

/**
 * @OA\Tag(
 *     name="User Profile",
 *     description="Endpoints for managing user profile (requires authentication)"
 * )
 */
class ProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * @OA\Get(
     *     path="/api/user/profile",
     *     tags={"User Profile"},
     *     summary="Get user profile",
     *     description="Get the authenticated user's complete profile information",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User profile information",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user_id", type="string", example="uuid-string"),
     *                 @OA\Property(property="first_name", type="string", example="John"),
     *                 @OA\Property(property="last_name", type="string", example="Doe"),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="phone", type="string", example="+8801234567890"),
     *                 @OA\Property(property="avatar_url", type="string", example="https://example.com/storage/profiles/user.jpg"),
     *                 @OA\Property(property="bio", type="string", example="Passionate web developer learning new technologies"),
     *                 @OA\Property(property="timezone", type="string", example="Asia/Dhaka"),
     *                 @OA\Property(property="language", type="string", example="en"),
     *                 @OA\Property(property="created_at", type="string", example="2024-01-15T10:00:00Z"),
     *                 @OA\Property(property="email_verified", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     )
     * )
     */
    public function show(): JsonResponse
    {
        $user = auth()->user()->load('addresses');

        // Calculate user stats
        $stats = [
            'total_enrollments' => $user->enrollments()->count(),
            'completed_courses' => $user->enrollments()->where('status', 'completed')->count(),
            'certificates_earned' => $user->certificates()->where('is_revoked', false)->count(),
            'total_study_hours' => $user->lessonProgress()->sum('time_spent_minutes') / 60
        ];

        return response()->json([
            'data' => [
                'user_id' => $user->user_id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar_url' => $user->avatar_url,
                'bio' => $user->bio,
                'timezone' => $user->timezone,
                'language' => $user->language,
                'addresses' => $user->addresses,
                'stats' => $stats,
                'created_at' => $user->created_at,
                'email_verified' => $user->email_verified
            ]
        ]);
    }

    /**
     * @OA\Patch(
     *     path="/api/user/profile",
     *     tags={"User Profile"},
     *     summary="Update user profile",
     *     description="Update the authenticated user's profile information",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="first_name", type="string", example="John"),
     *             @OA\Property(property="last_name", type="string", example="Doe"),
     *             @OA\Property(property="phone", type="string", example="+8801234567890"),
     *             @OA\Property(property="bio", type="string", example="Updated bio description"),
     *             @OA\Property(property="timezone", type="string", example="Asia/Dhaka"),
     *             @OA\Property(property="language", type="string", example="en")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profile updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user_id", type="string", example="uuid-string"),
     *                 @OA\Property(property="first_name", type="string", example="John"),
     *                 @OA\Property(property="last_name", type="string", example="Smith"),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="phone", type="string", example="+8801234567890"),
     *                 @OA\Property(property="bio", type="string", example="Updated bio description"),
     *                 @OA\Property(property="timezone", type="string", example="Asia/Dhaka"),
     *                 @OA\Property(property="language", type="string", example="en"),
     *                 @OA\Property(property="updated_at", type="string", example="2024-01-20T15:30:00Z")
     *             ),
     *             @OA\Property(property="message", type="string", example="Profile updated successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'bio' => 'nullable|string',
            'timezone' => 'nullable|string|max:50',
            'language' => 'nullable|string|max:10'
        ]);

        $user = auth()->user();

        $updateData = array_filter([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone' => $request->phone,
            'bio' => $request->bio,
            'timezone' => $request->timezone,
            'language' => $request->language
        ], function ($value) {
            return $value !== null;
        });

        $user->update($updateData);

        return response()->json([
            'data' => [
                'user_id' => $user->user_id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'bio' => $user->bio,
                'timezone' => $user->timezone,
                'language' => $user->language,
                'updated_at' => $user->updated_at
            ],
            'message' => 'Profile updated successfully'
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/user/profile/upload-picture",
     *     tags={"Profile"},
     *     summary="Upload profile picture",
     *     description="Upload or update the user's profile picture",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="avatar",
     *                     type="string",
     *                     format="binary",
     *                     description="Profile picture image (max 2MB, jpg/png/jpeg)"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profile picture uploaded successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="avatar_url", type="string", example="https://example.com/storage/avatars/user-uuid.jpg")
     *             ),
     *             @OA\Property(property="message", type="string", example="Avatar updated successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The avatar field is required."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="avatar",
     *                     type="array",
     *                     @OA\Items(type="string", example="The avatar field is required.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function uploadProfilePicture(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        $user = auth()->user();

        // Delete old avatar if exists and it's a local file
        if ($user->avatar_url && str_starts_with($user->avatar_url, '/storage/')) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $user->avatar_url));
        }

        // Store new avatar
        $path = $request->file('avatar')->store('avatars', 'public');
        $avatarUrl = Storage::url($path);

        $user->update(['avatar_url' => $avatarUrl]);

        return response()->json([
            'data' => [
                'avatar_url' => $avatarUrl
            ],
            'message' => 'Avatar updated successfully'
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/user/profile/change-password",
     *     tags={"Profile"},
     *     summary="Change password",
     *     description="Change the user's password",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"current_password", "new_password", "new_password_confirmation"},
     *             @OA\Property(property="current_password", type="string", description="Current password"),
     *             @OA\Property(property="new_password", type="string", description="New password (min 8 characters)"),
     *             @OA\Property(property="new_password_confirmation", type="string", description="New password confirmation")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password changed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Password changed successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid current password",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid current password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The current password field is required."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="current_password",
     *                     type="array",
     *                     @OA\Items(type="string", example="The current password field is required.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = auth()->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Invalid current password'
            ], 400);
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'message' => 'Password changed successfully'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/user/profile/addresses",
     *     tags={"Profile"},
     *     summary="Get user addresses",
     *     description="Get all addresses for the authenticated user",
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Addresses retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="address_id", type="string", example="uuid-string"),
     *                     @OA\Property(property="address_type", type="string", example="home"),
     *                     @OA\Property(property="street_address", type="string", example="123 Main Street"),
     *                     @OA\Property(property="city", type="string", example="Dhaka"),
     *                     @OA\Property(property="state", type="string", example="Dhaka"),
     *                     @OA\Property(property="postal_code", type="string", example="1000"),
     *                     @OA\Property(property="country", type="string", example="Bangladesh"),
     *                     @OA\Property(property="is_primary", type="boolean", example=true),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:00:00Z")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function getAddresses(): JsonResponse
    {
        $addresses = Address::where('user_id', auth()->user()->user_id)
            ->orderBy('is_primary', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $addresses]);
    }

    /**
     * @OA\Post(
     *     path="/api/user/profile/addresses",
     *     tags={"Profile"},
     *     summary="Add new address",
     *     description="Add a new address for the user",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"address_type", "street_address", "city", "state", "postal_code", "country"},
     *             @OA\Property(property="address_type", type="string", enum={"home", "work", "other"}, description="Address type"),
     *             @OA\Property(property="street_address", type="string", maxLength=255, description="Street address"),
     *             @OA\Property(property="city", type="string", maxLength=100, description="City"),
     *             @OA\Property(property="state", type="string", maxLength=100, description="State/Province"),
     *             @OA\Property(property="postal_code", type="string", maxLength=20, description="Postal code"),
     *             @OA\Property(property="country", type="string", maxLength=100, description="Country"),
     *             @OA\Property(property="is_primary", type="boolean", description="Set as primary address")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Address added successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="address_id", type="string", example="uuid-string"),
     *                 @OA\Property(property="address_type", type="string", example="home"),
     *                 @OA\Property(property="street_address", type="string", example="456 New Street"),
     *                 @OA\Property(property="city", type="string", example="Chittagong"),
     *                 @OA\Property(property="state", type="string", example="Chittagong"),
     *                 @OA\Property(property="postal_code", type="string", example="4000"),
     *                 @OA\Property(property="country", type="string", example="Bangladesh"),
     *                 @OA\Property(property="is_primary", type="boolean", example=false),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-20T16:00:00Z")
     *             ),
     *             @OA\Property(property="message", type="string", example="Address added successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The address type field is required."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="address_type",
     *                     type="array",
     *                     @OA\Items(type="string", example="The address type field is required.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function addAddress(Request $request): JsonResponse
    {
        $request->validate([
            'address_type' => 'required|in:home,work,other',
            'street_address' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            'is_primary' => 'nullable|boolean'
        ]);

        // If setting as primary, update existing primary addresses
        if ($request->is_primary) {
            Address::where('user_id', auth()->user()->user_id)
                ->where('is_primary', true)
                ->update(['is_primary' => false]);
        }

        $address = Address::create([
            'user_id' => auth()->user()->user_id,
            'address_type' => $request->address_type,
            'street_address' => $request->street_address,
            'city' => $request->city,
            'state' => $request->state,
            'postal_code' => $request->postal_code,
            'country' => $request->country,
            'is_primary' => $request->is_primary ?? false
        ]);

        return response()->json([
            'data' => $address,
            'message' => 'Address added successfully'
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/user/profile/addresses/{address_id}",
     *     tags={"Profile"},
     *     summary="Update address",
     *     description="Update an existing address",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="address_id",
     *         in="path",
     *         required=true,
     *         description="The address ID",
     *         @OA\Schema(type="string", example="uuid-string")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="address_type", type="string", enum={"home", "work", "other"}, description="Address type"),
     *             @OA\Property(property="street_address", type="string", maxLength=255, description="Street address"),
     *             @OA\Property(property="city", type="string", maxLength=100, description="City"),
     *             @OA\Property(property="state", type="string", maxLength=100, description="State/Province"),
     *             @OA\Property(property="postal_code", type="string", maxLength=20, description="Postal code"),
     *             @OA\Property(property="country", type="string", maxLength=100, description="Country"),
     *             @OA\Property(property="is_primary", type="boolean", description="Set as primary address")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Address updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="address_id", type="string", example="uuid-string"),
     *                 @OA\Property(property="address_type", type="string", example="work"),
     *                 @OA\Property(property="street_address", type="string", example="456 Updated Street"),
     *                 @OA\Property(property="city", type="string", example="Dhaka"),
     *                 @OA\Property(property="state", type="string", example="Dhaka"),
     *                 @OA\Property(property="postal_code", type="string", example="1200"),
     *                 @OA\Property(property="country", type="string", example="Bangladesh"),
     *                 @OA\Property(property="is_primary", type="boolean", example=true),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-20T16:30:00Z")
     *             ),
     *             @OA\Property(property="message", type="string", example="Address updated successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Address not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Address not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="address_type",
     *                     type="array",
     *                     @OA\Items(type="string", example="The selected address type is invalid.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function updateAddress(Request $request, string $address_id): JsonResponse
    {
        $request->validate([
            'address_type' => 'nullable|in:home,work,other',
            'street_address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'is_primary' => 'nullable|boolean'
        ]);

        $address = Address::where('address_id', $address_id)
            ->where('user_id', auth()->user()->user_id)
            ->firstOrFail();

        // If setting as primary, update existing primary addresses
        if ($request->is_primary) {
            Address::where('user_id', auth()->user()->user_id)
                ->where('address_id', '!=', $address_id)
                ->where('is_primary', true)
                ->update(['is_primary' => false]);
        }

        $updateData = array_filter([
            'address_type' => $request->address_type,
            'street_address' => $request->street_address,
            'city' => $request->city,
            'state' => $request->state,
            'postal_code' => $request->postal_code,
            'country' => $request->country,
            'is_primary' => $request->is_primary
        ], function ($value) {
            return $value !== null;
        });

        $address->update($updateData);

        return response()->json([
            'data' => $address->fresh(),
            'message' => 'Address updated successfully'
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/user/profile/addresses/{address_id}",
     *     tags={"Profile"},
     *     summary="Delete address",
     *     description="Delete an address",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="address_id",
     *         in="path",
     *         required=true,
     *         description="The address ID",
     *         @OA\Schema(type="string", example="uuid-string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Address deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Address deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Address not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Address not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function deleteAddress(string $address_id): JsonResponse
    {
        $address = Address::where('address_id', $address_id)
            ->where('user_id', auth()->user()->user_id)
            ->firstOrFail();

        $address->delete();

        return response()->json([
            'message' => 'Address deleted successfully'
        ]);
    }
}
