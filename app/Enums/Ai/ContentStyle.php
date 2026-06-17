<?php

declare(strict_types=1);

namespace App\Enums\Ai;

enum ContentStyle: string
{
    case ImageCard = 'image_card';
    case TweetCard = 'tweet_card';
    case TweetCardImage = 'tweet_card_image';

    /** i18n key for the style's display name. */
    public function label(): string
    {
        return "posts.ai.templates.{$this->value}.name";
    }

    /** i18n key for the style's description. */
    public function description(): string
    {
        return "posts.ai.templates.{$this->value}.description";
    }

    /** Whether this style requires a connected social account. */
    public function needsAccount(): bool
    {
        return match ($this) {
            self::ImageCard => false,
            self::TweetCard, self::TweetCardImage => true,
        };
    }

    /** Public path to the picker preview thumbnail. */
    public function previewAsset(): string
    {
        return match ($this) {
            self::ImageCard => '/images/ai-templates/image-card.png',
            self::TweetCard => '/images/ai-templates/tweet-card.png',
            self::TweetCardImage => '/images/ai-templates/tweet-card-image.png',
        };
    }

    public static function default(): self
    {
        return self::ImageCard;
    }
}
