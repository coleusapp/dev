<?php

namespace Coleus\Notes\Markdown\WikiLinks;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\ExtensionInterface;

class WikiLinkExtension implements ExtensionInterface
{
    public function __construct(private readonly ?WikiLinkResolver $resolver = null)
    {
        //
    }

    public function register(EnvironmentBuilderInterface $environment): void
    {
        // Above the standard "[" link parser so "[[x]]" never becomes a reference link.
        $environment->addInlineParser(new WikiLinkParser, 200);
        $environment->addRenderer(WikiLink::class, new WikiLinkRenderer($this->resolver));
        $environment->addBlockStartParser(NoteEmbedParser::blockStartParser(), 80);
        $environment->addRenderer(NoteEmbed::class, new NoteEmbedRenderer($this->resolver));
    }
}
