<?php

namespace App\Http\Controllers;

use App\Models\HeroSection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HeroSectionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $heroes = HeroSection::orderBy('order')->get();
        return response()->json([
            'success' => true,
            'message' => 'Hero sections retrieved successfully',
            'data' => $heroes
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'title' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'image_path' => 'required|string',
                'button_text' => 'nullable|string|max:255',
                'button_link' => 'nullable|string|max:255',
                'order' => 'integer',
                'is_active' => 'boolean',
            ]);

            $hero = HeroSection::create($validatedData);
            
            return response()->json([
                'success' => true,
                'message' => 'Hero section created successfully',
                'data' => $hero
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create hero section: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $hero = HeroSection::find($id);

            if (!$hero) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hero section not found'
                ], 404);
            }

            $validatedData = $request->validate([
                'title' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'image_path' => 'required|string',
                'button_text' => 'nullable|string|max:255',
                'button_link' => 'nullable|string|max:255',
                'order' => 'integer',
                'is_active' => 'boolean',
            ]);

            $hero->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Hero section updated successfully',
                'data' => $hero
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update hero section: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $hero = HeroSection::find($id);

            if (!$hero) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hero section not found'
                ], 404);
            }

            $hero->delete();

            return response()->json([
                'success' => true,
                'message' => 'Hero section deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete hero section: ' . $e->getMessage()
            ], 500);
        }
    }
}
