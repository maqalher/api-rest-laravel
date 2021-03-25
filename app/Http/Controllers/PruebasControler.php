<?php

namespace App\Http\Controllers;

use App\Category;
use App\Post;
use Illuminate\Http\Request;

class PruebasControler extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        //
    }
    public function index(Request $request)
    {
        //
        return 'desde index';
    }

    public function test(Request $request)
    {
        $posts = Post::all();

        foreach($posts as $post){
            echo "<h1>". $post->title ."</h1>";
            echo "<span>  {$post->user->name } -  {$post->category->name } </span>";
            echo "<p>". $post->content ."</p>";
            echo "<hr>";
        }

        $categories = Category::all();
        foreach($categories as $category){
            echo "<h2>". $category->name ."</h2>";
            foreach($category->posts as $post){
                echo "<h1>". $post->title ."</h1>";
                echo "<span>  {$post->user->name } -  {$post->category->name } </span>";
                echo "<p>". $post->content ."</p>";
                echo "<hr>";
            }
        }

        // dd($categories);
        die();
    }
}
