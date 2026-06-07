<?php

namespace App\Support;

class AiBulletinExtractionPrompt
{
    public const DEFAULT = 'Extract the important public bulletin content for the church website. Preserve headings, dates, event details, announcements, contact information, and links when available. Return clean formatted HTML with headings, paragraphs, and bullet lists where helpful. Anywhere it notes Connection Card - please link that to /card on this site in a new window. ';
}
