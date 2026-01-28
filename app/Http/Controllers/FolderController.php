<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use Illuminate\Http\Request;

class FolderController extends Controller
{
    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);

        Folder::create([
            'name' => $request->name,
            'user_id' => auth()->id()
        ]);

        return back()->with('success', 'Folder created.');
    }

    public function destroy(Folder $folder)
    {
        abort_if($folder->user_id !== auth()->id(), 403);

        // Optional: Move feeds to null folder or delete them?
        // Let's just unlink them (set folder_id null)
        $folder->feeds()->update(['folder_id' => null]);
        $folder->delete();

        return back()->with('success', 'Folder deleted.');
    }

    public function clearArticles(Folder $folder)
    {
        abort_if($folder->user_id !== auth()->id(), 403);

        $feedIds = $folder->feeds->pluck('id');

        $articlesToKeep = \App\Models\Article::whereIn('feed_id', $feedIds)
            ->whereHas('users', function($q) {
                $q->where('user_id', auth()->id())
                  ->where(function($sq) {
                      $sq->where('is_saved', true)->orWhere('is_favorite', true);
                  });
            })
            ->pluck('id');

        \App\Models\Article::whereIn('feed_id', $feedIds)
            ->whereNotIn('id', $articlesToKeep)
            ->delete();

        return redirect()->route('folder.show', $folder)->with('success', 'Folder articles cleared (except saved/favorites).');
    }
}
