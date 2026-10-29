// @vitest-environment jsdom
import { clipboardContent, htmlToPlainText, writeClipboard } from '@/lib/copyAs';
import { afterEach, describe, expect, it, vi } from 'vitest';

/**
 * As NoteMarkdown renders "# Title / text / checklist / table / quote /
 * code / rule / image".
 */
const RENDERED = `<h1 id="heading-title">Title<a href="#heading-title" class="heading-anchor" aria-hidden="true" title="Link to this heading"></a></h1>
<p>Some <em>text</em> here
and more.</p>
<ul>
<li><input type="checkbox" data-task-line="6"> milk</li>
<li><input type="checkbox" checked="" data-task-line="7"> eggs
<ol>
<li>nested</li>
<li>again</li>
</ol>
</li>
</ul>
<table>
<thead>
<tr>
<th>a</th>
<th>b</th>
</tr>
</thead>
<tbody>
<tr>
<td>1</td>
<td>2</td>
</tr>
</tbody>
</table>
<blockquote>
<p>quote</p>
</blockquote>
<pre><code>code  x
</code></pre>
<hr />
<p>end<img src="/x.png" alt="pic" loading="lazy" /></p>`;

afterEach(() => {
    vi.unstubAllGlobals();
});

describe('htmlToPlainText', () => {
    it('reads like the note, without markup or heading anchors', () => {
        expect(htmlToPlainText(RENDERED)).toBe(
            [
                'Title',
                '',
                'Some text here and more.',
                '',
                '- [ ] milk',
                '- [x] eggs',
                '  1. nested',
                '  2. again',
                '',
                'a\tb',
                '1\t2',
                '',
                'quote',
                '',
                'code  x',
                '',
                '---',
                '',
                'end[pic]',
            ].join('\n'),
        );
    });

    it('keeps line breaks and ordered list starts', () => {
        expect(htmlToPlainText('<p>one<br>two</p><ol start="3"><li>c</li><li>d</li></ol>')).toBe('one\ntwo\n\n3. c\n4. d');
        expect(htmlToPlainText('')).toBe('');
    });
});

describe('clipboardContent', () => {
    it('gives Markdown as is, text from the HTML, and HTML with its source as text', () => {
        expect(clipboardContent('markdown', '# A\n**b**', '<h1>A</h1>')).toEqual({ text: '# A\n**b**', html: null });
        expect(clipboardContent('text', '# A', '<h1>A</h1><p><strong>b</strong></p>')).toEqual({ text: 'A\n\nb', html: null });
        expect(clipboardContent('html', '# A', '<h1>A</h1>')).toEqual({ text: '<h1>A</h1>', html: '<h1>A</h1>' });
    });
});

describe('writeClipboard', () => {
    it('writes rich HTML where the browser can, plain text otherwise', async () => {
        const write = vi.fn(async () => undefined);
        const writeText = vi.fn(async () => undefined);
        vi.stubGlobal('navigator', { clipboard: { write, writeText } });
        vi.stubGlobal(
            'ClipboardItem',
            class {
                constructor(public items: Record<string, Blob>) {}
            },
        );

        await writeClipboard({ text: '<b>x</b>', html: '<b>x</b>' });
        await writeClipboard({ text: 'plain', html: null });

        expect(write).toHaveBeenCalledTimes(1);
        expect(Object.keys((write.mock.calls[0] as unknown as [{ items: Record<string, Blob> }[]])[0][0].items)).toEqual(['text/html', 'text/plain']);
        expect(writeText).toHaveBeenCalledWith('plain');
    });
});
