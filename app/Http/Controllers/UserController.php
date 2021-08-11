<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Auth;
use Validator;

class UserController extends Controller
{


    protected $user;


    public function __construct()
    {

        $this->middleware("auth:api", ["except" => ["login", "register"]]);
        $this->user = new User;
    }
    public function register(Request $request)
    {

        $validated =  $request->validate([
            'firstName' => 'required',
            'lastName' => 'required',
            'email' => 'required|unique:users',
            'password' => 'required|min:6'
        ]);

        User::create([
            'firstName' => $request->firstName,
            'lastName' => $request->lastName,
            'email' => $request->email,
            'address' => $request->address,
            'dob' => $request->dob,
            'gender' => $request->gender,
            'city' => $request->city,
            'state' => $request->state,
            'country' => $request->country,
            'phoneNumber' => $request->phoneNumber,
            'profileImage' => $request->profileImage,
            'password' => Hash::make($request->password)

        ]);


        $responseMessage = "Registration Successful";

        return response()->json([
            'success' => true,
            'message' => $responseMessage
        ], 200);
    }
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string',
            'password' => 'required|min:6',
        ]);


        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->messages()->toArray()
            ], 500);
        }

        $credentials = $request->only(["email", "password"]);
        $user = User::where('email', $credentials['email'])->first();
        if ($user) {
            if (!auth()->attempt($credentials)) {
                $responseMessage = "Invalid username or password";

                return response()->json([
                    "success" => false,
                    "message" => $responseMessage,
                    "error" => $responseMessage
                ], 422);
            }

            $accessToken = auth()->user()->createToken('authToken')->accessToken;
            $responseMessage = "Login Successful";

            return $this->respondWithToken($accessToken, $responseMessage, auth()->user());
        } else {
            $responseMessage = "Sorry, this user does not exist";
            return response()->json([
                "success" => false,
                "message" => $responseMessage,
                "error" => $responseMessage
            ], 422);
        }
    }

    public function viewProfile()
    {
        $responseMessage = "user profile";
        $data = Auth::guard("api")->user();
        return response()->json([
            "success" => true,
            "message" => $responseMessage,
            "data" => $data
        ], 200);
    }



    public function logout()
    {

        //auth()->user()->logout();
        $user = Auth::guard("api")->user()->token();
        $user->revoke();
        $responseMessage = "successfully logged out";
        return response()->json([
            'success' => true,
            'message' => $responseMessage
        ], 200);
    }
}
