<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

use Illuminate\Support\Facades\Http;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $response = Http::withHeaders(['Accept' => 'application/json'])->post('http://passport.test/api/v1/register', $request->all());

        if($response->status() == 422){
            return back()->withErrors($response->json()['errors']);
        }

        $service =$response->json();

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        $response =  Http::withHeaders([
            'Accept' => 'application/json'
        ])->post('http://passport.test/oauth/token', [
            'grant_type' => 'password',
            'client_id' => config('services.apiPassport.client_id'),
            'client_secret' => config('services.apiPassport.client_secret'),
            'username' => $request->email,
            'password' => $request->password
        ]);

        $token = $response->json();

        $user->accessToken()->create([
            'service_id' => $service['data']['id'],
            'access_token' => $token['access_token'],
            'refresh_token' => $token['refresh_token'],
            'expires_at' => now()->addSecond($token['expires_in']),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(RouteServiceProvider::HOME);
    }
}
