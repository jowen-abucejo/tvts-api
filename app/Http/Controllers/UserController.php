<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
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
}
