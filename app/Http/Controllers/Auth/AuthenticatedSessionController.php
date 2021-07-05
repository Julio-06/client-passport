<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\Http;
use App\Traits\Token;

class AuthenticatedSessionController extends Controller
{
    use Token;
    /**
     * Display the login view.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     *
     * @param  \App\Http\Requests\Auth\LoginRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        //MODIFICAMOS ESTE METODO PARA AUTENTIFICAR EL USUARIO CON EL API Y NO CON LA APLICACIÓN
        /* $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(RouteServiceProvider::HOME); */

        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string'
        ]);

        $response =  Http::withHeaders([
            'Accept' => 'application/json'
        ])->post('http://passport.test/api/login', [
            'email' => $request->email,
            'password' => $request->password
        ]);

        if($response->status() == 404){
            return back()->withErrors('CREDENCIALES NO VALIDAS');
        }

        $service = $response->json();

        //BUSCARA EL REGISTRO EN LA BASE DE DATOS DE LA APLICACIÓN EN CASO QUE NO EXISTA LO CREARA
        //SI EL REGISTRO EXISTE LO ACTUALIZARA CON LOS DATOS QUE VIENE DEL API ESTO NOS AYUDARA A VALIDAR
        //SI EL USUARIO PUDO HABER ACTUALIZADOS SUS DATOS.
        $user = User::updateOrcreate([
            'email' => $request->email
        ], $service['data']);

        //VALIDAMOS SI EL USUARIO CUANTA CON UN ACCESS_TOKEN SI RETORNA NULL SE CONSIDERA FALSO Y ENTRA EN LA CONDICIONAL
        if(!$user->accessToken){
            //LUEGO DE VALIDAR QUE EL USUARIO EXISTE REALIZAMOS OTRA PETICIÓN PARA OBTENER UN TOKEN
            $this->getAccessToken($user, $service);
        }

        Auth::login($user, $request->remember);

        return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * Destroy an authenticated session.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
