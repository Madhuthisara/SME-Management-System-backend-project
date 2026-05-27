<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Services\SupplierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    public function __construct(
        protected SupplierService $supplierService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $businessId = $request->query('business_id');
        $perPage = (int) $request->query('per_page', 15);

        if (!$businessId) {
            return $this->errorResponse('Business ID is required', 422);
        }

        $suppliers = $this->supplierService->getAllSuppliers($businessId, $perPage);
        return $this->successResponse($suppliers, 'Suppliers retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'business_id' => 'required|exists:businesses,id',
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('suppliers')->where(function ($query) use ($request) {
                        return $query->where('business_id', $request->business_id);
                    }),
                ],
                'contact_person' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:50',
                'email' => 'nullable|email|max:255',
                'address' => 'nullable|string|max:1000',
            ]);

            $supplier = $this->supplierService->createSupplier($validatedData);
            return $this->successResponse($supplier, 'Supplier created successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create supplier', 500, [], $e);
        }
    }

    public function update(Request $request): JsonResponse
    {
        $id = $request->query('id');

        if (!$id) {
            return $this->errorResponse('Supplier ID is required', 422);
        }

        $supplier = Supplier::find($id);

        if (!$supplier) {
            return $this->errorResponse('Supplier not found', 404);
        }

        try {
            $validatedData = $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('suppliers')->where(function ($query) use ($supplier) {
                        return $query->where('business_id', $supplier->business_id);
                    })->ignore($supplier->id, 'id'),
                ],
                'contact_person' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:50',
                'email' => 'nullable|email|max:255',
                'address' => 'nullable|string|max:1000',
            ]);

            $updatedSupplier = $this->supplierService->updateSupplier($supplier, $validatedData);
            return $this->successResponse($updatedSupplier, 'Supplier updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update supplier', 500, [], $e);
        }
    }

    public function destroy(Request $request): JsonResponse
    {
        $id = $request->query('id');

        if (!$id) {
            return $this->errorResponse('Supplier ID is required', 422);
        }

        $supplier = Supplier::find($id);

        if (!$supplier) {
            return $this->errorResponse('Supplier not found', 404);
        }

        try {
            $this->supplierService->deleteSupplier($supplier);
            return $this->successResponse([], 'Supplier deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete supplier', 500, [], $e);
        }
    }
}
