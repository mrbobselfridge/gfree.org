<?php

namespace App\Support;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Collection;

class CodeBlockAccess
{
    public static function canManage(?User $user = null): bool
    {
        $user ??= self::currentUser();

        return AdminAccess::canAccessTool($user, AdminAccess::CODE_BLOCKS);
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $submittedBlocks
     * @param  array<int, array<string, mixed>>|null  $existingBlocks
     * @return array<int, array<string, mixed>>
     */
    public static function protectBlocks(?array $submittedBlocks, ?array $existingBlocks, ?User $user = null): array
    {
        $submittedBlocks = array_values($submittedBlocks ?? []);

        if (self::canManage($user)) {
            return $submittedBlocks;
        }

        $existingBlocks = collect($existingBlocks ?? []);
        $existingCodeBlocks = $existingBlocks
            ->filter(fn (array $block): bool => ($block['type'] ?? null) === 'code')
            ->values();
        $existingLinkCardBlocks = $existingBlocks
            ->filter(fn (array $block): bool => ($block['type'] ?? null) === 'link_cards')
            ->values();

        $codeBlockIndex = 0;
        $linkCardBlockIndex = 0;
        $protectedBlocks = [];

        foreach ($submittedBlocks as $block) {
            if (($block['type'] ?? null) === 'link_cards') {
                $protectedBlocks[] = self::protectLinkCardBlock(
                    $block,
                    $existingLinkCardBlocks->get($linkCardBlockIndex),
                );
                $linkCardBlockIndex++;

                continue;
            }

            if (($block['type'] ?? null) !== 'code') {
                $protectedBlocks[] = $block;

                continue;
            }

            $existingCodeBlock = $existingCodeBlocks->get($codeBlockIndex);
            $codeBlockIndex++;

            if ($existingCodeBlock) {
                $protectedBlocks[] = $existingCodeBlock;
            }
        }

        while ($linkCardBlockIndex < $existingLinkCardBlocks->count()) {
            $protectedBlock = self::codeCardsOnlyBlock($existingLinkCardBlocks->get($linkCardBlockIndex));

            if ($protectedBlock !== null) {
                $protectedBlocks[] = $protectedBlock;
            }

            $linkCardBlockIndex++;
        }

        while ($codeBlockIndex < $existingCodeBlocks->count()) {
            $protectedBlocks[] = $existingCodeBlocks->get($codeBlockIndex);
            $codeBlockIndex++;
        }

        return array_values($protectedBlocks);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>|null  $existingBlocks
     * @return array<string, mixed>
     */
    public static function protectFormData(array $data, ?array $existingBlocks = null, ?User $user = null): array
    {
        if (! array_key_exists('content_blocks', $data)) {
            return $data;
        }

        $data['content_blocks'] = self::protectBlocks($data['content_blocks'], $existingBlocks, $user);

        return $data;
    }

    private static function currentUser(): ?User
    {
        $filamentUser = Filament::auth()->user();

        if ($filamentUser instanceof User) {
            return $filamentUser;
        }

        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }

    /**
     * @param  array<string, mixed>  $submittedBlock
     * @param  array<string, mixed>|null  $existingBlock
     * @return array<string, mixed>
     */
    private static function protectLinkCardBlock(array $submittedBlock, ?array $existingBlock): array
    {
        $existingCodeCards = collect($existingBlock['data']['cards'] ?? [])
            ->filter(fn (array $card): bool => LinkCard::isCodeType($card['type'] ?? null))
            ->values();
        $existingCodeCardsByKey = $existingCodeCards
            ->filter(fn (array $card): bool => filled($card['key'] ?? null))
            ->keyBy(fn (array $card): string => LinkCard::sanitizedKey($card['key']));

        $codeCardIndex = 0;
        $cards = [];

        foreach (($submittedBlock['data']['cards'] ?? []) as $card) {
            $type = LinkCard::normalizeType($card['type'] ?? null, $card['url'] ?? null);

            if (LinkCard::isCodeType($type)) {
                $existingCard = self::matchingExistingCodeCard($card, $existingCodeCardsByKey, $existingCodeCards, $codeCardIndex);
                $codeCardIndex++;

                if ($existingCard !== null) {
                    $cards[] = $existingCard;
                }

                continue;
            }

            unset($card['html'], $card['javascript']);
            $card['type'] = in_array($type, [
                LinkCard::TYPE_DISPLAY,
                LinkCard::TYPE_LINK_SAME,
                LinkCard::TYPE_LINK_NEW,
                LinkCard::TYPE_FLIP_IMAGE,
            ], true) ? $type : LinkCard::TYPE_DISPLAY;

            if (in_array($card['type'], [LinkCard::TYPE_LINK_SAME, LinkCard::TYPE_LINK_NEW], true) && ! LinkCard::isSafeHref($card['url'] ?? null)) {
                $card['type'] = LinkCard::TYPE_DISPLAY;
                unset($card['url']);
            }

            $cards[] = $card;
        }

        while ($codeCardIndex < $existingCodeCards->count()) {
            $cards[] = $existingCodeCards->get($codeCardIndex);
            $codeCardIndex++;
        }

        $submittedBlock['data']['cards'] = array_values($cards);

        return $submittedBlock;
    }

    /**
     * @param  array<string, mixed>  $submittedCard
     * @param  Collection<string, array<string, mixed>>  $existingCodeCardsByKey
     * @param  Collection<int, array<string, mixed>>  $existingCodeCards
     * @return array<string, mixed>|null
     */
    private static function matchingExistingCodeCard(
        array $submittedCard,
        Collection $existingCodeCardsByKey,
        Collection $existingCodeCards,
        int $codeCardIndex,
    ): ?array {
        $key = filled($submittedCard['key'] ?? null) ? LinkCard::sanitizedKey($submittedCard['key']) : null;

        if ($key && $existingCodeCardsByKey->has($key)) {
            return $existingCodeCardsByKey->get($key);
        }

        return $existingCodeCards->get($codeCardIndex);
    }

    /**
     * @param  array<string, mixed>  $block
     * @return array<string, mixed>|null
     */
    private static function codeCardsOnlyBlock(array $block): ?array
    {
        $cards = collect($block['data']['cards'] ?? [])
            ->filter(fn (array $card): bool => LinkCard::isCodeType($card['type'] ?? null))
            ->values()
            ->all();

        if ($cards === []) {
            return null;
        }

        $block['data']['cards'] = $cards;

        return $block;
    }
}
