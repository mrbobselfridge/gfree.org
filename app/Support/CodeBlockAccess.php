<?php

namespace App\Support;

use App\Models\User;
use Filament\Facades\Filament;

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

        $existingCodeBlocks = collect($existingBlocks ?? [])
            ->filter(fn (array $block): bool => ($block['type'] ?? null) === 'code')
            ->values();

        $codeBlockIndex = 0;
        $protectedBlocks = [];

        foreach ($submittedBlocks as $block) {
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
}
