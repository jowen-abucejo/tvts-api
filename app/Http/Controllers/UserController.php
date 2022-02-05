<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

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
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $user = $request->user();
        // $ignoreSelf = ($user->id)?? 0;
        // $this->validate($request, [
        //     'password' => 'required',
        //     'new_password' => 'required|confirmed',
        //     'username' => [
        //         'required',
        //         'string',
        //         Rule::unique('users')->where(function ($query) use ($request) {
        //             return $query
        //                 ->where('username', $request->username);
        //         })->ignore($ignoreSelf),
        //     ],
        // ]);  
        $password_match_user = $this->checkPasswordMatch($request, $user)->getData();
        if($password_match_user->password_match_status){
            $user->update([
                "username" => $request->username,
                "password" => Hash::make($request->new_password)
            ]);
            $user->save();
            return response()->json(["update_success" => true]);
        }
        return response()->json(["update_success" => false]);
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
        $user = $request->user();
        //revoked all previous token
        $user->token()->revoke();
        return response()->json([
            'message' => 'Logout Success'
        ]);
    }

    public function login(Request $request)
    {
        $request->validate(
            [
                'username' => 'required|regex:/^[a-zA-ZÑñ0-9@$_.]+$/',
                'password' => 'required|regex:/^[a-zA-ZÑñ0-9@$_.]+$/',
            ]
        );
        $credentials = $request->only('username', 'password');        

        if (Auth::attempt($credentials)) {
            // Authentication passed...
             $user = Auth::user();
             
             //revoked all previous token
             DB::table('oauth_access_tokens')
            ->where('user_id', '=', $user->id)
            ->update([
                'revoked' => true
            ]);
             $token = $user->createToken($user->username);
             $expiration = $token->token->expires_at->diffInSeconds(Carbon::now());

            return response()->json(
                [
                    "user_type" => $user->user_type,
                    "access_token" => $token->accessToken,
                    "token_type" => "Bearer",
                    "expires_in" => $expiration
                ]
            );
        }
        return response()->json(["error" => "Login Failed!", "message" => "The user credentials were incorrect."], 401); 
    }

    public function checkPasswordMatch(Request $request, $user = null)
    {
        $user = $user ?? $request->user();
        return response()->json(["password_match_status" => Hash::check($request->password, $user->password)]);
    }
}
