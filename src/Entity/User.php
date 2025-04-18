<?php

namespace App\Entity;

use Symfony\Component\Security\Core\User\UserInterface;


class User implements UserInterface
{
    private int $id;
    private string $username;
    private string $firstname;
    private string $email;
    private string $password;
    private array $userTypes = [];

    public function __construct(int $id, string $username, string $firstname, string $email, string $password)
    {
        $this->id = $id;
        $this->username = $username;
        $this->firstname = $firstname;
        $this->email = $email;
        $this->password = $password;
    }

    /**
     * Get the value of id
     */ 
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the value of username
     */ 
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set the value of username
     *
     * @return  self
     */ 
    public function setUsername(string $username)
    {
        $this->username = $username;

        return $this;
    }

    
    /**
     * Get the value of firstname
     */ 
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * Set the value of firstname
     *
     * @return  self
     */ 
    public function setFirstname(string $firstname)
    {
        $this->firstname = $firstname;

        return $this;
    }

     /**
     * Get the value of email
     */ 
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set the value of email
     *
     * @return  self
     */ 
    public function setEmail(string $email)
    {
        $this->email = $email;

        return $this;
    }

     /**
     * Get the value of password
     */ 
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set the value of password
     *
     * @return  self
     */ 
    public function setPassword(string $password)
    {
        $this->password = $password;

        return $this;
    }


    public function getUserTypes(): array
    {
        $userTypes = $this->userTypes;


        if ($this->email === 'admin@admin.com') {
            $userTypes[] = 'ROLE_ADMIN';
        }else{
            $userTypes[] = 'ROLE_USER';
        }

        return array_unique($userTypes);
    }

    public function getRoles(): array
    {
        return $this->getUserTypes();
    }

    public function getSalt(): ?string
    {
        return null; 
    }

    public function eraseCredentials(): void
    {
       
    }

}