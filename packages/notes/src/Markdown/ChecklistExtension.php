<?php

namespace Coleus\Notes\Markdown;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\Extension\TaskList\TaskListItemMarker;

/**
 * Overrides GFM's disabled task-list checkbox with an interactive one.
 */
class ChecklistExtension implements ExtensionInterface
{
    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addRenderer(TaskListItemMarker::class, new ChecklistMarkerRenderer, 10);
    }
}
