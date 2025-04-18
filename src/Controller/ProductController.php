<?php

namespace App\Controller;


use App\Service\JsonStorageService;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;


class ProductController extends AbstractController
{
    private JsonStorageService $storage;

    public function __construct(JsonStorageService $storage)
    {
        $this->storage = $storage;
    }

    /**
     * List all products
     * 
     * @Route("/api/products", name="products_list", methods={"GET"})
     * @OA\Get(
     *     summary="Get all products",
     *     security={{"bearerAuth": {}}},
     *     tags={"Products"},
     *     @OA\Response(
     *         response=200,
     *         description="List of products",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Product")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $products = $this->storage->read('products.json');
        return $this->json($products);
    }
    

    /**
     * Get product details
     * 
     * @Route("/api/products/{id}", name="product_show", methods={"GET"})
     * @OA\Get(
     *     summary="Get product by ID",
     *     tags={"Products"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Product ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product details",
     *         @OA\JsonContent(ref="#/components/schemas/Product")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found"
     *     )
     * )
     */
    public function show(int $id): JsonResponse
    {
        $products = $this->storage->read('products.json');
        $product = array_filter($products, fn($p) => $p['id'] === $id);

        if (empty($product)) {
            return $this->json(['message' => 'Product not found'], 404);
        }

        return $this->json(array_values($product)[0]);
    }

    /**
     * Create new product
     * 
     * @Route("/api/products", name="product_create", methods={"POST"})
     * @OA\Post(
     *     summary="Create a new product",
     *     tags={"Products"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "price"},
     *             @OA\Property(property="name", type="string", example="Smartphone"),
     *             @OA\Property(property="price", type="number", format="float", example=599.99),
     *             @OA\Property(property="quantity", type="integer", example=10),
     *             @OA\Property(property="inventoryStatus", type="string", example="INSTOCK"),
     *             @OA\Property(property="description", type="string", example="Latest model smartphone")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Product created",
     *         @OA\JsonContent(ref="#/components/schemas/Product")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error",
     *         ref="#/components/schemas/Error"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin access required"
     *     )
     * )
     */
    public function create(Request $request): JsonResponse {

        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json([
                'error' => 'Forbidden',
                'message' => 'You need administrator privileges to add products'
            ], 403);
        }

        $data = json_decode($request->getContent(), true);
        
        if ($data === null) {
            return $this->json(['error' => 'Invalid JSON data'], 400);
        }

        $requiredFields = ['name', 'price'];
        $missingFields = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            return $this->json([
                'error' => 'Missing required fields',
                'missing_fields' => $missingFields
            ], 400);
        }

        if (isset($data['inventoryStatus'])) {
            $validStatuses = ['INSTOCK', 'LOWSTOCK', 'OUTOFSTOCK'];
            if (!in_array($data['inventoryStatus'], $validStatuses)) {
                return $this->json([
                    'error' => 'Invalid inventoryStatus',
                    'message' => 'Must be one of: INSTOCK, LOWSTOCK, OUTOFSTOCK'
                ], 400);
            }
        }

        $errors = [];
        
        if (!is_string($data['name'])) {
            $errors['name'] = 'Name must be a string';
        }
        

        if (!is_numeric($data['price'])) {
            $errors['price'] = 'Price must be a number';
        }

        if (isset($data['rating']) && !is_numeric($data['rating'])) {
            $errors['rating'] = 'Rating must be a number';
        }
        
        if (isset($data['quantity']) && !is_numeric($data['quantity'])) {
            $errors['quantity'] = 'Quantity must be a number';
        }

        if (isset($data['shellId']) && !is_numeric($data['shellId'])) {
            $errors['shellId'] = 'Shell ID must be a number';
        }

        if (!empty($errors)) {
            return $this->json([
                'error' => 'Validation failed',
                'errors' => $errors
            ], 400);
        }

        try {
            $product = [
                'id' => $this->storage->getNextId('products.json'),
                'code' => $data['code'] ?? '',
                'name' => $data['name'],
                'description' => $data['description'] ?? '',
                'image' => $data['image'] ?? null,
                'category' => $data['category'] ?? null,
                'price' => (float) $data['price'],
                'quantity' => (int) ($data['quantity'] ?? 0),
                'internalReference' => $data['internalReference'] ?? null,
                'shellId' => (int) ($data['shellId'] ?? 0),
                'inventoryStatus' => $data['inventoryStatus'] ?? 'INSTOCK',
                'rating' => (float) ($data['rating'] ?? 0),
                'createdAt' => (new \DateTime())->format('Y-m-d H:i:s'),
                'updatedAt' => (new \DateTime())->format('Y-m-d H:i:s'),
            ];

            $products = $this->storage->read('products.json');
            $products[] = $product;
            $this->storage->write('products.json', $products);

            return $this->json($product, 201);
            
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to create product',
                'message' => $e->getMessage()
            ], 500);
        }
    }

        
    /**
     * Update product
     * 
     * @Route("/api/products/{id}", name="product_update", methods={"PATCH"})
     * @OA\Patch(
     *     summary="Update product",
     *     tags={"Products"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Product ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Updated Product Name"),
     *             @OA\Property(property="price", type="number", format="float", example=699.99),
     *             @OA\Property(property="quantity", type="integer", example=5)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product updated",
     *         @OA\JsonContent(ref="#/components/schemas/Product")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error",
     *         ref="#/components/schemas/Error"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin access required"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found"
     *     )
     * )
     */
    public function update(int $id, Request $request): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json([
                'error' => 'Forbidden',
                'message' => 'You need administrator privileges to update products'
            ], 403);
        }

        $products = $this->storage->read('products.json');
        $index = array_search($id, array_column($products, 'id'));

        if ($index === false) {
            return $this->json(['message' => 'Product not found'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            return $this->json(['error' => 'Invalid JSON data'], 400);
        }

        $errors = [];
        
        if (isset($data['name']) && !is_string($data['name'])) {
            $errors['name'] = 'Must be a string';
        }
        
        if (isset($data['price']) && !is_numeric($data['price'])) {
            $errors['price'] = 'Must be a number';
        }
        
        if (isset($data['quantity']) && !is_numeric($data['quantity'])) {
            $errors['quantity'] = 'Must be a number';
        }

        if (isset($data['inventoryStatus'])) {
            $validStatuses = ['INSTOCK', 'LOWSTOCK', 'OUTOFSTOCK'];
            if (!in_array($data['inventoryStatus'], $validStatuses)) {
                $errors['inventoryStatus'] = 'Must be one of: ' . implode(', ', $validStatuses);
            }
        }

        if (!empty($errors)) {
            return $this->json([
                'error' => 'Validation failed',
                'errors' => $errors
            ], 400);
        }

        $updateData = [];
        
        $allowedFields = [
            'name', 'code', 'description', 'image', 'category',
            'price', 'quantity', 'internalReference', 'shellId',
            'inventoryStatus', 'rating'
        ];
        
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field];
            }
        }

        $products[$index] = array_merge(
            $products[$index], 
            $updateData,
            ['updatedAt' => (new \DateTime())->format('Y-m-d H:i:s')]
        );
        
        $this->storage->write('products.json', $products);

        return $this->json($products[$index]);
    }

    /**
     * Delete product
     * 
     * @Route("/api/products/{id}", name="product_delete", methods={"DELETE"})
     * @OA\Delete(
     *     summary="Delete product",
     *     tags={"Products"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Product ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product deleted",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Product deleted")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin access required"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found"
     *     )
     * )
     */
    public function delete(int $id): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json([
                'error' => 'Forbidden',
                'message' => 'You need administrator privileges to delete products'
            ], 403);
        }

        $products = $this->storage->read('products.json');
        $filtered = array_filter($products, fn($p) => $p['id'] !== $id);

        if (count($products) === count($filtered)) {
            return $this->json(['message' => 'Product not found'], 404);
        }

        $this->storage->write('products.json', array_values($filtered));
        return $this->json(['message' => 'Product deleted']);
    }
}