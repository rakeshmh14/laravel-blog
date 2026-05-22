<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\GeneratePromptRequest;
use App\Http\Requests\ListImageGenerationsRequest;
use App\Http\Resources\ImageGenerationResource;
use App\Services\OpenAiService;
use Illuminate\Support\Str;

class ImageGenerationController extends Controller
{
    public function __construct(private OpenAiService $openAiService) {}

    /**
     * List image generations
     *
     * Returns paginated history for the authenticated user.
     */
    public function index(ListImageGenerationsRequest $request)
    {
        $user = $request->user();

        $query = $user->imageGenerations();

        if ($request->filled('search')) {
            $query->where('generated_prompt', 'LIKE', '%'.$request->search.'%');
        }

        $allowedSortFields = ['created_at', 'generated_prompt', 'original_filename', 'file_size'];
        $sortField = 'created_at';
        $sortDirection = 'desc';

        if ($request->filled('sort')) {
            $sort = $request->sort;
            if (str_starts_with($sort, '-')) {
                $sortField = substr($sort, 1);
                $sortDirection = 'desc';
            } else {
                $sortField = $sort;
                $sortDirection = 'asc';
            }
        }

        if (! in_array($sortField, $allowedSortFields)) {
            $sortField = 'created_at';
            $sortDirection = 'desc';
        }

        $query->orderBy($sortField, $sortDirection);

        $imageGenerations = $query->paginate(10);

        return ImageGenerationResource::collection($imageGenerations);
    }

    /**
     * Generate prompt from image
     *
     * Upload an image file. OpenAI analyzes it and returns a descriptive prompt
     * suitable for AI image-generation tools.
     */
    public function store(GeneratePromptRequest $request)
    {
        $user = $request->user();
        $image = $request->file('image');

        $originalName = $image->getClientOriginalName();
        $sanitizedName = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
        $extension = $image->getClientOriginalExtension();
        $safeFilename = $sanitizedName.'_'.Str::random(10).'.'.$extension;

        $imagePath = $image->storeAs('uploads/images', $safeFilename, 'public');

        $generatedPrompt = $this->openAiService->generatePromptFromImage($image);

        $imageGeneration = $user->imageGenerations()->create([
            'image_path' => $imagePath,
            'generated_prompt' => $generatedPrompt,
            'original_filename' => $originalName,
            'file_size' => $image->getSize(),
            'mime_type' => $image->getMimeType(),
        ]);

        return new ImageGenerationResource($imageGeneration);
    }
}
