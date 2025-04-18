<?php

namespace App\Service;

use App\Entity\Cart;
use Exception;
use RuntimeException;

class CartManager
{
    private JsonStorageService $storage;

    /**
     * CartManager constructor.
     * @param JsonStorageService $storage
     */
    public function __construct(JsonStorageService $storage)
    {
        $this->storage = $storage;
    }

    /**
     * @param int $userId
     * @return Cart
     */
    public function getCart(int $userId): Cart
    {
        return $this->getUserCollection('carts.json', $userId);
    }

    /**
     * @param int $userId
     * @return Cart
     */
    public function getWishlist(int $userId): Cart
    {
        return $this->getUserCollection('wishlists.json', $userId);
    }

    /**
     * @param Cart $cart
     */

    public function saveCart(Cart $cart): void
    {
        $this->saveUserCollection('carts.json', $cart);
    }

    /**
     * @param Cart $wishlist
     */
    public function saveWishlist(Cart $wishlist): void
    {
        $this->saveUserCollection('wishlists.json', $wishlist);
    }

    /**
     * @param string $filename
     * @param int $userId
     * @return Cart
     */
    private function getUserCollection(string $filename, int $userId): Cart
    {
        $collections = $this->storage->read($filename);
        $collectionData = current(array_filter($collections, fn($c) => $c['userId'] === $userId));

        if (!$collectionData) {
            return $this->createUserCollection($filename, $userId);
        }

        $collection = new Cart($collectionData['userId']);
        $collection->setId($collectionData['id']);
        foreach ($collectionData['items'] as $item) {
            $quantity = $filename === 'wishlists.json' ? 1 : $item['quantity'];
            $collection->addItem($item['productId'], $quantity);
        }

        return $collection;
    }

    /**
     * @param string $filename
     * @param Cart $collection
     */
    private function saveUserCollection(string $filename, Cart $collection): void
    {
        $collections = $this->storage->read($filename);
        
        $collectionData = [
            'id' => $collection->getId(),
            'userId' => $collection->getUserId(),
            'items' => $collection->getItems(),
            'createdAt' => ($createdAt = $collection->getCreatedAt()) instanceof \DateTime ? $createdAt->format('Y-m-d H:i:s') : null,
            'updatedAt' => ($updatedAt = $collection->getUpdatedAt()) instanceof \DateTime ? $updatedAt->format('Y-m-d H:i:s') : null
        ];

        $index = array_search($collection->getId(), array_column($collections, 'id'));
        if ($index !== false) {
            $collections[$index] = $collectionData;
        } else {
            $collections[] = $collectionData;
        }

        $this->storage->write($filename, $collections);
    }

    /**
     * @param string $filename
     * @param int $userId
     * @return Cart
     */
    private function createUserCollection(string $filename, int $userId): Cart
    {
        $collections = $this->storage->read($filename);
        $collection = new Cart($userId);
        $collection->setId($this->storage->getNextId($filename));
        $this->saveUserCollection($filename, $collection);
        return $collection;
    }

    /**
     * @param int $productId
     * @param int $quantity
     * @throws RuntimeException
     */
    public function validateCartItem(int $productId, int $quantity): void
    {
        $products = $this->storage->read('products.json');
        $product = current(array_filter($products, fn($p) => $p['id'] === $productId));

        if (!$product) {
            throw new RuntimeException('Product not found');
        }

        if ($product['inventoryStatus'] === 'OUTOFSTOCK') {
            throw new RuntimeException('This product is out of stock');
        }

        if ($quantity <= 0) {
            throw new RuntimeException('Quantity must be greater than zero');
        }

        if ($product['quantity'] < $quantity) {
            throw new RuntimeException(sprintf(
                'Insufficient stock. Available quantity: %d',
                $product['quantity']
            ));
        }
    }

    /**
     * @param int $productId
     * @throws RuntimeException
     */
    public function validateWishlistItem(int $productId): void
    {
        $products = $this->storage->read('products.json');
        $product = current(array_filter($products, fn($p) => $p['id'] === $productId));

        if (!$product) {
            throw new RuntimeException('Product not found');
        }
    }
}