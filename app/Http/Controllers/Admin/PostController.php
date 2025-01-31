<?php

namespace App\Http\Controllers\Admin;

use App\Models\Tag;
use App\Models\Category;
use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use App\Http\Requests\StorePostRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:admin.posts.index')->only('index');
        $this->middleware('can:admin.posts.create')->only('create','store');
        $this->middleware('can:admin.posts.edit')->only('edit','update');
        $this->middleware('can:admin.posts.destroy')->only('destroy');
    }

    public function index()
    {
        return view('admin.posts.index');
    }


    public function create()
    {
        $tags = Tag::all();
        $categories = Category::pluck('name','id');
        return view('admin.posts.create',compact('categories','tags'));
    }

    public function store(StorePostRequest $request)
    {
        $post = Post::create($request->all());

        if ($request->file('file')) {
            $url = Storage::put('posts', $request->file('file'));
            $post->image()->create([
                'url' => $url
            ]);
        }



        foreach ($request->tags as $tag_id) {
            $post->tags()->attach($tag_id);
        }
        Cache::flush();

        return redirect()->route('admin.posts.edit', $post);
    }

    public function show(Post $post)
    {
        return view('admin.posts.show', compact('post'));
    }

    public function edit(Post $post)
    {
        $this->authorize('author',$post);
        
        $tags = Tag::all();
        $categories = Category::pluck('name', 'id');
        return view('admin.posts.edit', compact('post', 'categories', 'tags'));
    }

    public function update(Request $request, Post $post)
    {
        $this->authorize('author',$post);
        $post->update($request->all());

        if ($request->file('file')) {
            $url = Storage::put('posts', $request->file('file'));

            if ($post->image) {
                Storage::delete($post->image->url);

                $post->image()->update([
                    'url' => $url
                ]);
            } else {
                $post->image()->create([
                    'url' => $url
                ]);
            }
        }
        
        if ($request->tags) {
            $post->tags()->sync($request->tags);
        }

        Cache::flush();

        return redirect()->route('admin.posts.edit', $post)->with('info', 'El post se actualizó con éxito');
    }

    public function destroy(Post $post)
    {
        $this->authorize('author',$post);
        $post->delete();

        Cache::flush();
        return redirect()->route('admin.posts.index', $post)->with('info', 'Registro eliminado');
    }
}
