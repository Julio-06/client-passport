<?php

namespace App\Traits;

use Illuminate\Support\Facades\Http;

trait Token {
    public function getAccessToken($user, $service){
        $response =  Http::withHeaders([
            'Accept' => 'application/json'
        ])->post('http://passport.test/oauth/token', [
            'grant_type' => 'password',
            'client_id' => config('services.apiPassport.client_id'),
            'client_secret' => config('services.apiPassport.client_secret'),
            'username' => request('email'),
            'password' => request('password')
        ]);

        $token = $response->json();

        $user->accessToken()->create([
            'service_id' => $service['data']['id'],
            'access_token' => $token['access_token'],
            'refresh_token' => $token['refresh_token'],
            'expires_at' => now()->addSecond($token['expires_in']),
        ]);
    }
}