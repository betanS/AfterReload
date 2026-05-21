<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    public function destroy(): RedirectResponse
    {
        $user = Auth::user();

        Auth::logout();

        $user->delete();

        return redirect()->route('welcome');
    }
}
