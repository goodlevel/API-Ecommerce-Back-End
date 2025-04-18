<?php

namespace App\Entity;

use DateTime;

class Product
{
    private int $id;
    private string $code;
    private string $name;
    private string $description;
    private string $image;
    private string $category;
    private float $price;
    private int $quantity;
    private string $internalReference;
    private int $shellId;
    private string $inventoryStatus;
    private float $rating;
    private DateTime $createdAt;
    private DateTime $updatedAt;

    

    /**
     * Get the value of id
     */ 
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the value of code
     */ 
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set the value of code
     *
     * @return  self
     */ 
    public function setCode(string $code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get the value of name
     */ 
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the value of name
     *
     * @return  self
     */ 
    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the value of description
     */ 
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set the value of description
     *
     * @return  self
     */ 
    public function setDescription(string $description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get the value of image
     */ 
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Set the value of image
     *
     * @return  self
     */ 
    public function setImage(string $image)
    {
        $this->image = $image;

        return $this;
    }

    /**
     * Get the value of category
     */ 
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Set the value of category
     *
     * @return  self
     */ 
    public function setCategory(string $category)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get the value of price
     */ 
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set the value of price
     *
     * @return  self
     */ 
    public function setPrice(float $price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get the value of quantity
     */ 
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * Set the value of quantity
     *
     * @return  self
     */ 
    public function setQuantity(int $quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Get the value of internalReference
     */ 
    public function getInternalReference()
    {
        return $this->internalReference;
    }

    /**
     * Set the value of internalReference
     *
     * @return  self
     */ 
    public function setInternalReference(string $internalReference)
    {
        $this->internalReference = $internalReference;

        return $this;
    }

    /**
     * Get the value of shellId
     */ 
    public function getShellId()
    {
        return $this->shellId;
    }

    /**
     * Set the value of shellId
     *
     * @return  self
     */ 
    public function setShellId(int $shellId)
    {
        $this->shellId = $shellId;

        return $this;
    }

    /**
     * Get the value of inventoryStatus
     */ 
    public function getInventoryStatus()
    {
        return $this->inventoryStatus;
    }

    /**
     * Set the value of inventoryStatus
     *
     * @return  self
     */ 
    public function setInventoryStatus(string $inventoryStatus)
    {
        $this->inventoryStatus = $inventoryStatus;

        return $this;
    }

    /**
     * Get the value of rating
     */ 
    public function getRating()
    {
        return $this->rating;
    }

    /**
     * Set the value of rating
     *
     * @return  self
     */ 
    public function setRating(float $rating)
    {
        $this->rating = $rating;

        return $this;
    }

    /**
     * Get the value of createdAt
     */ 
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set the value of createdAt
     *
     * @return  self
     */ 
    public function setCreatedAt(DateTime $createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get the value of updatedAt
     */ 
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set the value of updatedAt
     *
     * @return  self
     */ 
    public function setUpdatedAt(DateTime $updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}