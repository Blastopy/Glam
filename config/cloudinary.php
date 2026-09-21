<?php

return [
    'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
    'api_key' => env('CLOUDINARY_API_KEY'),
    'api_secret' => env('CLOUDINARY_API_SECRET'),

    // Where uploaded style photos live in your Cloudinary media library
    'services_folder' => env('CLOUDINARY_SERVICES_FOLDER', 'salonbook/services'),
];
