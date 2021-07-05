<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
class PostController extends Controller
{
    public function store(){

        //VERIFICA SI EL TOKEN ESTA CADUCADO
        $this->resolveAuthorization();


        /* --------------------------------- */

        $response = http::withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . auth()->user()->accessToken->access_token
        ])->post('http://passport.test/api/v1/posts', [
            'name' => 'Este es un nombre de prueba',
            'slug' => 'Este-es-un-nombre-de-prueba',
            'extract' => 'adaddawd',
            'body' => 'adwadawdwa',
            'category_id' => 1
        ]);

        return $response->json();
    }
}
