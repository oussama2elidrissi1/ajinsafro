<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class CustomRequestDocument extends Model
{
    protected $fillable = [
        'custom_request_id', 'uploaded_by', 'quote_id', 'document_type', 'title',
        'file_path', 'original_name', 'mime_type', 'size', 'is_auto_generated',
    ];

    protected $casts = ['is_auto_generated' => 'boolean'];

    public function customRequest(): BelongsTo
    {
        return $this->belongsTo(CustomRequest::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(CustomRequestQuote::class, 'quote_id');
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }
}
