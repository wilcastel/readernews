<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWordPressSiteRequest;
use App\Http\Requests\UpdateWordPressSiteRequest;
use App\Models\WordPressSite;
use App\Services\WordPressService;
use Illuminate\Http\JsonResponse;

class WordPressSiteController extends Controller
{
    public function index(): JsonResponse
    {
        $sites = auth()->user()
            ->wordPressSites()
            ->orderBy('name')
            ->get();

        return response()->json($sites);
    }

    public function store(StoreWordPressSiteRequest $request): JsonResponse
    {
        $site = auth()->user()->wordPressSites()->create($request->validated());

        try {
            (new WordPressService($site))->testConnection();
        } catch (\Throwable) {
            // Connection test is best-effort; site is still created
        }

        return response()->json($site, 201);
    }

    public function update(UpdateWordPressSiteRequest $request, WordPressSite $wordPressSite): JsonResponse
    {
        if ($wordPressSite->user_id !== auth()->id()) {
            abort(403);
        }

        $wordPressSite->update($request->validated());

        return response()->json($wordPressSite->fresh());
    }

    public function destroy(WordPressSite $wordPressSite): JsonResponse
    {
        if ($wordPressSite->user_id !== auth()->id()) {
            abort(403);
        }

        $wordPressSite->delete();

        return response()->json(null, 204);
    }

    public function testConnection(WordPressSite $wordPressSite): JsonResponse
    {
        if ($wordPressSite->user_id !== auth()->id()) {
            abort(403);
        }

        try {
            $success = (new WordPressService($wordPressSite))->testConnection();
            $message = $success ? 'Connection successful.' : 'Authentication failed. Check your credentials.';
        } catch (\Throwable $e) {
            $success = false;
            $message = 'Connection failed: ' . $e->getMessage();
        }

        return response()->json(['success' => $success, 'message' => $message]);
    }
}
