<?php

declare(strict_types=1);

namespace Supergnaw\Nestbox\Lorikeet;

use Supergnaw\Nestbox\Exception\InvalidTableException;
use Supergnaw\Nestbox\Exception\NestboxException;
use Supergnaw\Nestbox\Nestbox;

class Lorikeet extends Nestbox
{
    // settings variables
    public string $imageSaveDirectory;
    public string $imageThumbnailDirectory;
    public bool $keepAspectRatio;
    public int $maxWidth;
    public int $maxHeight;
    public int $maxFilesizeBb;
    public bool $allowBmp;
    public bool $allowGif;
    public bool $allowJpg;
    public bool $allowPng;
    public bool $allowWebp;
    public string $convertToFiletype;
    public string $virusTotalApi;

    // constructor
    public function __construct(string $host = null, string $user = null, string $pass = null, string $name = null)
    {
        // call parent constructor
        parent::__construct();

        // set default variables
        $defaultSettings = [
            "imageSaveDirectory" => ".",
            "imageThumbnailDirectory" => ".",
            "keepAspectRatio" => true,
            "maxWidth" => 0,
            "maxHeight" => 0,
            "maxFilesizeBb" => 2,
            "allowBmp" => true,
            "allowGif" => true,
            "allowJpg" => true,
            "allowPng" => true,
            "allowWebp" => true,
            "convertToFiletype" => "webp",
            "virusTotalApi" => "",
        ];

        $this->load_settings(package: "lorikeet", defaultSettings: $defaultSettings);

        $this->settingNames = array_keys($defaultSettings);
    }

    public function __invoke(string $host = null, string $user = null, string $pass = null, string $name = null)
    {
        $this->__construct($host, $user, $pass, $name);
    }

    public function __destruct()
    {
        // save settings
        $this->save_settings(package: "lorikeet", settings: $this->settingNames);

        // do the thing
        parent::__destruct();
    }

    public function query_execute(string $query, array $params = [], bool $close = false): bool
    {
        try {
            return parent::query_execute($query, $params, $close);
        } catch (InvalidTableException) {
            $this->create_tables();
            return parent::query_execute($query, $params, $close);
        }
    }

    public function create_tables(): void
    {
        $this->create_lorikeet_images_table();
    }

    public function create_lorikeet_images_table(): bool
    {
        $sql = "CREATE TABLE IF NOT EXISTS `lorikeet_images` (
                    `image_id` VARCHAR( 64 ) NOT NULL ,
                    `image_title` VARCHAR( 128 ) NOT NULL ,
                    `image_caption` VARCHAR( 256 ) NULL ,
                    `saved` NOT NULL DEFAULT CURRENT TIMESTAMP ,
                    `edited` TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT TIMESTAMP ,
                    `tags` MEDIUMTEXT NOT NULL ,
                    PRIMARY KEY ( `image_id` )
                ) ENGINE = InnoDB DEFAULT CHARSET=UTF8MB4 COLLATE=utf8_unicode_ci;";

        return $this->query_execute($sql);
    }

    public function create_save_directory(string $image_directory = null): bool
    {
        die($image_directory);
        return true;
    }

    public function change_save_directory(): bool
    {
        return true;
    }

    public function create_thumbnail_directory(): bool
    {
        return true;
    }

    public function change_thumbnail_directory(): bool
    {
        return true;
    }

    public function upload_image(): bool
    {
        // verify file size is not zero
        // verify file extension is approved
        // verify file magic number
        // |  Ext  | First 12 Hex digits (x = variable)           | ASCII            |
        // | ----- | -------------------------------------------- | ---------------- |
        // |  .bmp | 42 4d xx xx xx xx xx xx xx xx xx xx xx xx xx | BM______________ |
        // |  .gif | 47 49 46 38 xx xx xx xx xx xx xx xx xx xx xx | GIF8____________ |
        // |  .jpg | ff d8 ff e0 xx xx xx xx xx xx xx xx xx xx xx | ????____________ |
        // |  .png | 89 50 4e 47 xx xx xx xx xx xx xx xx xx xx xx | .PNG____________ |
        // | .webp | 52 49 46 46 xx xx xx xx 57 45 42 50 56 50 38 | RIFF____WEBPVP8? |
        // verify file info fileinfo()
        // - https://www.php.net/manual/en/book.fileinfo.php
        // get file hash
        // verify image size getimagesize()
        // - https://www.php.net/manual/en/function.getimagesize.php
        // copy image contents from uploaded image to new image
        // scale image as defined in settings
        // change filetype as needed
        // save to target directory with source file hash
        // create thumbnail
        // add image to database with hash as id to prevent duplicates
        // - modify database if new image data was provided with duplicate
        return true;
    }

    // process image
    public function resize_image(): bool
    {
        return true;
    }

    public function convert_type(): bool
    {
        return true;
    }

    public function generate_thumbnail(): bool
    {
        return true;
    }


    // image database entries
    public function add_image(): bool
    {
        return true;
    }

    public function edit_image(): bool
    {
        return true;
    }

    public function delete_image(): bool
    {
        return true;
    }

    // image search
    public function search_by_id(string $id): array
    {
        return [];
    }

    public function search_by_title(string $title, bool $exact_match = true): array
    {
        return [];
    }

    public function search_by_caption(string $title, bool $exact_match = true): array
    {
        return [];
    }

    public function search_by_tags(array $tags, bool $match_all = false): array
    {
        return [];
    }

    public function image_search(string $id = "", string $title = "", string $caption = "", array $tags = []): array
    {
        return [];
    }

    public function display_image(string $id): void
    {
        return;
    }
}
