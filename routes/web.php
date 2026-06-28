<?php

use Illuminate\Support\Facades\Route;

Route::get('/link-storage', function () {
    $target = storage_path('app/public');
    $link = public_path('storage');

    if (file_exists($link)) {
        return 'The "public/storage" directory already exists.';
    }

    if (symlink($target, $link)) {
        return 'Symlink created successfully!';
    }

    return 'Failed to create symlink.';
});
