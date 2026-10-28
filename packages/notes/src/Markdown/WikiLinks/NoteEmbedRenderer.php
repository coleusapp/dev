<?php

namespace Coleus\Notes\Markdown\WikiLinks;

use Coleus\Notes\Markdown\NoteMarkdown;
use Coleus\Notes\Models\Note;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;
use League\CommonMark\Util\Xml;

/**
 * Renders an embedded note (or one section of it) inside the note that
 * embeds it, under a link to it. Loop-safe: a note already being rendered
 * up the chain, or anything deeper than MAX_DEPTH, shows as a link only.
 * Checklists inside are read-only, since their line numbers belong to the
 * other note.
 */
class NoteEmbedRenderer implements NodeRendererInterface
{
    public const int MAX_DEPTH = 3;

    /**
     * Ids of the notes being rendered, outermost first.
     *
     * @var list<int>
     */
    private static array $rendering = [];

    public function __construct(private readonly ?WikiLinkResolver $resolver)
    {
        //
    }

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): HtmlElement
    {
        NoteEmbed::assertInstanceOf($node);

        $link = ['title' => $node->title, 'heading' => $node->heading, 'label' => $node->label];
        $text = Xml::escape(WikiLinks::text($link));
        $id = $this->resolver?->resolve($node->title);
        $note = $id === null ? null : Note::query()->find($id);

        if ($note === null) {
            return new HtmlElement('p', [], new HtmlElement('a', [
                'href' => '#',
                'class' => 'wiki-link wiki-link-missing',
                'data-wiki-title' => $node->title,
                'title' => 'Create "'.$node->title.'"',
            ], $text));
        }

        $heading = $node->heading === null ? [] : ['heading' => $node->heading];
        $title = new HtmlElement('a', [
            'href' => route('notes.show', ['note' => $note->id, ...$heading], false),
            'class' => 'wiki-link',
            'data-note-id' => (string) $note->id,
            ...($node->heading === null ? [] : ['data-heading' => $node->heading]),
        ], $text);

        $chain = [...self::$rendering, ...($this->resolver?->fromNoteId === null ? [] : [$this->resolver->fromNoteId])];

        if (in_array($note->id, $chain, true) || count($chain) >= self::MAX_DEPTH) {
            return new HtmlElement('p', ['class' => 'note-embed-skipped'], $title);
        }

        if ($note->isLocked()) {
            return new HtmlElement('div', ['class' => 'note-embed', 'data-embed-id' => (string) $note->id], [
                new HtmlElement('div', ['class' => 'note-embed-title'], $title),
                new HtmlElement('div', ['class' => 'note-embed-body'], new HtmlElement('p', ['class' => 'note-embed-missing'], 'This note is locked.')),
            ]);
        }

        $body = $node->heading === null ? $note->body : WikiLinks::section($note->body, $node->heading);
        $content = $body === null
            ? new HtmlElement('p', ['class' => 'note-embed-missing'], 'No heading “'.Xml::escape($node->heading ?? '').'” in this note.')
            : self::renderInside($chain, $note, $body);

        return new HtmlElement('div', ['class' => 'note-embed', 'data-embed-id' => (string) $note->id], [
            new HtmlElement('div', ['class' => 'note-embed-title'], $title),
            new HtmlElement('div', ['class' => 'note-embed-body'], $content),
        ]);
    }

    /**
     * @param  list<int>  $chain
     */
    private static function renderInside(array $chain, Note $note, string $body): string
    {
        $previous = self::$rendering;
        self::$rendering = $chain;

        try {
            $html = NoteMarkdown::toHtml($body, WikiLinkResolver::forBody($body, $note->id));
        } finally {
            self::$rendering = $previous;
        }

        return (string) preg_replace('/ data-task-line="\d+"/', ' disabled=""', $html);
    }
}
