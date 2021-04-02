<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Response;
use Illuminate\Http\Request;

class UserController extends Controller
{
    //
    public function register(Request $request)
    {

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
                    'code' => 400,
                    'message' => 'El usario no se ha creado',
                    'errors' => $validate->errors()
                );
            } else {

                // Validacion correcta

                // Cifrar la contrasena
                // $pwd = password_hash($params->password, PASSWORD_BCRYPT, ['cost' => 4]); // cirfra la contrasena 4 veces
                $pwd = hash('sha256', $params->password);

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
                    'status' => 'success',
                    'code' => 200,
                    'message' => 'El usuario se ha creado',
                    'user' => $user
                );
            }
        } else {
            $data = array(
                'status' => 'error',
                'code' => 400,
                'message' => 'Los datos enviados no son correctos',
            );
        }

        return response()->json($data, $data['code']);
    }

    public function login(Request $request)
    {

        $jwtAuth = new \JwtAuth();

        // Recibir datos por POST
        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);

        // Validar datos
        $validate = \Validator::make($params_array, [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validate->fails()) {
            // Validacion ha fallado
            $signup = array(
                'status' => 'error',
                'code' => 404,
                'message' => 'El usuario no se ha podido identificar',
                'errors' => $validate->errors()
            );
        } else {
            // Cifrar la password
            $pwd = hash('sha256', $params->password);

            // Devolver token o datos
            $signup = $jwtAuth->signup($params->email, $pwd);

            if (!empty($params->gettoken)) {
                $signup = $jwtAuth->signup($params->email, $pwd, true);
            }
        }

        return response()->json($signup, 200);
    }

    public function update(Request $request)
    {

        // comprobar si el usuario esta identificado
        $token = $request->header('Authorization');
        $jwtAuth = new \JwtAuth();
        $checkToken = $jwtAuth->checkToken($token);

        // dd($checkToken);

        // Recoger los datos por post
        $json = $request->input('json', null);
        $params_array = json_decode($json, true);

        if ($checkToken && !empty($params_array)) {
            // Actualizar usuario

            // Sacar usuario identificado
            $user = $jwtAuth->checkToken($token, true);

            // Validar datos
            $validate = \Validator::make($params_array, [
                'name' => 'required|alpha',
                'surname' => 'required|alpha',
                'email' => 'required|email|unique:users,' . $user->sub, // pasa el id del usuario
            ]);

            // Quitar los campos que no quiero actualizar
            unset($params_array['id']);
            unset($params_array['role']);
            unset($params_array['password']);
            unset($params_array['created_at']);
            unset($params_array['remember_token']);

            // Actualizar usuario en bd
            $user_update = User::where('id', $user->sub)->update($params_array);

            // Devolver array con resultado
            $data = array(
                'code' => 200,
                'status' => 'success',
                'user' => $user,
                'changes' => $params_array
            );
        } else {
            $data = array(
                'code' => 400,
                'status' => 'error',
                'message' => 'El usuario no esta identificado'
            );
        }

        return response()->json($data, $data['code']);
    }

    public function upload(Request $request)
    {

        // Recoger datos de la peticion
        $image = $request->file('file0');


        // Validacion de imagen
        $validate = \Validator::make($request->all(), [
            'file0' => 'required|image|mimes:png,jpg,jpeg,gif'
        ]);
        // dd($request);

        // Guardar imagen
        if (!$image || $validate->fails()) {

            $data = array(
                'code' => 400,
                'status' => 'error',
                'message' => 'Error al subir imagen'
            );
        } else {
            $image_name = time() . $image->getClientOriginalName(); // crea el nombre de la imagen para que sea unica
            \Storage::disk('users')->put($image_name, \File::get($image)); // agrega la imagen al server en la carpeta users

            $data = array(
                'code' => 200,
                'status' => 'success',
                'image' => $image_name
            );
        }

        return response()->json($data, $data['code']);
    }

    public function getImage($filename)
    {
        $isset = \Storage::disk('users')->exists($filename);

        if ($isset) {
            $file = \Storage::disk('users')->get($filename);
            return new Response($file, 200);
        } else {
            $data = array(
                'code' => 404,
                'status' => 'error',
                'message' => 'La imagen no existe'
            );
        }

        return response()->json($data, $data['code']);
    }

    public function detail($id)
    {
        $user = User::find($id);

        if (is_object($user)) {
            $data = array(
                'code' => 200,
                'status' => 'success',
                'user' => $user
            );
        } else {
            $data = array(
                'code' => 404,
                'status' => 'error',
                'message' => 'El usuario no existe'
            );
        }

        return response()->json($data, $data['code']);
    }
}
