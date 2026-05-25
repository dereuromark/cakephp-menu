<?php

declare(strict_types=1);

namespace Menu\Resolver;

use Cake\Core\InstanceConfigTrait;
use Menu\Item\Item;
use Menu\Item\ItemInterface;
use Psr\Http\Message\ServerRequestInterface;
use function array_filter;
use function array_intersect_key;
use function array_merge;
use function array_walk;
use function is_array;
use function is_numeric;
use function ksort;
use const SORT_STRING;

class UrlArrayResolver implements ResolverInterface
{
    use InstanceConfigTrait;

    /**
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'fuzzy' => true,
    ];

    /**
     * @phpstan-param array<string, mixed> $options
     */
    public function __construct(
        protected ServerRequestInterface $request,
        array $options = [],
    ) {
        $this->setConfig($options);
    }

    public function resolve(ItemInterface $item): void
    {
        $link = $item->getLink();
        if ($link === null) {
            return;
        }

        $linkArray = $link->getRawUrl();
        if (!is_array($linkArray)) {
            return;
        }

        $requestParams = (array)$this->request->getAttribute('params');
        foreach ($this->extractRoutes($item, $linkArray) as $route) {
            if ($this->matches($requestParams, $route, $item)) {
                $item->setActive(true);

                return;
            }
        }
    }

    /**
     * @phpstan-param array<string, mixed> $requestParams
     * @phpstan-param array<string|int, mixed> $linkArray
     */
    protected function matches(array $requestParams, array $linkArray, ItemInterface $item): bool
    {
        $normalizedRequestParams = $this->extractParams($requestParams);
        $normalizedRoute = $this->normalizeRoute($linkArray);

        $fuzzy = $item instanceof Item
            ? $item->isFuzzyMatch()
            : false;
        if (!$fuzzy) {
            $fuzzy = (bool)$this->getConfig('fuzzy');
        }

        if ($fuzzy) {
            if (isset($normalizedRoute['?'])) {
                if (array_intersect_key($normalizedRequestParams['?'], $normalizedRoute['?']) !== $normalizedRoute['?']) {
                    return false;
                }

                unset($normalizedRoute['?']);
            }

            return array_intersect_key($normalizedRequestParams, $normalizedRoute) === $normalizedRoute;
        }

        return $normalizedRequestParams === $normalizedRoute;
    }

    /**
     * @param array<string, mixed> $requestParams
     *
     * @return array<string, mixed>
     */
    protected function extractParams(array $requestParams): array
    {
        $params = $requestParams;
        $params['?'] = $this->request->getQueryParams();
        $params['_method'] = $this->request->getMethod();
        $params['_host'] = $this->request->getUri()->getHost();
        if (!isset($params['_ext'])) {
            $params['_ext'] = null;
        }

        $pass = isset($params['pass']) && is_array($params['pass']) ? $params['pass'] : [];
        unset(
            $params['pass'],
            $params['paging'],
            $params['models'],
            $params['url'],
            $params['autoRender'],
            $params['bare'],
            $params['requested'],
            $params['return'],
            $params['isAjax'],
            $params['_Token'],
            $params['_csrfToken'],
            $params['_matchedRoute'],
            $params['_route'],
        );

        $params = array_merge($params, $pass);
        $params += $params['?'];

        return $this->normalizeParams($params);
    }

    /**
     * @param array<string|int, mixed> $route
     *
     * @return array<string, mixed>
     */
    protected function normalizeRoute(array $route): array
    {
        unset(
            $route['#'],
            $route['_base'],
            $route['_scheme'],
            $route['_port'],
            $route['_full'],
            $route['_ssl'],
        );

        /** @var array<string, mixed> $route */
        return $this->normalizeParams($route);
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    protected function normalizeParams(array $params): array
    {
        ksort($params, SORT_STRING);
        array_walk($params, function (mixed &$value): void {
            if (is_numeric($value)) {
                $value = (string)$value;

                return;
            }
            if (is_array($value)) {
                /** @var array<string, mixed> $value */
                $value = $this->normalizeParams($value);
            }
        });

        return $params;
    }

    /**
     * @phpstan-param array<string|int, mixed> $linkArray
     *
     * @return list<array<string|int, mixed>>
     */
    protected function extractRoutes(ItemInterface $item, array $linkArray): array
    {
        $routes = [$linkArray];
        if ($item instanceof Item) {
            $routes = array_merge(
                $routes,
                array_filter($item->getMatchRoutes(), static fn (mixed $route): bool => is_array($route)),
            );
        }

        /** @var list<array<string|int, mixed>> $routes */
        return $routes;
    }
}
