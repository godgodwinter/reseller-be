<?php

namespace App\Http\Controllers;

use App\Helpers\Fungsi;
use App\Models\Reseller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ResellerAuthController extends Controller
{

    public function __construct()
    {
        $this->middleware('babeng:reseller', ['except' => ['login', 'register']]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = [
            'username' => $request->username,
            'password' => $request->password,
        ];

        // Try login with multiple possible credential keys
        if ($token = $this->attemptLogin($credentials)) {
            return $this->respondWithToken($token);
        }

        $credentials['username'] = $request->nomeridentitas ?? $request->username;
        if ($token = $this->attemptLogin($credentials)) {
            return $this->respondWithToken($token);
        }

        return response()->json(['error' => 'Unauthorized'], 401);
    }
    /**
     * Attempt login with given credentials.
     */
    private function attemptLogin(array $credentials)
    {
        return $this->guard()->attempt($credentials);
    }


    /**
     * Handle user registration
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:resellers,email',
            'username' => 'required|string|unique:resellers,username',
            'nomeridentitas' => 'required|string|unique:resellers,nomeridentitas',
            'password' => 'required|string|min:3',
        ]);

        $user = Reseller::create([
            'name' => $request->name,
            'email' => $request->email,
            'username' => $request->username,
            'nomeridentitas' => $request->nomeridentitas,
            'password' => Hash::make($request->password),
        ]);

        $token = Auth::login($user);

        return response()->json([
            'status' => 'success',
            'message' => 'User created successfully',
            'user' => $user,
            'authorisation' => [
                'token' => $token,
                'type' => 'bearer',
            ],
        ]);
    }
    /**
     * Handle user logout
     */
    public function logout()
    {
        $this->guard()->logout();
        return response()->json(['message' => 'Successfully logged out']);
    }


    /**
     * Define guard for reseller
     */
    protected function guard()
    {
        return Auth::guard('reseller');
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'data' => (object)[
                'token' => $token,
                'me' => $this->guard()->user(),
                'newToken' => $token,
                'status' => true,
            ],
            'message' => "Success",
            'code' => 200,
            'token_type' => 'bearer',
            'expires_in' => $this->guard()->factory()->getTTL() * 1,  //auto logout after 1 hour (default)
        ]);
    }
    /**
     * Get the authenticated User
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        // return response()->json(['message' => 'ME']);
        return response()->json([
            'success' => true,
            'data' => ($this->guard()->user())
        ]);
        // return response()->json($this->guard()->user());
    }


    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken($this->guard()->refresh());
    }
}
