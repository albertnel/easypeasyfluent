<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    public function seedUsers(Request $request)
    {
        $count = $request->input('count', 10); // Default to 10 users if no count is provided
        runBackgroundJob('App\Services\UserSeederService', 'seedUsers', [$count]);

        return response()->json(['message' => "Seeding $count users in the background."]);
    }
}