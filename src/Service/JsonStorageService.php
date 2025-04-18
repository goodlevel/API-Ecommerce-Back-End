<?php

namespace App\Service;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class JsonStorageService
{
    private string $storageDir;

    
    public function __construct(string $storageDir, ParameterBagInterface $params) 
    {
        $this->storageDir = $storageDir ?: $params->get('kernel.project_dir') . '/public/storage/';

        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0777, true);
        }
    }


    public function read(string $filename): array
    {
        $filePath = $this->storageDir . $filename;
        if (!file_exists($filePath)) {
            return [];
        }

        $content = file_get_contents($filePath);
        return json_decode($content, true) ?: [];
    }

    public function write(string $filename, array $data): void
    {
        $filePath = $this->storageDir . $filename;
        file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
    }

    public function getNextId(string $filename): int
    {
        $items = $this->read($filename);
        return empty($items) ? 1 : max(array_column($items, 'id')) + 1;
    }
}