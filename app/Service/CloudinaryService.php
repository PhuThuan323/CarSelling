<?php

declare(strict_types=1);

namespace App\Service;

use Cloudinary\Cloudinary;
use RuntimeException;

class CloudinaryService
{
    private Cloudinary $cloudinary;

    public function __construct()
    {
        $cloudinaryUrl =
            $_ENV['CLOUDINARY_URL']
            ?? '';

        if ($cloudinaryUrl === '') {
            throw new RuntimeException(
                'CLOUDINARY_URL chưa được cấu hình.'
            );
        }

        $this->cloudinary =
            new Cloudinary(
                $cloudinaryUrl
            );
    }

    public function uploadBrandLogo(
        string $filePath
    ): array {

        $result =
            $this->cloudinary
                ->uploadApi()
                ->upload(
                    $filePath,
                    [
                        'folder' =>
                            'carselling/brands',

                        'resource_type' =>
                            'image',

                        'unique_filename' =>
                            true,

                        'overwrite' =>
                            false,
                    ]
                );

        return [
            'secure_url' =>
                $result['secure_url']
                ?? '',

            'public_id' =>
                $result['public_id']
                ?? '',

            'width' =>
                $result['width']
                ?? null,

            'height' =>
                $result['height']
                ?? null,

            'bytes' =>
                $result['bytes']
                ?? null,

            'format' =>
                $result['format']
                ?? null,
        ];
    }

    public function uploadValuationImage(string $filePath):array{
        $result = $this->cloudinary->uploadApi()->upload($filePath,
            [
                'folder'=>'fastcar/valuations',
                'resource_type'=>'image',
                'unique_filename'=>true,
                'overwrite'=>false,
            ]);
        return [
            'secure_url' => $result['secure_url'] ?? '',
            'public_id' => $result['public_id'] ?? '',
            'asset_id' => $result['asset_id'] ?? null,
            'width' => $result['width'] ?? null,
            'height' => $result['height'] ?? null,
            'bytes' => $result['bytes'] ?? null,
            'format' => $result['format'] ?? null,
        ];
    }
}