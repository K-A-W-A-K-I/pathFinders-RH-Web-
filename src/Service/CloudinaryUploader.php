<?php

namespace App\Service;

use Cloudinary\Cloudinary;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class CloudinaryUploader
{
    private string $cloudName;
    private string $apiKey;
    private string $apiSecret;
    private string $imageFolder;
    private string $cvFolder;
    private int $maxImageSize;
    private int $maxCvSize;

    public function __construct(
        ?string $cloudName,
        ?string $apiKey,
        ?string $apiSecret,
        ?string $imageFolder,
        ?string $cvFolder,
        ?int $maxImageSize,
        ?int $maxCvSize,
    ) {
        $this->cloudName = (string) ($cloudName ?? '');
        $this->apiKey = (string) ($apiKey ?? '');
        $this->apiSecret = (string) ($apiSecret ?? '');
        $this->imageFolder = trim((string) ($imageFolder ?? 'pathfinders/users/images'));
        $this->cvFolder = trim((string) ($cvFolder ?? 'pathfinders/users/cv'));
        $this->maxImageSize = (int) ($maxImageSize ?? 5 * 1024 * 1024);
        $this->maxCvSize = (int) ($maxCvSize ?? 10 * 1024 * 1024);
    }

    public function isEnabled(): bool
    {
        return $this->cloudName !== '' && $this->apiKey !== '' && $this->apiSecret !== '';
    }

    /**
     * @return array{secure_url: string, public_id: string}
     */
    public function uploadProfileImage(UploadedFile $file, int $userId): array
    {
        $this->assertEnabled();
        $this->validateImage($file);

        $result = $this->client()->uploadApi()->upload($file->getPathname(), [
            'resource_type' => 'image',
            'folder' => $this->imageFolder,
            'public_id' => sprintf('user_%d_%d', $userId, time()),
            'overwrite' => true,
        ]);

        return [
            'secure_url' => (string) ($result['secure_url'] ?? ''),
            'public_id' => (string) ($result['public_id'] ?? ''),
        ];
    }

    /**
     * @return array{secure_url: string, public_id: string}
     */
    public function uploadCv(UploadedFile $file, int $userId): array
    {
        $this->assertEnabled();
        $this->validateCv($file);

        $result = $this->client()->uploadApi()->upload($file->getPathname(), [
            'resource_type' => 'auto',  // Let Cloudinary auto-detect the resource type
            'folder' => $this->cvFolder,
            'public_id' => sprintf('cv_%d_%d', $userId, time()),
            'overwrite' => true,
            'type' => 'upload',  // Explicitly set type to 'upload' for public access
        ]);

        return [
            'secure_url' => (string) ($result['secure_url'] ?? ''),
            'public_id' => (string) ($result['public_id'] ?? ''),
        ];
    }

    public function deleteByUrl(?string $url, string $resourceType): void
    {
        if (empty($url) || !$this->isEnabled() || !str_contains((string) $url, 'res.cloudinary.com')) {
            return;
        }

        $publicId = $this->extractPublicId((string) $url);
        if ($publicId === null || $publicId === '') {
            return;
        }

        try {
            // Try to delete with the specified resource type first
            $this->client()->uploadApi()->destroy($publicId, [
                'resource_type' => $resourceType,
                'invalidate' => true,
            ]);
        } catch (\Throwable $e) {
            // If it fails, try with 'raw' resource type (for CVs)
            try {
                $this->client()->uploadApi()->destroy($publicId, [
                    'resource_type' => 'raw',
                    'invalidate' => true,
                ]);
            } catch (\Throwable) {
                // Old files cleanup should never block profile updates.
            }
        }
    }

    private function validateImage(UploadedFile $file): void
    {
        if ($file->getSize() !== null && $file->getSize() > $this->maxImageSize) {
            throw new \InvalidArgumentException('Image trop volumineuse (max 5MB).');
        }

        $mime = (string) $file->getMimeType();
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (!in_array($mime, $allowed, true)) {
            throw new \InvalidArgumentException('Format image invalide. Utilisez JPG, PNG, WEBP ou GIF.');
        }
    }

    private function validateCv(UploadedFile $file): void
    {
        if ($file->getSize() !== null && $file->getSize() > $this->maxCvSize) {
            throw new \InvalidArgumentException('CV trop volumineux (max 10MB).');
        }

        $mime = (string) $file->getMimeType();
        $extension = strtolower((string) $file->getClientOriginalExtension());
        if ($extension === '') {
            $extension = strtolower((string) $file->guessExtension());
        }

        $allowedMime = ['application/pdf'];
        $allowedExtensions = ['pdf'];

        $isMimeAllowed = in_array($mime, $allowedMime, true);
        $isExtensionAllowed = in_array($extension, $allowedExtensions, true);

        if (!$isMimeAllowed && !$isExtensionAllowed) {
            throw new \InvalidArgumentException('Format CV invalide. Utilisez uniquement un fichier PDF.');
        }
    }

    private function client(): Cloudinary
    {
        return new Cloudinary([
            'cloud' => [
                'cloud_name' => $this->cloudName,
                'api_key' => $this->apiKey,
                'api_secret' => $this->apiSecret,
            ],
            'url' => ['secure' => true],
        ]);
    }

    private function assertEnabled(): void
    {
        if (!$this->isEnabled()) {
            throw new \RuntimeException('Cloudinary n\'est pas configuré. Vérifiez les variables d\'environnement.');
        }
    }

    private function extractPublicId(string $url): ?string
    {
        $marker = '/upload/';
        $position = strpos($url, $marker);
        if ($position === false) {
            return null;
        }

        $path = substr($url, $position + strlen($marker));
        if ($path === false || $path === '') {
            return null;
        }

        if (preg_match('#^v\d+/#', $path) === 1) {
            $path = preg_replace('#^v\d+/#', '', $path) ?? $path;
        }

        $pathWithoutQuery = explode('?', $path, 2)[0];
        $dotPosition = strrpos($pathWithoutQuery, '.');
        if ($dotPosition === false) {
            return $pathWithoutQuery;
        }

        return substr($pathWithoutQuery, 0, $dotPosition);
    }
}
