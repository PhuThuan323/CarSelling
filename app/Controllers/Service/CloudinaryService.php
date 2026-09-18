<?php
declare(strict_types=1);
namespace App\Service;
use Cloudinary\Cloudinary;
use RuntimeException;

class CloudinaryService{
    private Cloudinary $cloud;
    public function __construct(){
        $cloudUrl = $_ENV['CLOUDINARY_URL'] ?? $_SERVER['CLOUDINARY_URL']??'';
        if ($cloudUrl === '') {
            throw new RuntimeException(
                'CLOUDINARY_URL is not configured.'
            );
        }
        $this->cloud=new Cloudinary($cloudUrl);
    }
    public function uploadBrandLogo(
        string $filePath
    ): array {
        $result =
            $this->cloud
                ->uploadApi
                ->upload(
                    $filePath,
                    [
                        'folder' => 'carselling/brands',
                        'resource_type' => 'image',
                        'unique_filename' => true,
                        'overwrite' => false,
                        'tags' => [
                            'carselling',
                            'brand-logo',
                        ],
                    ]
                );

        return [
            'secure_url' =>
                (string) ($result['secure_url'] ?? ''),

            'public_id' =>
                (string) ($result['public_id'] ?? ''),

            'asset_id' =>
                (string) ($result['asset_id'] ?? ''),

            'width' =>
                isset($result['width'])
                    ? (int) $result['width']
                    : null,

            'height' =>
                isset($result['height'])
                    ? (int) $result['height']
                    : null,

            'bytes' =>
                isset($result['bytes'])
                    ? (int) $result['bytes']
                    : null,

            'format' =>
                (string) ($result['format'] ?? ''),
        ];
    }
    public function destroyImage(
        string $publicId
    ): bool {
        if ($publicId === '') {
            return true;
        }

        $result =
            $this->cloud
                ->uploadApi
                ->destroy(
                    $publicId,
                    [
                        'resource_type' => 'image',
                        'invalidate' => true,
                    ]
                );

        return (($result['result'] ?? '') === 'ok');
    }
}