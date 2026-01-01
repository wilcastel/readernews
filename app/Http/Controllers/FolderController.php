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
}
