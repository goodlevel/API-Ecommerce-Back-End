<?php

namespace App\Entity;

class Cart
{
    private int $id;
    private int $userId;
    private array $items;
    private \DateTime $createdAt;
    private \DateTime $updatedAt;

    public function __construct(int $userId)
    {
        $this->userId = $userId;
        $this->items = [];
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    /**
     * @param int $id
     */
    public function getId(): int { 
        return $this->id; 
    }

    /**
     * Set the value of id
     *
     * @return  self
     */ 
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }
    
    /**
     * @param int $userId
     */
    public function getUserId(): int { 
        return $this->userId; 
    }

    /**
     * @param int $userId
     * @return array
     */
    public function getItems(): array {
        return is_array($this->items) ? $this->items : [];
    }

    /**
     * @param \DateTime $createdAt
     */
    public function getCreatedAt(): \DateTime { 
        return $this->createdAt; 
    }

    /**
     * @param \DateTime $updatedAt
     */
    public function getUpdatedAt(): \DateTime { 
        return $this->updatedAt; 
    }

    

    /**
     * @param int $productId
     * @param int $quantity
     */
    public function addItem(int $productId, int $quantity): void {
        $this->items[] = ['productId' => $productId, 'quantity' => $quantity];
        $this->updatedAt = new \DateTime();
    }

    /**
     * @param int $productId
     */
    public function removeItem(int $productId): void {
        $this->items = array_filter($this->items, fn($item) => $item['productId'] !== $productId);
        $this->updatedAt = new \DateTime();
    }

    /**
     * @param int $productId
     * @param int $quantity
     */
    public function updateItemQuantity(int $productId, int $quantity): void {
        foreach ($this->items as &$item) {
            if ($item['productId'] === $productId) {
                $item['quantity'] = $quantity;
                $this->updatedAt = new \DateTime();
                break;
            }
        }
    }

}