<?php

declare(strict_types=1);

namespace Menu\Resolver;

use Cake\Core\InstanceConfigTrait;
use Menu\Item\Item;
use Menu\Item\ItemInterface;
use Psr\Http\Message\RequestInterface;
use function array_filter;
use function array_merge;
use function is_string;
use function parse_url;

class Psr7UrlResolver implements ResolverInterface
{
    use InstanceConfigTrait;

    /**
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'ignoreQueryString' => true,
    ];

    /**
     * @phpstan-param array<string, mixed> $options
     */
    public function __construct(
        protected RequestInterface $request,
        array $options = [],
    ) {
        $this->setConfig($options);
    }

    public function resolve(ItemInterface $item): void
    {
        $requestUri = (string)$this->request->getUri();
        $requestPath = parse_url($requestUri, PHP_URL_PATH);
        $ignoreQueryString = $item instanceof Item && $item->getIgnoreQueryString() !== null
            ? $item->getIgnoreQueryString()
            : (bool)$this->getConfig('ignoreQueryString');

        foreach ($this->extractRoutes($item) as $route) {
            if (!is_string($route)) {
                continue;
            }

            if ($requestUri === $route) {
                $item->setActive(true);

                return;
            }

            if (!$ignoreQueryString) {
                continue;
            }

            $routePath = parse_url($route, PHP_URL_PATH);
            if ($requestPath === $routePath) {
                $item->setActive(true);

                return;
            }
        }
    }

    /**
     * @return list<array<string|int, mixed>|string>
     */
    protected function extractRoutes(ItemInterface $item): array
    {
        $routes = [];
        if ($item instanceof Item) {
            $routes = $item->getMatchRoutes();
        }

        $link = $item->getLink();
        if ($link === null) {
            return $routes;
        }

        $rawUrl = $link->getRawUrl();

        return array_merge(
            $routes,
            array_filter([$rawUrl], static fn (mixed $route): bool => is_string($route)),
        );
    }
}
