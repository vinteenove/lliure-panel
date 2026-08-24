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

    /**
     * [spike] Visibilidade opcional: callback avaliado na montagem do menu.
     * Serve para esconder item que o usuário não pode acessar — quem decide é
     * quem hospeda (no Vimu, as permissões da instância). Null = sempre visível,
     * então nada muda para quem não usa.
     *
     * @var null|\Closure():bool
     */
    public ?\Closure $visible = null;

    public function __construct(
        string $label,
        string $url,
        string $icon = 'fas fa-circle',
        int $order = 0,
        ?string $badge = null,
        array $children = [],
        ?\Closure $visible = null
    ) {
        $this->label = $label;
        $this->url = $url;
        $this->icon = $icon;
        $this->order = $order;
        $this->badge = $badge;
        $this->children = $children;
        $this->visible = $visible;
    }

    public function isVisible(): bool
    {
        return $this->visible === null || ($this->visible)();
    }

    public function toArray(): array
    {
        return [
            'label'    => $this->label,
            'url'      => $this->url,
            'icon'     => $this->icon,
            'badge'    => $this->badge,
            'children' => array_map(
                fn(MenuItem $child) => $child->toArray(),
                array_values(array_filter($this->children, fn(MenuItem $c) => $c->isVisible()))
            ),
            'order'    => $this->order,
        ];
    }
}
