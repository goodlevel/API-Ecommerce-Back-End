<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Service\CartManager;
use OpenApi\Annotations as OA;
use DateTime;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;


class CartController extends AbstractController
{
    private CartManager $cartManager;
    
    public function __construct(CartManager $cartManager) {
        $this->cartManager = $cartManager;
    }

    /**
     * Get user's cart
     * 
     * @Route("/api/cart", name="cart_get", methods={"GET"})
     * @OA\Get(
     *     summary="Get user cart",
     *     tags={"Cart"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="User cart details")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function getCart(UserInterface $user): JsonResponse
    {
        $cart = $this->cartManager->getCart($user->getId());
        return $this->json($this->formatCartResponse($cart));
    }

    /**
     * Add item to cart
     * 
     * @Route("/api/cart/items", name="cart_add_item", methods={"POST"})
     * @OA\Post(
     *     summary="Add item to cart",
     *     tags={"Cart"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"productId", "quantity"},
     *             @OA\Property(property="productId", type="integer", example=1),
     *             @OA\Property(property="quantity", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Item added to cart",
     *         @OA\JsonContent(ref="#/components/schemas/Cart")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error",
     *         ref="#/components/schemas/Error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function addItem(Request $request, UserInterface $user): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            return $this->json(['error' => 'Invalid JSON data'], 400);
        }

        if (!isset($data['productId']) || !isset($data['quantity'])) {
            return $this->json([
                'error' => 'Validation error',
                'message' => 'Both productId and quantity are required'
            ], 400);
        }

        try {
            $this->cartManager->validateCartItem($data['productId'], $data['quantity']);
            
            $cart = $this->cartManager->getCart($user->getId());
            
            $existingItemIndex = $this->findProductIndexInCart($cart, $data['productId']);
            
            if ($existingItemIndex !== null) {
            
                $newQuantity = $data['quantity'];
                $cart->updateItemQuantity($data['productId'], $newQuantity);
            } else {

                $cart->addItem($data['productId'], $data['quantity']);
            }
            
            $this->cartManager->saveCart($cart);

            return $this->json($this->formatCartResponse($cart));
            
        } catch (Exception $e) {
            return $this->json([
                'error' => 'Cart operation failed',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Remove item from cart
     * 
     * @Route("/api/cart/items/{productId}", name="cart_remove_item", methods={"DELETE"})
     * @OA\Delete(
     *     summary="Remove item from cart",
     *     tags={"Cart"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="productId",
     *         in="path",
     *         required=true,
     *         description="Product ID to remove",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Item removed from cart",
     *         @OA\JsonContent(ref="#/components/schemas/Cart")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found in cart"
     *     )
     * )
     */
    public function removeItem(int $productId, UserInterface $user): JsonResponse
    {
        $cart = $this->cartManager->getCart($user->getId());
        $cart->removeItem($productId);
        $this->cartManager->saveCart($cart);

        return $this->json($this->formatCartResponse($cart));
    }

    /**
     * Update item quantity in cart
     * 
     * @Route("/api/cart/items/{productId}", name="cart_update_item", methods={"PATCH"})
     * @OA\Patch(
     *     summary="Update item quantity in cart",
     *     tags={"Cart"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="productId",
     *         in="path",
     *         required=true,
     *         description="Product ID to update",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"quantity"},
     *             @OA\Property(property="quantity", type="integer", example=3)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Quantity updated",
     *         @OA\JsonContent(ref="#/components/schemas/Cart")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error",
     *         ref="#/components/schemas/Error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found in cart"
     *     )
     * )
     */
    public function updateItemQuantity(int $productId, Request $request, UserInterface $user): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            return $this->json(['error' => 'Invalid JSON data'], 400);
        }

        if (!isset($data['quantity'])) {
            return $this->json([
                'error' => 'Validation error',
                'message' => 'Quantity is required'
            ], 400);
        }

        try {
            $this->cartManager->validateCartItem($productId, $data['quantity']);
            
            $cart = $this->cartManager->getCart($user->getId());
            $cart->updateItemQuantity($productId, $data['quantity']);
            $this->cartManager->saveCart($cart);

            return $this->json($this->formatCartResponse($cart));
            
        } catch (Exception $e) {
            return $this->json([
                'error' => 'Cart operation failed',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    private function formatCartResponse(Cart $cart): array
    {
        return [
            'id' => $cart->getId(),
            'userId' => $cart->getUserId(),
            'items' => $cart->getItems(),
            'createdAt' => ($createdAt = $cart->getCreatedAt()) instanceof \DateTime ? $createdAt->format('Y-m-d H:i:s') : null,
            'updatedAt' => ($updatedAt = $cart->getUpdatedAt()) instanceof \DateTime ? $updatedAt->format('Y-m-d H:i:s') : null,
            'totalItems' => array_reduce($cart->getItems(), fn($carry, $item) => $carry + $item['quantity'], 0)
        ];
    }

    /**
     * Get user's wishlist
     * 
     * @Route("/api/wishlist", name="wishlist_get", methods={"GET"})
     * @OA\Get(
     *     summary="Get user wishlist",
     *     tags={"Wishlist"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="User wishlist details",
     *         @OA\JsonContent(ref="#/components/schemas/Wishlist")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function getWishlist(UserInterface $user): JsonResponse
    {
        $wishlist = $this->cartManager->getWishlist($user->getId());
        return $this->json($this->formatWishlistResponse($wishlist));
    }

    
    /**
     * Add item to wishlist
     * 
     * @Route("/api/wishlist/items", name="wishlist_add_item", methods={"POST"})
     * @OA\Post(
     *     summary="Add item to wishlist",
     *     tags={"Wishlist"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"productId"},
     *             @OA\Property(property="productId", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Item added to wishlist",
     *         @OA\JsonContent(ref="#/components/schemas/Wishlist")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error",
     *         ref="#/components/schemas/Error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Product already in wishlist"
     *     )
     * )
     */
    public function addWishlistItem(Request $request, UserInterface $user): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            return $this->json(['error' => 'Invalid JSON data'], 400);
        }

        if (!isset($data['productId'])) {
            return $this->json([
                'error' => 'Validation error',
                'message' => 'productId is required'
            ], 400);
        }

        try {
            $this->cartManager->validateWishlistItem($data['productId']);
            $wishlist = $this->cartManager->getWishlist($user->getId());
            
            $items = $wishlist->getItems();
            $productExists = array_filter($items, fn($item) => $item['productId'] === $data['productId']);
            
            if (empty($productExists)) {
                $wishlist->addItem($data['productId'], 1);
                $this->cartManager->saveWishlist($wishlist);
            } else {
                return $this->json([
                    'error' => 'Duplicate product',
                    'message' => 'This product is already in your wishlist'
                ], 409);
            }

            return $this->json($this->formatWishlistResponse($wishlist));
            
        } catch (Exception $e) {
            return $this->json([
                'error' => 'Wishlist operation failed',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Remove item from wishlist
     * 
     * @Route("/api/wishlist/items/{productId}", name="wishlist_remove_item", methods={"DELETE"})
     * @OA\Delete(
     *     summary="Remove item from wishlist",
     *     tags={"Wishlist"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="productId",
     *         in="path",
     *         required=true,
     *         description="Product ID to remove",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Item removed from wishlist",
     *         @OA\JsonContent(ref="#/components/schemas/Wishlist")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found in wishlist"
     *     )
     * )
     */
    public function removeWishlistItem(int $productId, UserInterface $user): JsonResponse
    {
        $wishlist = $this->cartManager->getWishlist($user->getId());
        $wishlist->removeItem($productId);
        $this->cartManager->saveWishlist($wishlist);

        return $this->json($this->formatWishlistResponse($wishlist));
    }

   
    private function formatWishlistResponse(Cart $wishlist): array
    {
        return [
            'id' => $wishlist->getId(),
            'userId' => $wishlist->getUserId(),
            'items' => $wishlist->getItems(),
            'createdAt' => ($createdAt = $wishlist->getCreatedAt()) instanceof \DateTime ? $createdAt->format('Y-m-d H:i:s') : null,
            'updatedAt' => ($updatedAt = $wishlist->getUpdatedAt()) instanceof \DateTime ? $updatedAt->format('Y-m-d H:i:s') : null,
            'totalItems' => count($wishlist->getItems())
        ];
    }

    private function findProductIndexInCart(Cart $cart, int $productId): ?int
    {
        foreach ($cart->getItems() as $index => $item) {
            if ($item['productId'] === $productId) {
                return $index;
            }
        }
        return null;
    }


}