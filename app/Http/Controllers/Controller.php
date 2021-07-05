<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Http;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    //ESTE METODO NOS PERMITIRA REFRESCAR EL TOKEN CADA VEZ QUE CADUQUE
    public function resolveAuthorization(){
        if(auth()->user()->accessToken->expires_at <= now()){
            $response =  Http::withHeaders([
                'Accept' => 'application/json'
            ])->post('http://passport.test/oauth/token', [
                'grant_type' => 'refresh_token',
                'refresh_token' => auth()->user()->accessToken->refresh_token,
                'client_id' => config('services.apiPassport.client_id'),
                'client_secret' => config('services.apiPassport.client_secret'),
            ]);
    
            $token = $response->json();
    
            auth()->user()->accessToken->update([
                'access_token' => $token['access_token'],
                'refresh_token' => $token['refresh_token'],
                'expires_at' => now()->addSecond($token['expires_in']),
            ]);
        }
    }
}
