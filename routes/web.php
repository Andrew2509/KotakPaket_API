<?php

use Illuminate\Support\Facades\Route;

Route::get('/link-storage', function () {
    $target = storage_path('app/public');
    $link = public_path('storage');

    // Force recreate link if it's already a link (even a broken one)
    if (is_link($link)) {
        unlink($link);
    } elseif (file_exists($link)) {
        // If it's a real folder/file (not a link), rename it to avoid deleting user files
        rename($link, $link . '_backup_' . time());
    }

    if (symlink($target, $link)) {
        return 'Symlink created successfully!';
    }

    return 'Failed to create symlink.';
});

Route::get('/check-storage', function () {
    $target = storage_path('app/public');
    $link = public_path('storage');
    
    $results = [
        'target_path' => $target,
        'target_exists' => file_exists($target) ? 'Yes' : 'No',
        'target_is_dir' => is_dir($target) ? 'Yes' : 'No',
        'link_path' => $link,
        'link_exists_via_file_exists' => file_exists($link) ? 'Yes' : 'No',
        'link_exists_via_is_link' => is_link($link) ? 'Yes' : 'No',
    ];
    
    if (is_link($link)) {
        $target_read = readlink($link);
        $results['link_target_readlink'] = $target_read;
        $results['link_target_exists'] = file_exists($target_read) ? 'Yes' : 'No';
    }
    
    if (is_dir($target)) {
        $results['target_files'] = array_slice(scandir($target), 2);
    }
    
    return response()->json($results);
});
