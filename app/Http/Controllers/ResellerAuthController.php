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

        // login reseller pakai guard reseller
        if ($token = $this->attemptLogin($credentials)) {
            return $this->respondWithToken($token);
        }

        // fallback: coba login pakai nomeridentitas kalau ada
        $credentials['username'] = $request->nomeridentitas ?? $request->username;
        if ($token = $this->attemptLogin($credentials)) {
            return $this->respondWithToken($token);
        }

        return response()->json(['error' => 'Unauthorized'], 401);
    }

    private function attemptLogin(array $credentials)
    {
        return Auth::guard('reseller')->attempt($credentials);
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'success'     => true,
            'message'     => 'Login reseller berhasil',
            'data'        => [
                'token'      => $token,
                'me'         => Auth::guard('reseller')->user(),
                'token_type' => 'bearer',
                'expires_in' => Auth::guard('reseller')->factory()->getTTL() * 60, // detik
            ],
        ]);
    }

    public function me()
    {
        return response()->json([
            'success' => true,
            'data'    => Auth::guard('reseller')->user(),
        ]);
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

        $user = reseller::create([
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
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken($this->guard()->refresh());
    }
}
