<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function register(Request $request) {
        // Validacion de los datos
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed' // La validacion confirmed espera que en la peticion se envie un campo con el sufijo `_confirmation` (password_confirmation)
        ]);

        // Alta del usuario
        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);

        // Respues de la API
        $user->save();

        // Response permite obtener respuestas especificas de la API (HTTP_CREATED retorna el codigo de estado 201)
        return response($user, Response::HTTP_CREATED);
    }

    public function login(Request $request) {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required']
        ]);

        // Auth::attempt() recibe un arreglo como parametro para validar las credenciales de login
        if (Auth::attempt($credentials)) {
            $user = Auth::user(); // Si las credenciales de login son correctas, se autentica el usuario en la aplicacion
            $token = $user->createToken('token')->plainTextToken; // Se genera un token a traves de createToken()
            $cookie = cookie('cookie_token', $token, 60 * 24); // Se genera una cookie de sesion (nombre de la cookie, token, expiracion de la cookie)

            return response([
                'token' => $token
            ], Response::HTTP_OK)->withCookie($cookie);
        } else {
            return response(['message' => 'Credenciales invalidas'], Response::HTTP_UNAUTHORIZED);
        }
    }

    public function userProfile(Request $request) {
        return response()->json([
            'message' => 'User Profile OK',
            'user' => auth()->user()
        ], Response::HTTP_OK);
    }

    public function logout(Request $request) {
        $user = $request->user();
        $user->currentAccessToken()->delete(); // Se elimina el token actual generado por el login en el sistema de autenticacion

        $cookie = Cookie::forget('cookie_token'); // Se elimina la cookie  generada por el login, en el navegador del usuario
        return response(['message' => 'Cierre de sesion OK'], Response::HTTP_OK)->withCookie($cookie);
    }

    public function allUsers(Request $request) {
        $users = User::all();

        return response()->json(['users' => $users]);
    }
}
