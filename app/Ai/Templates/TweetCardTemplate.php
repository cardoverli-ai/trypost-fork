<?php

declare(strict_types=1);

namespace App\Ai\Templates;

use App\Enums\PostPlatform\ContentType;
use App\Services\Image\PostImagePipeline;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class TweetCardTemplate implements AiContentTemplate
{
    public function key(): string
    {
        return 'tweet_card';
    }

    public function name(): string
    {
        return 'posts.ai.templates.tweet_card.name';
    }

    public function description(): string
    {
        return 'posts.ai.templates.tweet_card.description';
    }

    public function previewAsset(): string
    {
        return '/images/ai-templates/tweet-card.png';
    }

    public function needsAccount(): bool
    {
        return true;
    }

    /** @return array<int, string> */
    public function supportedFormats(): array
    {
        return [];
    }

    public function generatorFormat(): string
    {
        return 'tweet_card';
    }

    public function promptView(): string
    {
        return 'prompts.post_content.tweet_card';
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema, TemplateContext $context): array
    {
        return [
            'tweet_text' => $schema->string()
                ->description('The post body, written as a punchy first-person X/Twitter-style take. Paragraph breaks (\\n\\n) allowed. Max ~560 characters.')
                ->required(),
        ];
    }

    /**
     * @param  array<string, mixed>  $structured
     */
    public function assemble(array $structured, TemplateContext $context): GeneratedPost
    {
        $text = (string) data_get($structured, 'tweet_text', '');

        $media = [];

        if ($context->socialAccount) {
            $media = app(PostImagePipeline::class)->forTweetCard(
                workspace: $context->workspace,
                account: $context->socialAccount,
                tweetText: $text,
            );
        }

        return new GeneratedPost(
            content: $text,
            media: $media,
            contentType: ContentType::tryFrom($context->format),
        );
    }
}
