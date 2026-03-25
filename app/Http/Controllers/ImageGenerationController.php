<?php

namespace App\Http\Controllers;

use App\Http\Requests\GeneratePromptRequest;
use Illuminate\Http\Request;
use OpenAI;

class ImageGenerationController extends Controller
{
    public function __construct(private OpenAiService $openAiService)
    {
        throw new \Exception('Not implemented');
    }

    public function index()
    {

    }

    public function store(GeneratePromptRequest $request)
    {
        $user = $request->user();
        $image = $request->file('image');

        $originalName = $image->getClientOriginalName();
        $sanitizedName = preg_replace('/[^a-zA-Z0-9', '_', pathinfo($originalName, PATHINFO_FILENAME));
        $extension = $image->guessClientExtension();
        $safeFilename = $sanitizedName. '_'.Str::random(10) . $extension;

        $image->storAs('uploads/images',$safeFilename, 'public');

        $

    }
}
    