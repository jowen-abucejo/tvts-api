<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return UserResource::collection(User::all());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $new_user = User::create([
            "name" => "admin",
            "username" => "admin123",
            "password" => Hash::make("admin"),
            "user_type" => "admin",
        ]);

        return new UserResource($new_user);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\User  $user_id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        return new UserResource(User::where('id', $request->user_id)->first());
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\User  $violationType
     * @return \Illuminate\Http\Response
     */
    public function edit(User $violationType)
    {
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $violationType
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $violationType)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\User  $violationType
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $violationType)
    {
        //
    }

    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        return response()->json([
            'message' => 'Logout Success'
        ]);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('username', 'password');
        // $credentials = ["username"=>"admin123", "password"=>"admin"];

        if (Auth::attempt($credentials)) {
            // Authentication passed...
             $user = Auth::user();
             $token = $user->createToken($user->username);
             $expiration = $token->token->expires_at->diffInSeconds(Carbon::now());

            return response()->json(
                [
                    "access_token" => $token->accessToken,
                    "token_type" => "Bearer",
                    "expires_in" => $expiration
                ]
            );
        }
        return response()->json(["error" => "invalid_credentials", "message" => "The user credentials were incorrect."], 401); 
    }
}
