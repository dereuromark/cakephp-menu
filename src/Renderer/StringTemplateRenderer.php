<?php

declare(strict_types=1);

namespace Menu\Renderer;

use Cake\Core\InstanceConfigTrait;
use Cake\View\StringTemplateTrait;
use Menu\Item\ItemInterface;
use Menu\Item\SelfRendererInterface;
use Menu\MenuInterface;
use function array_filter;
use function array_map;
use function array_merge;
use function array_unique;
use function htmlspecialchars;
use function implode;
use function in_array;
use function is_array;
use function sprintf;
use function trim;

class StringTemplateRenderer implements RendererInterface
{
    use InstanceConfigTrait;
    use StringTemplateTrait;

    /**
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'activeClass' => 'active',
        'dividerClass' => 'divider',
        'submenuClass' => 'has-children',
        'templates' => [
            'menuWrapper' => '<ul{{attributes}}>{{items}}</ul>',
            'item' => '<li{{attributes}}>{{content}}</li>',
            'link' => '<a{{attributes}}>{{title}}</a>',
            'label' => '<span{{attributes}}>{{title}}</span>',
            'divider' => '<li{{attributes}}></li>',
        ],
    ];

    /**
     * @phpstan-param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->setConfig($config);
    }

    /**
     * @phpstan-param array<string, mixed> $options
     */
    public function render(MenuInterface $menu, array $options = []): string
    {
        if (isset($options['templates']) && is_array($options['templates'])) {
            $this->setConfig('templates', array_merge($this->getConfig('templates'), $options['templates']));
        }

        $items = [];
        foreach ($menu->getItems() as $item) {
            $html = $this->renderItem($item, $options);
            if ($html !== '') {
                $items[] = $html;
            }
        }

        return $this->templater()->format('menuWrapper', [
            'attributes' => $this->renderAttributes(
                isset($options['attributes']) && is_array($options['attributes'])
                    ? $options['attributes']
                    : $menu->getAttributes(),
            ),
            'items' => implode('', $items),
        ]);
    }

    /**
     * @phpstan-param array<string, mixed> $options
     */
    public function renderItem(ItemInterface $item, array $options = []): string
    {
        if (!$item->isVisible()) {
            return '';
        }

        if ($item instanceof SelfRendererInterface) {
            return $item->render();
        }

        if ($item->isDivider()) {
            $attributes = $item->getAttributes();
            $attributes = $this->appendClass($attributes, $this->getConfig('dividerClass'));

            return $this->templater()->format('divider', [
                'attributes' => $this->renderAttributes($attributes),
            ]);
        }

        $content = $item->getBefore() . $this->renderContent($item) . $item->getAfter();
        if ($item->hasSubMenu()) {
            $content .= $this->render($item->getSubMenu(), $options);
        }

        $attributes = $item->getAttributes();
        if ($item->isActive()) {
            $attributes = $this->appendClass($attributes, $this->getConfig('activeClass'));
        }
        if ($item->hasSubMenu()) {
            $attributes = $this->appendClass($attributes, $this->getConfig('submenuClass'));
        }

        return $this->templater()->format('item', [
            'attributes' => $this->renderAttributes($attributes),
            'content' => $content,
        ]);
    }

    protected function renderContent(ItemInterface $item): string
    {
        if ($item->isRaw()) {
            return (string)$item->getRaw();
        }

        if ($item->getLink() !== null) {
            $attributes = $item->getLink()->getAttributes();
            $attributes['href'] = $item->getLink()->getUrl() ?? '#';

            return $this->templater()->format('link', [
                'attributes' => $this->renderAttributes($attributes),
                'title' => $this->escapeLabel($item),
            ]);
        }

        return $this->templater()->format('label', [
            'attributes' => '',
            'title' => $this->escapeLabel($item),
        ]);
    }

    protected function escapeLabel(ItemInterface $item): string
    {
        $label = $item->getLabel() ?? '';

        if (!$item->shouldEscapeLabel()) {
            return $label;
        }

        return htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
    }

    /**
     * @phpstan-param array<string, mixed> $attributes
     */
    protected function renderAttributes(array $attributes): string
    {
        $result = [];
        foreach ($attributes as $name => $value) {
            if ($value === null || $value === false) {
                continue;
            }
            if ($value === true) {
                $result[] = sprintf(' %s="%s"', $name, $name);

                continue;
            }

            if (is_array($value)) {
                $value = implode(' ', array_filter(array_map('strval', $value)));
            }

            $result[] = sprintf(
                ' %s="%s"',
                $name,
                htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'),
            );
        }

        return implode('', $result);
    }

    /**
     * @phpstan-param array<string, mixed> $attributes
     *
     * @phpstan-return array<string, mixed>
     */
    protected function appendClass(array $attributes, string $class): array
    {
        if ($class === '') {
            return $attributes;
        }

        $existing = $attributes['class'] ?? [];
        if (!is_array($existing)) {
            $existing = trim((string)$existing) === '' ? [] : [trim((string)$existing)];
        }
        if (!in_array($class, $existing, true)) {
            $existing[] = $class;
        }

        $attributes['class'] = array_unique($existing);

        return $attributes;
    }
}
