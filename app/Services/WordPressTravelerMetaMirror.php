<?php

namespace App\Services;

use App\Models\CatalogActivity;
use App\Models\CatalogTransfer;
use App\Models\StHotel;
use App\Models\Wp\WpPost;
use App\Models\WpPostmeta;

/**
 * Duplicates key fields into wp_postmeta keys commonly read by Traveler and by
 * theme/plugin partials (e.g. accommodations slider uses meta "price", "address").
 */
class WordPressTravelerMetaMirror
{
    public function mirrorActivityMetas(WpPost $post, CatalogActivity $record): void
    {
        $primary = $record->min_price ?? $record->adult_price;
        $primaryStr = $primary !== null && $primary !== '' ? (string) $primary : '';

        $displayAddress = trim((string) ($record->address ?? ''));
        if ($displayAddress === '') {
            $displayAddress = trim((string) ($record->place_text ?? ''));
        }

        $post->setMeta('price', $primaryStr);
        $post->setMeta('min_price', $record->min_price !== null && $record->min_price !== '' ? (string) $record->min_price : '');
        $post->setMeta('adult_price', $record->adult_price !== null && $record->adult_price !== '' ? (string) $record->adult_price : '');
        $post->setMeta('child_price', $record->child_price !== null && $record->child_price !== '' ? (string) $record->child_price : '');
        $post->setMeta('duration', (string) ($record->duration ?? ''));
        $post->setMeta('address', $displayAddress);
        $post->setMeta('location', (string) ($record->place_text ?? ''));
        $post->setMeta('is_featured', $record->is_featured ? 'on' : 'off');
        $post->setMeta('type_activity', (string) ($record->type_activity ?? ''));
    }

    public function mirrorTransferMetas(WpPost $post, CatalogTransfer $record): void
    {
        $primary = $record->min_price ?? $record->cars_price;
        $primaryStr = $primary !== null && $primary !== '' ? (string) $primary : '';

        $post->setMeta('price', $primaryStr);
        $post->setMeta('min_price', $record->min_price !== null && $record->min_price !== '' ? (string) $record->min_price : '');
        $post->setMeta('max_price', $record->max_price !== null && $record->max_price !== '' ? (string) $record->max_price : '');
        $post->setMeta('cars_price', $record->cars_price !== null && $record->cars_price !== '' ? (string) $record->cars_price : '');
        $post->setMeta('address', (string) ($record->cars_address ?? ''));
        $post->setMeta('is_featured', $record->is_featured ? 'on' : 'off');
    }

    public function mirrorHotelMetas(int $postId, StHotel $hotel): void
    {
        $min = $hotel->min_price !== null && $hotel->min_price !== '' ? (string) $hotel->min_price : '';
        WpPostmeta::updateOrInsertMeta($postId, 'price', $min);
        WpPostmeta::updateOrInsertMeta($postId, 'min_price', $min);
        WpPostmeta::updateOrInsertMeta($postId, 'address', $hotel->address !== null && $hotel->address !== '' ? (string) $hotel->address : '');
        WpPostmeta::updateOrInsertMeta($postId, 'hotel_star', $hotel->hotel_star !== null && $hotel->hotel_star !== '' ? (string) $hotel->hotel_star : '');
        WpPostmeta::updateOrInsertMeta($postId, 'is_featured', ($hotel->is_featured ?? '') === 'on' ? 'on' : 'off');
    }
}
