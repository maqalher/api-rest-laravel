<?php

namespace App\Http\Controllers;

use App\Helpers\JwtAuth;
use App\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PostController extends Controller
{
    public function __construct()
    {
        $this->middleware('api.auth', ['except' => ['index', 'show', 'getIamge', 'getPostsByCategory', 'getPostsByUser']]);
    }

    public function index()
    {
        $posts = Post::all()->load('category');

        return response()->json([
            'code' => 200,
            'status' => 'success',
            'posts' => $posts
        ], 200);
    }

    public function show($id)
    {
        $post = Post::find($id)->load('category');

        if (is_object($post)) {
            $data = [
                'code' => 200,
                'status' => 'success',
                'posts' => $post
            ];
        } else {
            $data = [
                'code' => 400,
                'status' => 'error',
                'message' => 'La entrada no existe'
            ];
        }

        return response()->json($data, $data['code']);
    }

    public function store(Request $request)
    {
        // Recoger los datos pos Post
        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);

        if (!empty($params_array)) {
            // conseguir el usuario identificado
            $user = $this->getIdentity($request);

            // Validar los datos
            $validate = \Validator::make($params_array, [
                'title' => 'required',
                'content' => 'required',
                'category_id' => 'required',
                'image' => 'required'
            ]);

            if ($validate->fails()) {
                $data = [
                    'code' => 400,
                    'status' => 'error',
                    'message' => 'No se ha guardado el post, faltan datos'
                ];
            } else {
                // Guardar los datos
                $post = new Post();
                $post->user_id = $user->sub;
                $post->category_id = $params->category_id;
                $post->title = $params->title;
                $post->content = $params->content;
                $post->image = $params->image;
                $post->save();

                $data = [
                    'code' => 200,
                    'status' => 'success',
                    'post' => $post
                ];
            }
        } else {
            $data = [
                'code' => 400,
                'status' => 'error',
                'message' => 'Envia los datos correctamente'
            ];
        }

        return response()->json($data, $data['code']);
    }

    public function update($id, Request $request)
    {
        // Recoger los datos por post
        $json = $request->input('json', null);
        $params_array = json_decode($json, true);


        if (!empty($params_array)) {
            // Validar datos
            $validate = \Validator::make($params_array, [
                'title' => 'required',
                'content' => 'required',
                'category_id' => 'required',
            ]);

            if ($validate->fails()) {
                $data = [
                    'code' => 400,
                    'status' => 'error',
                    'message' => 'Envia los datos correctamente'
                ];
            } else {
                // Eliminar lo que no queremos actualizar
                unset($params_array['id']);
                unset($params_array['user_id']);
                unset($params_array['created_id']);
                unset($params_array['user']);

                // conseguir el usuario identificado
                $user = $this->getIdentity($request);

                // Buscar el registro a actualizar
                $post = Post::where('id', $id)->where('user_id', $user->sub)->first();


                if(!empty($post) && is_object($post)){
                    // Actualziar el registro en concreto
                    $post->update($params_array);

                    $data = [
                        'code' => 200,
                        'status' => 'success',
                        'post' => $post,
                        'changes' => $params_array
                    ];
                }else {
                    $data = [
                        'code' => 400,
                        'status' => 'error',
                        'message' => 'No tienes permisos para modificar'
                    ];
                }

                // $post = Post::where('id', $id)->update($params_array); // 1 o 0

                // $where = [
                //     'id' => $id,
                //     'user_id' => $user->sub
                // ]; // necesita agregar fillable
                // $post = Post::where('id', $id)
                //                 ->where('user_id', $user->sub)
                //                 ->updateOrCreate($params_array); // todos los datos
                // $post = Post::updateOrCreate($where, $params_array);


            }
        } else {
            $data = [
                'code' => 400,
                'status' => 'error',
                'message' => 'Envia los datos correctamente'
            ];
        }

        return response()->json($data, $data['code']);
    }

    public function destroy($id, Request $request)
    {

        // conseguir el usuario identificado
        $user = $this->getIdentity($request);

        // Conseguir el registro
        // $post = Post::find($id);
        $post = Post::where('id', $id)->where('user_id', $user->sub)->first();

        if (!empty($post)) {
            // Borarlo
            $post->delete();

            // Respuesta
            $data = [
                'code' => 200,
                'status' => 'success',
                'post' => $post,
            ];
        } else {
            $data = [
                'code' => 404,
                'status' => 'error',
                'message' => 'El post no existe'
            ];
        }

        return response()->json($data, $data['code']);
    }

    private function getIdentity($request){
        $jwtAuth = new JwtAuth();
        $token = $request->header('Authorization', null);
        $user = $jwtAuth->checkToken($token, true);

        return $user;
    }

    public function upload(Request $request){
        // conseguir el usuario identificado
        $user = $this->getIdentity($request);

        // Recoger la imagen de la peticion
        $image = $request->file('file0');

        // Validar imagen
        $validate = \Validator::make($request->all(), [
            'file0' => 'required|image|mimes:png,jpg,jpeg,gif'
        ]);

        //Guardar la imagen
        if(!$image || $validate->fails()){
            $data = [
                'code' => 404,
                'status' => 'error',
                'message' => 'Error al subir la imagen'
            ];
        }else {
            $image_name = time().$image->getClientOriginalName();

            \Storage::disk('images')->put($image_name, \File::get($image));

            $data = [
                'code' => 200,
                'status' => 'success',
                'image' => $image_name,
            ];
        }

        return response()->json($data, $data['code']);
    }

    public function getIamge($filename){
        // Comprobar si existe el fichero
        $isset = \Storage::disk('images')->exists($filename);

        if($isset){
            // Conseguir la imagen
            $file = \Storage::disk('images')->get($filename);

            // Devolver la imagen
            return new Response($file, 200);
        }else{
            $data = [
                'code' => 404,
                'status' => 'error',
                'message' => 'La imagen no existe'
            ];
        }

        return response()->json($data, $data['code']);
    }

    public function getPostsByCategory($id){
        $posts = Post::where('category_id', $id)->get();

        return response()->json([
            'status' => 'success',
            'posts' => $posts
        ], 200);
    }

    public function getPostsByUser($id){
        $posts = Post::where('user_id', $id)->get();

        return response()->json([
            'status' => 'success',
            'posts' => $posts
        ], 200);
    }
}
