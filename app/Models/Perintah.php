<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Perintah extends Model
{
    protected $table = 'perintahs';

    protected $fillable = [
        'tipe',
        'kotak',
        'pesanan_id',
        'status',
    ];

    /**
     * Get the pesanan associated with the command.
     */
    public function pesanan()
    {
        return $this->belongsTo(Pesanan::class);
    }
}
