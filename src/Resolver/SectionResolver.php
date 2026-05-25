<?php

declare(strict_types=1);

namespace Menu\Resolver;

use Menu\Item\ItemInterface;
use Psr\Http\Message\ServerRequestInterface;
use function array_is_list;
use function is_array;
use function is_string;

class SectionResolver implements ContextAwareResolverInterface
{
    public function __construct(
        protected ServerRequestInterface $request,
        protected string $dataKey = 'section',
        protected bool $expand = true,
    ) {
    }

    public function resolve(ItemInterface $item): void
    {
        $this->resolveWithContext($item, new ResolverContext());
    }

    public function resolveWithContext(ItemInterface $item, ResolverContext $context): void
    {
        $sections = $item->getData($this->dataKey);
        if ($sections === null) {
            return;
        }

        $requestParams = (array)$this->request->getAttribute('params');
        foreach ($this->normalizeSections($sections) as $section) {
            if ($this->matchesSection($requestParams, $section)) {
                $item->setActive(true);
                if ($this->expand) {
                    $item->setExpanded();
                }

                return;
            }
        }
    }

    /**
     * @param mixed $sections
     *
     * @return list<array<string, mixed>>
     */
    protected function normalizeSections(mixed $sections): array
    {
        if (is_string($sections)) {
            return [['controller' => $sections]];
        }
        if (!is_array($sections)) {
            return [];
        }
        if (!array_is_list($sections)) {
            /** @var array<string, mixed> $sections */
            return [$sections];
        }

        $result = [];
        foreach ($sections as $section) {
            if (is_string($section)) {
                $result[] = ['controller' => $section];
            } elseif (is_array($section)) {
                $result[] = $section;
            }
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $requestParams
     * @param array<string, mixed> $section
     */
    protected function matchesSection(array $requestParams, array $section): bool
    {
        foreach ($section as $key => $value) {
            if (($requestParams[$key] ?? null) !== $value) {
                return false;
            }
        }

        return true;
    }
}
