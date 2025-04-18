<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\JsonStorageService;
use App\Service\AuthService;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;



class AuthController extends AbstractController
{
    private JsonStorageService $storage;

    public function __construct(JsonStorageService $storage)
    {
        $this->storage = $storage;
    }

    /**
     * Register new user
     * 
     * @Route("/api/account", name="register", methods={"POST"})
     * @OA\Post(
     *     summary="Register a new user",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "username", "firstname", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="username", type="string", example="johndoe"),
     *             @OA\Property(property="firstname", type="string", example="John"),
     *             @OA\Property(property="password", type="string", example="securePassword123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/User")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Email already exists"
     *     )
     * )
     */
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher): JsonResponse {
        
        $data = json_decode($request->getContent(), true);

    
        if ($data === null || json_last_error() !== JSON_ERROR_NONE) {
            return $this->json(['error' => 'Invalid JSON payload'], 400);
        }

        
        $requiredFields = ['email', 'username', 'firstname', 'password'];
        $missingFields = array_diff($requiredFields, array_keys($data));
        
        if (!empty($missingFields)) {
            return $this->json([
                'error' => 'Missing required fields',
                'missing_fields' => array_values($missingFields)
            ], 400);
        }

    
        $emptyFields = [];
        foreach ($requiredFields as $field) {
            if (empty(trim($data[$field]))) {
                $emptyFields[] = $field;
            }
        }
        
        if (!empty($emptyFields)) {
            return $this->json([
                'error' => 'Fields cannot be empty',
                'empty_fields' => $emptyFields
            ], 400);
        }

       
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return $this->json(['error' => 'Invalid email format'], 400);
        }

        $users = $this->storage->read('users.json');

        if (array_filter($users, fn($u) => $u['email'] === $data['email'])) {
            return $this->json(['error' => 'Email already exists'], 409);
        }

    
        $id = $this->storage->getNextId('users.json');
        $user = new User(
            $id,
            $data['username'],
            $data['firstname'],
            $data['email'],
            $passwordHasher->hashPassword(new User($id, '', '', $data['email'], ''), $data['password'])
        );

        $users[] = [
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'firstname' => $user->getFirstname(),
            'email' => $user->getEmail(),
            'password' => $user->getPassword(),
        ];

        $this->storage->write('users.json', $users);

        return $this->json([
            'message' => 'User created successfully',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'username' => $user->getUsername()
            ]
        ], 201);
    }


     /**
     * Login user and get JWT token
     * 
     * @Route("/api/token", name="login", methods={"POST"})
     * @OA\Post(
     *     summary="Authenticate user and get JWT token",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="password", type="string", example="securePassword123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Authentication successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid input",
     *         ref="#/components/schemas/Error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials"
     *     )
     * )
     */
     public function login(Request $request, AuthService $authService, JWTTokenManagerInterface $jwtManager): JsonResponse {

        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            return $this->json(['error' => 'Invalid JSON data'], 400);
        }

        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$email || !$password) {
            return $this->json(['error' => 'Email and password are required'], 400);
        }

        $user = $authService->authenticate($email, $password);

        if (!$user) {
            return $this->json(['error' => 'Invalid credentials'], 401);
        }

        $token = $jwtManager->create($user);    

        return $this->json([
            'token' => $token
        ]);
    }
}