<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Dotenv\Exception\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        try {
            // Validate the request data
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|unique:users',
                'password' => 'required|string',
                'role' => 'required|in:admin,teacher,student',
            ]);

            // Hash the password
            $validated['password'] = Hash::make($validated['password']);

            // Create the user
            $user = User::create($validated);

            // Return a success response
            return response()->json(['message' => 'User registered successfully', 'user' => $user], 201);
        } catch (ValidationException $e) {
            // Handle validation errors
            return response()->json(['message' => 'Validation failed', 'errors' => $e], 422);
        } catch (\Exception $e) {
            // Handle other exceptions
            return response()->json(['message' => 'User registration failed', 'error' => $e->getMessage()], 500);
        }
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = User::where('email', $request->email)->first();
       // $user->tokens()->delete();
        // Create access token (expires in 1 day)
        $accessToken = $user->createToken('access_token', ['*'], now()->addDay())->plainTextToken;

        // Create refresh token (expires in 7 days)
        $refreshToken = $user->createToken('refresh_token', ['*'], now()->addDays(7))->plainTextToken;


        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'accessToken' => $accessToken,
            'refreshToken' => $refreshToken,
            'expires_in' => Carbon::now()->addDay()->toDateTimeString(),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }
}
