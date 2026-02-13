<?php

namespace Lliure\Menu;

class MenuItem
{
    public string $label;
    public string $url;
    public string $icon;
    public ?string $badge = null;
    public array $children = [];
    public int $order = 0;

    public function __construct(
        string $label,
        string $url,
        string $icon = 'fas fa-circle',
        int $order = 0,
        ?string $badge = null,
        array $children = []
    ) {
        $this->label = $label;
        $this->url = $url;
        $this->icon = $icon;
        $this->order = $order;
        $this->badge = $badge;
        $this->children = $children;
    }

    public function toArray(): array
    {
        return [
            'label'    => $this->label,
            'url'      => $this->url,
            'icon'     => $this->icon,
            'badge'    => $this->badge,
            'children' => array_map(fn(MenuItem $child) => $child->toArray(), $this->children),
            'order'    => $this->order,
        ];
    }
}
