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
 * @group User API - Profile
 * 
 * Endpoints for managing user profile (requires authentication)
 */
class ProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get user profile
     * 
     * Get the authenticated user's complete profile information.
     * 
     * @authenticated
     * 
     * @response 200 {
     *   "data": {
     *     "user_id": "uuid-string",
     *     "first_name": "John",
     *     "last_name": "Doe",
     *     "email": "john@example.com",
     *     "phone": "+8801234567890",
     *     "date_of_birth": "1995-05-15",
     *     "gender": "male",
     *     "profile_picture": "https://example.com/storage/profiles/user.jpg",
     *     "bio": "Passionate web developer learning new technologies",
     *     "timezone": "Asia/Dhaka",
     *     "language": "en",
     *     "notification_preferences": {
     *       "email": true,
     *       "push": true,
     *       "types": {
     *         "announcement": true,
     *         "assignment": true,
     *         "assessment": true
     *       }
     *     },
     *     "addresses": [
     *       {
     *         "address_id": "uuid-string",
     *         "address_type": "home",
     *         "street_address": "123 Main Street",
     *         "city": "Dhaka",
     *         "state": "Dhaka",
     *         "postal_code": "1000",
     *         "country": "Bangladesh",
     *         "is_primary": true
     *       }
     *     ],
     *     "stats": {
     *       "total_enrollments": 3,
     *       "completed_courses": 1,
     *       "certificates_earned": 1,
     *       "total_study_hours": 45.5
     *     },
     *     "created_at": "2024-01-15T10:00:00Z",
     *     "email_verified_at": "2024-01-15T10:30:00Z"
     *   }
     * }
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
     * Update user profile
     * 
     * Update the authenticated user's profile information.
     * 
     * @authenticated
     * @bodyParam first_name string First name.
     * @bodyParam last_name string Last name.
     * @bodyParam phone string Phone number.
     * @bodyParam date_of_birth date Date of birth (YYYY-MM-DD format).
     * @bodyParam gender string Gender (male,female,other).
     * @bodyParam bio string Bio/description.
     * @bodyParam timezone string Timezone.
     * @bodyParam language string Language preference.
     * 
     * @response 200 {
     *   "data": {
     *     "user_id": "uuid-string",
     *     "first_name": "John",
     *     "last_name": "Smith",
     *     "email": "john@example.com",
     *     "phone": "+8801234567890",
     *     "date_of_birth": "1995-05-15",
     *     "gender": "male",
     *     "bio": "Updated bio description",
     *     "timezone": "Asia/Dhaka",
     *     "language": "en",
     *     "updated_at": "2024-01-20T15:30:00Z"
     *   },
     *   "message": "Profile updated successfully"
     * }
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
     * Upload profile picture
     * 
     * Upload or update the user's profile picture.
     * 
     * @authenticated
     * @bodyParam profile_picture file required Profile picture image (max 2MB, jpg/png).
     * 
     * @response 200 {
     *   "data": {
     *     "profile_picture": "https://example.com/storage/profiles/user-uuid.jpg"
     *   },
     *   "message": "Profile picture updated successfully"
     * }
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
     * Change password
     * 
     * Change the user's password.
     * 
     * @authenticated
     * @bodyParam current_password string required Current password.
     * @bodyParam new_password string required New password (min 8 characters).
     * @bodyParam new_password_confirmation string required New password confirmation.
     * 
     * @response 200 {
     *   "message": "Password changed successfully"
     * }
     * 
     * @response 400 {
     *   "message": "Invalid current password"
     * }
     */
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = auth()->user();

        if (!Hash::check($request->current_password, $user->password_hash)) {
            return response()->json([
                'message' => 'Invalid current password'
            ], 400);
        }

        $user->update([
            'password_hash' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'message' => 'Password changed successfully'
        ]);
    }

    /**
     * Get user addresses
     * 
     * Get all addresses for the authenticated user.
     * 
     * @authenticated
     * 
     * @response 200 {
     *   "data": [
     *     {
     *       "address_id": "uuid-string",
     *       "address_type": "home",
     *       "street_address": "123 Main Street",
     *       "city": "Dhaka",
     *       "state": "Dhaka",
     *       "postal_code": "1000",
     *       "country": "Bangladesh",
     *       "is_primary": true,
     *       "created_at": "2024-01-15T10:00:00Z"
     *     }
     *   ]
     * }
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
     * Add new address
     * 
     * Add a new address for the user.
     * 
     * @authenticated
     * @bodyParam address_type string required Address type (home,work,other).
     * @bodyParam street_address string required Street address.
     * @bodyParam city string required City.
     * @bodyParam state string required State/Province.
     * @bodyParam postal_code string required Postal code.
     * @bodyParam country string required Country.
     * @bodyParam is_primary boolean Set as primary address.
     * 
     * @response 201 {
     *   "data": {
     *     "address_id": "uuid-string",
     *     "address_type": "home",
     *     "street_address": "456 New Street",
     *     "city": "Chittagong",
     *     "state": "Chittagong",
     *     "postal_code": "4000",
     *     "country": "Bangladesh",
     *     "is_primary": false,
     *     "created_at": "2024-01-20T16:00:00Z"
     *   },
     *   "message": "Address added successfully"
     * }
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
     * Update address
     * 
     * Update an existing address.
     * 
     * @authenticated
     * @urlParam address_id string required The address ID. Example: "uuid-string"
     * @bodyParam address_type string Address type (home,work,other).
     * @bodyParam street_address string Street address.
     * @bodyParam city string City.
     * @bodyParam state string State/Province.
     * @bodyParam postal_code string Postal code.
     * @bodyParam country string Country.
     * @bodyParam is_primary boolean Set as primary address.
     * 
     * @response 200 {
     *   "data": {
     *     "address_id": "uuid-string",
     *     "address_type": "work",
     *     "street_address": "456 Updated Street",
     *     "city": "Dhaka",
     *     "state": "Dhaka",
     *     "postal_code": "1200",
     *     "country": "Bangladesh",
     *     "is_primary": true,
     *     "updated_at": "2024-01-20T16:30:00Z"
     *   },
     *   "message": "Address updated successfully"
     * }
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
     * Delete address
     * 
     * Delete an address.
     * 
     * @authenticated
     * @urlParam address_id string required The address ID. Example: "uuid-string"
     * 
     * @response 200 {
     *   "message": "Address deleted successfully"
     * }
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
