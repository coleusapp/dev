<?php

use Coleus\Notes\Bases\BaseTable;
use Coleus\Notes\Models\Base;
use Coleus\Notes\Models\Note;
use Coleus\Notes\Models\NoteLockKey;
use Coleus\Notes\Services\FolderService;
use Coleus\Notes\Services\NoteLocks;
use Coleus\Users\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

/**
 * @param  array<string, mixed>  $definition
 * @return list<array<string, mixed>>
 */
function baseRows(array $definition): array
{
    return BaseTable::build($definition)['rows'];
}

it('shows note fields, properties and formulas as columns', function () {
    $folder = app(FolderService::class)->create('Shop', null);
    $note = Note::factory()->create(['body' => "---\nprice: 2.5\nQty: 4\n---\n# Apples #fruit #food"]);
    app(FolderService::class)->moveNote($note, $folder);

    $table = BaseTable::build([
        'columns' => ['note.folder', 'note.tags', 'price', 'qty', 'formula.total', 'missing'],
        'formulas' => [['name' => 'total', 'expression' => 'price * Qty']],
    ]);

    expect($table['columns'])->toBe([
        ['id' => 'note.folder', 'label' => 'Folder', 'kind' => 'note'],
        ['id' => 'note.tags', 'label' => 'Tags', 'kind' => 'note'],
        ['id' => 'price', 'label' => 'price', 'kind' => 'property'],
        ['id' => 'qty', 'label' => 'qty', 'kind' => 'property'],
        ['id' => 'formula.total', 'label' => 'total', 'kind' => 'formula'],
        ['id' => 'missing', 'label' => 'missing', 'kind' => 'property'],
    ])
        ->and($table['rows'])->toBe([[
            'id' => $note->id,
            'title' => 'Apples #fruit #food',
            'locked' => false,
            'cells' => ['note.folder' => 'Shop', 'note.tags' => ['food', 'fruit'], 'price' => 2.5, 'qty' => 4, 'formula.total' => 10.0, 'missing' => null],
            'errors' => [],
        ]])
        ->and($table['total'])->toBe(1);
});

it('takes the notes its query picks, leaving out the archive and trash', function () {
    $work = Note::factory()->create(['body' => '# Plan #work']);
    Note::factory()->create(['body' => '# Home']);
    Note::factory()->create(['body' => '# Old #work', 'archived_at' => now()]);
    Note::factory()->trashed()->create(['body' => '# Gone #work']);

    expect(array_column(baseRows(['query' => 'tag:work']), 'id'))->toBe([$work->id])
        ->and(baseRows([]))->toHaveCount(2);
});

it('filters rows on properties, note fields and formulas', function () {
    $a = Note::factory()->create(['body' => "---\nstatus: Done\nscore: 9\ntags: [x, y]\n---\nA"]);
    $b = Note::factory()->create(['body' => "---\nstatus: open\nscore: 10\n---\nB"]);
    $c = Note::factory()->create(['body' => 'C']);

    $ids = fn (array $filters, string $where = '') => collect(baseRows([
        'filters' => $filters,
        'where' => $where,
        'formulas' => [['name' => 'double', 'expression' => 'score * 2']],
    ]))->pluck('id')->sort()->values()->all();

    expect($ids([['column' => 'status', 'operator' => 'is', 'value' => 'done']]))->toBe([$a->id])
        ->and($ids([['column' => 'score', 'operator' => 'gt', 'value' => '9']]))->toBe([$b->id])
        ->and($ids([['column' => 'formula.double', 'operator' => 'gte', 'value' => '18']]))->toBe([$a->id, $b->id])
        ->and($ids([['column' => 'note.tags', 'operator' => 'is', 'value' => 'y']]))->toBe([$a->id])
        ->and($ids([['column' => 'status', 'operator' => 'empty', 'value' => '']]))->toBe([$c->id])
        ->and($ids([['column' => 'status', 'operator' => 'not_empty', 'value' => ''], ['column' => 'note.title', 'operator' => 'contains', 'value' => 'b']]))->toBe([$b->id])
        ->and($ids([], 'score < 10 || !status'))->toBe([$a->id, $c->id]);
});

it('matches each filter operator', function (mixed $value, string $operator, string $expected, bool $result) {
    expect(BaseTable::matches($value, $operator, $expected))->toBe($result);
})->with([
    'is, any case' => ['Done', 'is', 'done', true],
    'is a number' => [10, 'is', '10.0', true],
    'is with nothing to compare matches empty' => [null, 'is', '', true],
    'is a list item' => [['a', 'b'], 'is', 'B', true],
    'is not' => ['open', 'is_not', 'done', true],
    'is not, empty value' => [null, 'is_not', 'done', true],
    'contains' => ['Lisbon trip', 'contains', 'TRIP', true],
    'contains in a list item' => [['alpha', 'beta'], 'contains', 'et', true],
    'does not contain' => ['Lisbon', 'not_contains', 'x', true],
    'greater, numbers' => [9, 'gt', '10', false],
    'greater, dates' => ['2026-11-02', 'gt', '2026-11-01', true],
    'at least' => [5, 'gte', '5', true],
    'less than, empty is never less' => [null, 'lt', '5', false],
    'at most' => ['2026-01-01', 'lte', '2026-01-01', true],
    'empty list' => [[], 'empty', '', true],
    'not empty' => [false, 'not_empty', '', true],
]);

it('sorts by several columns with empty values last', function () {
    $a = Note::factory()->create(['body' => "---\ngroup: b\nn: 1\n---\nA"]);
    $b = Note::factory()->create(['body' => "---\ngroup: a\nn: 2\n---\nB"]);
    $c = Note::factory()->create(['body' => "---\ngroup: b\nn: 3\n---\nC"]);
    $d = Note::factory()->create(['body' => 'D']);

    $ids = fn (array $sort) => array_column(baseRows(['sort' => $sort]), 'id');

    expect($ids([['column' => 'group', 'direction' => 'asc'], ['column' => 'n', 'direction' => 'desc']]))->toBe([$b->id, $c->id, $a->id, $d->id])
        ->and($ids([['column' => 'n', 'direction' => 'desc']]))->toBe([$c->id, $b->id, $a->id, $d->id]);
});

it('groups rows by a column, groups in order with the empty group last', function () {
    $a = Note::factory()->create(['body' => "---\ngenre: Sci-fi\nyear: 1969\n---\nA"]);
    $b = Note::factory()->create(['body' => "---\ngenre: fantasy\nyear: 1968\n---\nB"]);
    $c = Note::factory()->create(['body' => "---\ngenre: sci-fi\nyear: 1974\n---\nC"]);
    $d = Note::factory()->create(['body' => 'D']);
    $e = Note::factory()->create(['body' => "---\ngenre: [x, y]\n---\nE"]);

    $table = BaseTable::build(['group_by' => 'genre', 'sort' => [['column' => 'year', 'direction' => 'desc']]]);

    expect(array_column($table['rows'], 'id'))->toBe([$b->id, $c->id, $a->id, $e->id, $d->id])
        ->and($table['groups'])->toBe([
            ['value' => 'fantasy', 'total' => 1, 'count' => 1],
            ['value' => 'sci-fi', 'total' => 2, 'count' => 2],
            ['value' => 'x, y', 'total' => 1, 'count' => 1],
            ['value' => null, 'total' => 1, 'count' => 1],
        ])
        ->and(BaseTable::build([])['groups'])->toBeNull();
});

it('counts only the rows it sends in each group', function () {
    Note::factory()->count(BaseTable::MAX_ROWS)->create(['body' => "---\nk: a\n---\nA"]);
    Note::factory()->count(3)->create(['body' => "---\nk: b\n---\nB"]);

    expect(BaseTable::build(['group_by' => 'k'])['groups'])->toBe([['value' => 'a', 'total' => BaseTable::MAX_ROWS, 'count' => BaseTable::MAX_ROWS]]);
});

it('gives cards a picture or a snippet', function () {
    $picture = Note::factory()->create(['body' => "# Trip\n![](/notes/attachments/0b6c6a54-8d4e-4c55-9d64-1f0b3b8c4c11/view.jpg)\nSea"]);
    $text = Note::factory()->create(['body' => "# Plain\nJust **words** here"]);

    $rows = collect(BaseTable::build(['view' => 'cards'])['rows'])->keyBy('id');

    expect($rows[$picture->id]['thumbnail'])->toContain('/notes/attachments/0b6c6a54-8d4e-4c55-9d64-1f0b3b8c4c11/')->toEndWith('?w=320')
        ->and($rows[$text->id]['thumbnail'])->toBeNull()
        ->and($rows[$text->id]['snippet'])->toBe('Just words here')
        ->and(BaseTable::build([])['rows'][0])->not->toHaveKey('thumbnail');
});

it('reports formula errors in their cells, including loops', function () {
    Note::factory()->create(['body' => "---\nword: hello\n---\nA"]);

    $table = BaseTable::build([
        'columns' => ['formula.bad', 'formula.a', 'formula.ghost', 'formula.typo'],
        'formulas' => [
            ['name' => 'bad', 'expression' => 'word * 2'],
            ['name' => 'a', 'expression' => 'formula.b + 1'],
            ['name' => 'b', 'expression' => 'formula.a + 1'],
            ['name' => 'ghost', 'expression' => 'formula.nope'],
            ['name' => 'typo', 'expression' => '1 +'],
        ],
        'where' => 'shout()',
    ]);

    expect($table['errors'])->toBe(['formula.typo' => 'The formula ends too soon.', 'where' => 'Unknown function "shout".'])
        ->and($table['rows'][0]['cells'])->toBe(['formula.bad' => null, 'formula.a' => null, 'formula.ghost' => null, 'formula.typo' => null])
        ->and($table['rows'][0]['errors'])->toBe([
            'formula.bad' => '* needs a number, not "hello".',
            'formula.a' => '"a" depends on itself.',
            'formula.ghost' => 'There is no formula named "nope".',
            'formula.typo' => 'The formula ends too soon.',
        ]);
});

it('shows a locked note by title only', function () {
    $key = NoteLockKey::query()->create(['salt' => 'c2FsdA==', 'iterations' => 600000, 'verifier_iv' => 'aXY=', 'verifier' => 'dg==']);
    $note = Note::factory()->create(['body' => "---\nsecret: yes\n---\n# Vault"]);
    app(NoteLocks::class)->lock($note, $key, 'aXY=', base64_encode('secret'), 'Vault');

    expect(baseRows(['columns' => ['secret']]))->toBe([['id' => $note->id, 'title' => 'Vault', 'locked' => true, 'cells' => ['secret' => null], 'errors' => []]]);
});

it('opens the first base, or an empty page when there is none', function () {
    $this->get(route('notes.bases.index'))->assertInertia(fn (Assert $page) => $page
        ->component('Bases', false)
        ->where('bases', [])
        ->where('base', null)
        ->where('table', null)
        ->has('options.fields', 5)
        ->where('options.operators.gte', '≥'));

    $base = Base::query()->create(['name' => 'Books', 'definition' => []]);
    Base::query()->create(['name' => 'Zoo', 'definition' => []]);

    $this->get(route('notes.bases.index'))->assertRedirect(route('notes.bases.show', $base));
});

it('shows a base with its table and the properties to choose from', function () {
    Note::factory()->create(['body' => "---\nauthor: Le Guin\n---\n# Earthsea"]);
    $base = Base::query()->create(['name' => 'Books', 'definition' => ['columns' => ['author']]]);

    $this->get(route('notes.bases.show', $base))->assertInertia(fn (Assert $page) => $page
        ->component('Bases', false)
        ->where('base.name', 'Books')
        ->where('base.definition.columns', ['author'])
        ->where('base.definition.sort', [])
        ->where('table.rows.0.cells.author', 'Le Guin')
        ->where('options.properties', ['author'])
        ->where('options.operators.not_contains', 'does not contain'));
});

it('creates, updates and deletes a base', function () {
    $this->post(route('notes.bases.store'), ['name' => ' Reading '])->assertRedirect();
    $base = Base::query()->sole();

    expect($base->name)->toBe('Reading')
        ->and($base->definition)->toBe(['query' => '', 'columns' => ['note.updated'], 'formulas' => [], 'filters' => [], 'where' => '', 'sort' => [], 'view' => 'table', 'group_by' => '']);

    $this->put(route('notes.bases.update', $base), ['name' => 'Books', 'definition' => [
        'query' => 'tag:book',
        'columns' => ['note.title', 'author', 'author', 'formula.age'],
        'formulas' => [['name' => 'age', 'expression' => 'days_between(published, today())']],
        'filters' => [['column' => 'author', 'operator' => 'not_empty', 'value' => null]],
        'sort' => [['column' => 'author', 'direction' => 'desc']],
        'view' => 'cards',
        'group_by' => 'genre',
    ]])->assertSessionHasNoErrors()->assertRedirect();

    expect($base->fresh()->definition)->toBe([
        'query' => 'tag:book',
        'columns' => ['author', 'formula.age'],
        'formulas' => [['name' => 'age', 'expression' => 'days_between(published, today())']],
        'filters' => [['column' => 'author', 'operator' => 'not_empty', 'value' => '']],
        'where' => '',
        'sort' => [['column' => 'author', 'direction' => 'desc']],
        'view' => 'cards',
        'group_by' => 'genre',
    ]);

    $this->delete(route('notes.bases.destroy', $base))->assertRedirect(route('notes.bases.index'));
    expect(Base::query()->count())->toBe(0);
});

it('rejects a definition it cannot use', function (array $definition, string $field, string $message) {
    $this->post(route('notes.bases.store'), ['name' => 'B', 'definition' => $definition])->assertSessionHasErrors([$field => $message]);
})->with([
    'unreadable formula' => [['formulas' => [['name' => 'x', 'expression' => 'price *']]], 'definition.formulas.0.expression', 'The formula ends too soon.'],
    'bad formula name' => [['formulas' => [['name' => '2nd', 'expression' => '1']]], 'definition.formulas.0.name', 'Use letters, digits and _ in a formula name, starting with a letter.'],
    'duplicate formula names' => [['formulas' => [['name' => 'x', 'expression' => '1'], ['name' => 'X', 'expression' => '2']]], 'definition.formulas.0.name', 'Two formulas have this name.'],
    'unknown operator' => [['filters' => [['column' => 'a', 'operator' => 'like', 'value' => 'x']]], 'definition.filters.0.operator', 'The selected definition.filters.0.operator is invalid.'],
    'unreadable where' => [['where' => 'a ==='], 'definition.where', 'Unexpected "=".'],
    'unknown view' => [['view' => 'kanban'], 'definition.view', 'The selected definition.view is invalid.'],
    'too many sorts' => [['sort' => array_fill(0, 4, ['column' => 'a', 'direction' => 'asc'])], 'definition.sort', 'Sort by at most 3 columns.'],
]);

it('requires a name', function () {
    $this->post(route('notes.bases.store'), ['name' => ''])->assertSessionHasErrors(['name' => 'Give the base a name.']);
    $this->post(route('notes.bases.store'), ['name' => 'A|B'])->assertSessionHasErrors(['name' => 'Leave out [ ] | and # so the base can be embedded as ![[Name.base]].']);
});

it("keeps other people's bases out of reach", function () {
    $base = Base::query()->create(['name' => 'Mine', 'definition' => []]);
    $this->actingAs(User::factory()->create());

    $this->get(route('notes.bases.show', $base))->assertNotFound();
    $this->put(route('notes.bases.update', $base), ['name' => 'x'])->assertNotFound();
    $this->delete(route('notes.bases.destroy', $base))->assertNotFound();
    $this->get(route('notes.bases.index'))->assertInertia(fn (Assert $page) => $page->where('bases', []));
});
