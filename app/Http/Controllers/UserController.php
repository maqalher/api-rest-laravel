<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    //
    public function register(Request $request) {

        // Recoger los datos del usuario por post
        $json = $request->input('json', null);  // obtiene valor o manda null
        $params = json_decode($json); // objeto
        $params_array = json_decode($json, true); // arreglo
        // var_dump($param_array);
        // die();


        if (!empty($params) && !empty($params_array)) {


            // Limpiar datos
            $params_array = array_map('trim', $params_array);

            // Validar datos
            $validate = \Validator::make($params_array, [
            'name' => 'required|alpha',
            'surname' => 'required|alpha',
            'email' => 'required|email|unique:users', // Comprobar si el usario existe ya (duplicado)
            'password' => 'required',
        ]);

            if ($validate->fails()) {
                // Validacion ha fallado
                $data = array(
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'El usario no se ha creado',
                    'errors' => $validate->errors()
                );
            } else {

                // Validacion correcta

                // Cifrar la contrasena
                $pwd = password_hash($params->password, PASSWORD_BCRYPT, ['cost' => 4]); // cirfra la contrasena 4 veces

                // Crear usuario
                $user = new User();
                $user->name = $params_array['name'];
                $user->surname = $params_array['surname'];
                $user->email = $params_array['email'];
                $user->password = $pwd;
                $user->role = 'ROLE_USER';

                // Guardar Usuario
                $user->save();

                $data = array(
                    'status' => 'error',
                    'code' => 200,
                    'message' => 'El usario se ha creado',
                    'user' => $user
                );
            }
        }else {
            $data = array(
                'status' => 'error',
                'code' => 400,
                'message' => 'Los datos enviados no son correctos',
            );
        }





        return response()->json($data, $data['code']);
    }

    public function login(Request $request) {
        return 'desde login de usarios';
    }

}
