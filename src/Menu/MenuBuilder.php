<?php

namespace Lliure\Menu;

class MenuBuilder
{
    private static ?self $instance = null;
    private array $items = [];

    private function __construct() {}

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function addItem(MenuItem $item): self
    {
        $this->items[] = $item;
        return $this;
    }

    public function addGroup(string $label, array $items, string $icon = 'fas fa-folder', int $order = 0): self
    {
        $parent = new MenuItem($label, '#', $icon, $order, null, $items);
        $this->items[] = $parent;
        return $this;
    }

    public function getItems(): array
    {
        $items = $this->items;
        usort($items, fn(MenuItem $a, MenuItem $b) => $a->order <=> $b->order);
        return $items;
    }

    public function toArray(): array
    {
        return array_map(fn(MenuItem $item) => $item->toArray(), $this->getItems());
    }
}
