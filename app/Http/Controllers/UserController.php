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
use Illuminate\Support\Str;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $limit = $request->limit ?? 30;
        $order = $request->order ?? "ASC";
        $search = $request->search ? rawurldecode($request->search) : "";

        $like = env("DB_CONNECTION") == "pgsql" ? "ILIKE" : "LIKE";

        if ($request->fetch_all === true) {
            return UserResource::collection(
                User::withCount("ticketIssued")
                    ->withTrashed()
                    ->where("name", $like, "%" . $search . "%")
                    ->orWhere("username", $like, "%" . $search . "%")
                    ->get()
            );
        }

        return UserResource::collection(
            User::withCount("ticketIssued")
                ->withTrashed()
                ->where("name", $like, "%" . $search . "%")
                ->orWhere("username", $like, "%" . $search . "%")
                ->orderBy("name", $order)
                ->orderBy("username", $order)
                ->paginate($limit)
        );
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
        $name = Str::title(preg_replace("!\s+!", " ", $request->name));
        $checkUsername = User::where("username", $request->username)->count();

        if ($checkUsername > 0) {
            return response()->json(
                [
                    "error" => "Username Already Exist!",
                    "message" => "Please provide new username.",
                ],
                400
            );
        }

        $new_user = User::create([
            "name" => $name,
            "username" => $request->username,
            "password" => Hash::make($request->username),
            "user_type" => $request->user_type,
        ]);

        return new UserResource($new_user);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\User  $user_id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $user_id)
    {
        if (!$user_id) {
            return response()->json(
                [
                    "error" => "User Account Doesn't Exist",
                    "message" =>
                        "No match found for the user account specified.",
                ],
                400
            );
        }

        $user = User::find($user_id);

        if (!$user) {
            return response()->json(
                [
                    "error" => "User Account Doesn't Exist",
                    "message" =>
                        "No match found for the user account specified.",
                ],
                400
            );
        }

        return new UserResource($user);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\User  $violationType
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $user_id = null)
    {
        $user = $user_id ? User::find($user_id) : $request->user();
        if (!$user) {
            return response()->json(
                [
                    "error" => "User Account Doesn't Exist",
                    "message" =>
                        "No match found for the user account specified.",
                ],
                400
            );
        }

        $username = Str::lower(preg_replace("!\s*!", "", $user->name));
        try {
            $user->update([
                "username" => $username,
                "password" => Hash::make($username),
            ]);
            $user->save();
            return response()->json(["update_success" => true]);
        } catch (\Throwable $th) {
            return response()->json(
                [
                    "error" => "Unable to Reset Login Credentials!",
                    "message" => "Please try again.",
                ],
                400
            );
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $user_id = null)
    {
        $user = $user_id ? User::find($user_id) : $request->user();
        if (!$user) {
            return response()->json(
                [
                    "error" => "User Account Doesn't Exist",
                    "message" =>
                        "No match found for the user account specified.",
                ],
                400
            );
        }

        if ($request->user()->isAdmin() && $user_id) {
            $checkUsername = User::where("username", $request->username)
                ->where("id", "!=", $user->id)
                ->count();

            if ($checkUsername > 0) {
                return response()->json(
                    [
                        "error" => "Username Already Exist!",
                        "message" => "Please provide new username.",
                    ],
                    400
                );
            }

            $name = Str::title(preg_replace("!\s+!", " ", $request->name));
            $user->update([
                "name" => $name,
                "user_type" => $request->user_type,
            ]);
            $user->save();
            return response()->json(["update_success" => true]);
        }

        $password_match_user = $this->checkPasswordMatch(
            $request,
            $user
        )->getData();

        $checkUsername = User::where("username", $request->username)
            ->where("id", "!=", $user->id)
            ->count();

        if ($checkUsername > 0) {
            return response()->json(
                [
                    "error" => "Username Already Exist!",
                    "message" => "Please provide new username.",
                ],
                401
            );
        }

        if ($password_match_user->password_match_status) {
            $user->update([
                "username" => $request->username,
                "password" => Hash::make($request->new_password),
            ]);
            $user->save();
            return response()->json(["update_success" => true]);
        }
        return response()->json(
            ["error" => "User Account Update Failed!", "message" => ""],
            400
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  number $user_id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $user_id)
    {
        if (!$user_id) {
            return response()->json(
                [
                    "error" => "User Account Doesn't Exist",
                    "message" =>
                        "No match found for the user account specified.",
                ],
                400
            );
        }

        $user = User::withTrashed()
            ->withCount("ticketIssued")
            ->find($user_id);

        if (!$user) {
            return response()->json(
                [
                    "error" => "User Account Doesn't Exist",
                    "message" =>
                        "No match found for the user account specified.",
                ],
                400
            );
        }

        if (boolval($request->permanentDelete) === false) {
            if ($user->trashed()) {
                $user->restore();
                $user->save();
                return response()->json(["update_status" => true]);
            }

            $user->delete();
            return response()->json(["update_status" => $user->trashed()]);
        }

        if ($user->ticket_issued_count > 0) {
            return response()->json(
                [
                    "error" => "Unable to Delete User Account!",
                    "message" => "User is associated with $user->ticket_issued_count tickets. You can set account as 'NOT ACTIVE' instead.",
                ],
                400
            );
        }

        $user->forceDelete();
        return response()->json(["deleted" => true]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        //revoked all previous token
        $user->token()->revoke();
        return response()->json([
            "message" => "Logout Success",
        ]);
    }

    public function login(Request $request)
    {
        $active_version = env("API_VERSION");
        $version = $request->api_version;
        if (!$version || $version !== $active_version) {
            return response()->json(
                [
                    "error" => "Login Failed!",
                    "message" => "Application not properly configured.",
                ],
                401
            );
        }

        $request->validate([
            "username" => 'required|regex:/^[a-zA-ZÑñ0-9@$_.]+$/',
            "password" => 'required|regex:/^[a-zA-ZÑñ0-9@$_.]+$/',
        ]);
        $credentials = $request->only("username", "password");

        if (Auth::attempt($credentials)) {
            // Authentication passed...
            $user = Auth::user();

            //revoked all previous token
            DB::table("oauth_access_tokens")
                ->where("user_id", "=", $user->id)
                ->update([
                    "revoked" => true,
                ]);
            $token = $user->createToken($user->username);
            $expiration = $token->token->expires_at->diffInSeconds(
                Carbon::now()
            );

            return response()->json([
                "isAdmin" => $user->isAdmin(),
                "isTreasury" => $user->isTreasury(),
                "access_token" => $token->accessToken,
                "token_type" => "Bearer",
                "expires_in" => $expiration,
            ]);
        }
        return response()->json(
            [
                "error" => "Login Failed!",
                "message" => "The user credentials were incorrect.",
            ],
            401
        );
    }

    public function checkPasswordMatch(Request $request, $user = null)
    {
        $user = $user ?? $request->user();
        return response()->json([
            "password_match_status" => Hash::check(
                $request->password,
                $user->password
            ),
        ]);
    }
}
